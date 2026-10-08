<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Recurring billing engine: creates subscriptions, issues period invoices,
 * renews automatically, expires non-renewing plans and flags overdue invoices.
 */
class BillingService
{
    /** Safety limit when catching up many missed periods in a single run. */
    public const MAX_CATCH_UP_PERIODS = 24;

    public function __construct(private readonly EmailService $emails) {}

    /**
     * Create a subscription, scheduling its trial and (optionally) issuing the first invoice.
     */
    public function subscribe(array $data, bool $issueFirstInvoice = true): Subscription
    {
        return DB::transaction(function () use ($data, $issueFirstInvoice) {
            /** @var Plan $plan */
            $plan = Plan::query()->findOrFail($data['plan_id']);
            $start = Carbon::parse($data['start_date'] ?? today())->startOfDay();
            $trialDays = (int) ($data['trial_days'] ?? $plan->trial_days);

            $subscription = new Subscription(array_merge([
                'price' => $plan->price,
                'currency_id' => $plan->currency_id,
                'quantity' => 1,
                'discount' => 0,
                'auto_renew' => true,
            ], collect($data)->except(['trial_days'])->all()));

            $subscription->start_date = $start;

            if ($trialDays > 0) {
                $trialEnd = $start->copy()->addDays($trialDays);
                $subscription->status = Subscription::STATUS_TRIAL;
                $subscription->trial_ends_at = $trialEnd;
                $subscription->current_period_start = $start;
                $subscription->current_period_end = $trialEnd->copy()->subDay();
                $subscription->next_billing_date = $trialEnd;
                $subscription->save();

                return $subscription;
            }

            $subscription->status = $data['status'] ?? Subscription::STATUS_ACTIVE;
            $subscription->next_billing_date = $start;
            $subscription->save();

            if ($issueFirstInvoice) {
                $this->renew($subscription, automatic: false, includeSetupFee: true);
            } else {
                $subscription->forceFill([
                    'current_period_start' => $start,
                    'current_period_end' => $plan->addPeriods($start)->subDay(),
                    'next_billing_date' => $plan->addPeriods($start),
                ])->save();
            }

            return $subscription->refresh();
        });
    }

    /**
     * Bill the next period of a subscription: issue the invoice and move the period forward.
     */
    public function renew(Subscription $subscription, bool $automatic = true, bool $includeSetupFee = false): Invoice
    {
        return DB::transaction(function () use ($subscription, $automatic, $includeSetupFee) {
            $subscription->loadMissing('plan.product', 'customer', 'currency');
            $plan = $subscription->plan;

            $periodStart = ($subscription->next_billing_date ?? today())->copy()->startOfDay();
            $nextBilling = $plan->addPeriods($periodStart);
            $periodEnd = $nextBilling->copy()->subDay();

            $invoice = $this->invoiceForPeriod($subscription, $periodStart, $periodEnd, $includeSetupFee);

            $subscription->forceFill([
                'status' => $subscription->status === Subscription::STATUS_TRIAL ? Subscription::STATUS_ACTIVE : $subscription->status,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'next_billing_date' => $nextBilling,
            ])->save();

            SubscriptionRenewal::query()->create([
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'amount' => $invoice->total,
                'status' => 'success',
                'is_automatic' => $automatic,
            ]);

            ActivityLog::record('renewed', $subscription, "Subscription {$subscription->reference} → {$invoice->number}");

            return $invoice;
        });
    }

    /**
     * Build an invoice covering one billing period of the subscription.
     */
    public function invoiceForPeriod(Subscription $subscription, Carbon $start, Carbon $end, bool $includeSetupFee = false): Invoice
    {
        $plan = $subscription->plan;
        $currency = $subscription->currency ?? $plan->currency;
        $issueDate = $start->copy();

        $invoice = Invoice::query()->create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'currency_id' => $currency?->id,
            'exchange_rate' => $currency?->exchange_rate ?? 1,
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays((int) Setting::get('invoice_due_days', 7)),
            'status' => Invoice::STATUS_SENT,
            'tax_rate' => (float) Setting::get('default_tax_rate', 0),
            'notes' => Setting::get('invoice_notes'),
            'terms' => Setting::get('invoice_terms'),
        ]);

        $description = sprintf(
            '%s (%s → %s)',
            $plan->fullName(),
            $start->format('d/m/Y'),
            $end->format('d/m/Y'),
        );

        $invoice->items()->create([
            'plan_id' => $plan->id,
            'product_id' => $plan->product_id,
            'description' => $description,
            'quantity' => max(1, $subscription->quantity),
            'unit_price' => $subscription->price,
            'discount' => $subscription->discount,
            'sort_order' => 1,
        ]);

        if ($includeSetupFee && (float) $plan->setup_fee > 0) {
            $invoice->items()->create([
                'plan_id' => $plan->id,
                'product_id' => $plan->product_id,
                'description' => __('Setup fee').' — '.$plan->fullName(),
                'quantity' => 1,
                'unit_price' => $plan->setup_fee,
                'discount' => 0,
                'sort_order' => 2,
            ]);
        }

        $invoice->recalculate();

        if (Setting::bool('auto_email_invoices') && $start->lte(today())) {
            $this->emails->sendInvoice($invoice);
        }

        return $invoice;
    }

    public function cancel(Subscription $subscription, bool $immediately = false, ?string $reason = null): void
    {
        if ($immediately) {
            $subscription->forceFill([
                'status' => Subscription::STATUS_CANCELLED,
                'auto_renew' => false,
                'cancelled_at' => now(),
                'ends_at' => today(),
                'next_billing_date' => null,
                'cancel_reason' => $reason,
            ])->save();

            return;
        }

        // Cancel at period end: the engine will expire it when the current period finishes.
        $subscription->forceFill([
            'auto_renew' => false,
            'cancelled_at' => now(),
            'ends_at' => $subscription->current_period_end,
            'cancel_reason' => $reason,
        ])->save();
    }

    public function pause(Subscription $subscription): void
    {
        $subscription->forceFill(['status' => Subscription::STATUS_PAUSED])->save();
    }

    public function resume(Subscription $subscription): void
    {
        $nextBilling = $subscription->next_billing_date;

        $subscription->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'auto_renew' => true,
            'cancelled_at' => null,
            'ends_at' => null,
            'next_billing_date' => $nextBilling && $nextBilling->gte(today()) ? $nextBilling : today(),
        ])->save();
    }

    /**
     * Run the daily billing cycle.
     *
     * @return array{renewed: int, invoices: int, expired: int, overdue: int, past_due: int}
     */
    public function run(?Carbon $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();
        $summary = ['renewed' => 0, 'invoices' => 0, 'expired' => 0, 'overdue' => 0, 'past_due' => 0];

        $due = Subscription::query()
            ->with('plan.product', 'customer', 'currency')
            ->live()
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', $today)
            ->get();

        foreach ($due as $subscription) {
            $endsWithoutRenewal = ! $subscription->auto_renew
                || ($subscription->ends_at && $subscription->ends_at->lt($subscription->next_billing_date));

            if ($endsWithoutRenewal) {
                $this->expire($subscription);
                $summary['expired']++;

                continue;
            }

            $periods = 0;
            while ($subscription->next_billing_date && $subscription->next_billing_date->lte($today) && $periods < self::MAX_CATCH_UP_PERIODS) {
                $this->renew($subscription, automatic: true);
                $periods++;
            }

            $summary['renewed']++;
            $summary['invoices'] += $periods;
        }

        // Flag unpaid invoices whose due date has passed.
        $overdue = Invoice::query()
            ->with('subscription')
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIAL])
            ->whereDate('due_date', '<', $today)
            ->get();

        foreach ($overdue as $invoice) {
            $invoice->forceFill(['status' => Invoice::STATUS_OVERDUE])->save();
            $summary['overdue']++;

            $subscription = $invoice->subscription;
            if ($subscription && $subscription->status === Subscription::STATUS_ACTIVE && Setting::bool('mark_past_due', true)) {
                $subscription->forceFill(['status' => Subscription::STATUS_PAST_DUE])->save();
                $summary['past_due']++;
            }
        }

        Setting::set('last_billing_run', now()->toDateTimeString());

        return $summary;
    }

    public function expire(Subscription $subscription): void
    {
        $subscription->forceFill([
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => $subscription->ends_at ?? $subscription->current_period_end ?? today(),
            'next_billing_date' => null,
        ])->save();

        $this->emails->sendForSubscription(EmailTemplate::SUBSCRIPTION_EXPIRED, $subscription);
    }
}
