<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

/**
 * Daily e-mail reminders: upcoming renewals and overdue invoices.
 */
class ReminderService
{
    public function __construct(private readonly EmailService $emails) {}

    /** @return array<int> */
    public function reminderDays(): array
    {
        return collect(explode(',', (string) Setting::get('reminder_days', '7,3,1')))
            ->map(fn ($day) => (int) trim($day))
            ->filter(fn ($day) => $day > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array{renewal: int, overdue: int, failed: int}
     */
    public function run(?Carbon $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();
        $summary = ['renewal' => 0, 'overdue' => 0, 'failed' => 0];

        if (! Setting::bool('reminders_enabled', true)) {
            return $summary;
        }

        foreach ($this->reminderDays() as $days) {
            $subscriptions = Subscription::query()
                ->with('customer', 'plan.product', 'currency')
                ->live()
                ->whereDate('next_billing_date', $today->copy()->addDays($days))
                ->get();

            foreach ($subscriptions as $subscription) {
                if ($this->alreadySent(EmailTemplate::RENEWAL_REMINDER, ['subscription_id' => $subscription->id], $today)) {
                    continue;
                }

                $log = $this->emails->sendForSubscription(EmailTemplate::RENEWAL_REMINDER, $subscription, ['days_left' => $days]);
                $this->count($summary, 'renewal', $log);
            }
        }

        $interval = max(1, (int) Setting::get('overdue_reminder_interval', 3));

        $invoices = Invoice::query()
            ->with('customer', 'currency', 'subscription.plan.product')
            ->where('status', Invoice::STATUS_OVERDUE)
            ->get();

        foreach ($invoices as $invoice) {
            if ($this->alreadySent(EmailTemplate::INVOICE_OVERDUE, ['invoice_id' => $invoice->id], $today->copy()->subDays($interval - 1))) {
                continue;
            }

            $log = $this->emails->sendInvoice($invoice, EmailTemplate::INVOICE_OVERDUE);
            $this->count($summary, 'overdue', $log);
        }

        Setting::set('last_reminder_run', now()->toDateTimeString());

        return $summary;
    }

    protected function alreadySent(string $template, array $where, Carbon $since): bool
    {
        return EmailLog::query()
            ->where('template_key', $template)
            ->where($where)
            ->where('status', 'sent')
            ->whereDate('created_at', '>=', $since)
            ->exists();
    }

    protected function count(array &$summary, string $key, ?EmailLog $log): void
    {
        if (! $log) {
            return;
        }

        $log->status === 'sent' ? $summary[$key]++ : $summary['failed']++;
    }
}
