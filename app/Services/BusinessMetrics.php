<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Seller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * KPIs used by the dashboard, reports and as context for the AI assistant.
 * Every amount is converted to the base currency.
 */
class BusinessMetrics
{
    public function summary(?User $user = null): array
    {
        $paying = Subscription::query()->visibleTo($user)->with('plan', 'currency')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->get();

        $mrr = $paying->sum(fn (Subscription $s) => $s->monthlyAmountInBase());
        $payingCustomers = $paying->pluck('customer_id')->unique()->count();

        $monthStart = now()->startOfMonth();
        $revenueMonth = $this->revenueBetween($monthStart, now(), $user);
        $revenueLastMonth = $this->revenueBetween($monthStart->copy()->subMonthNoOverflow(), $monthStart->copy()->subDay(), $user);

        $open = Invoice::query()->visibleTo($user)->with('currency')->open()->get();
        $overdue = $open->where('status', Invoice::STATUS_OVERDUE);

        $churned = Subscription::query()->visibleTo($user)
            ->whereIn('status', [Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED])
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        $liveCount = Subscription::query()->visibleTo($user)->live()->count();

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'active_subscriptions' => $paying->count(),
            'trial_subscriptions' => Subscription::query()->visibleTo($user)->where('status', Subscription::STATUS_TRIAL)->count(),
            'customers' => Customer::query()->visibleTo($user)->where('status', 'active')->count(),
            'paying_customers' => $payingCustomers,
            'arpu' => $payingCustomers ? round($mrr / $payingCustomers, 2) : 0,
            'revenue_month' => round($revenueMonth, 2),
            'revenue_last_month' => round($revenueLastMonth, 2),
            'revenue_growth' => $revenueLastMonth > 0 ? round((($revenueMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1) : null,
            'outstanding' => round($open->sum(fn (Invoice $i) => $i->balance() / ((float) $i->exchange_rate ?: 1)), 2),
            'overdue_count' => $overdue->count(),
            'overdue_amount' => round($overdue->sum(fn (Invoice $i) => $i->balance() / ((float) $i->exchange_rate ?: 1)), 2),
            'churn_rate' => ($liveCount + $churned) > 0 ? round($churned / ($liveCount + $churned) * 100, 1) : 0,
            'churned_30d' => $churned,
            'new_subscriptions_month' => Subscription::query()->visibleTo($user)->where('created_at', '>=', $monthStart)->count(),
        ];
    }

    public function revenueBetween(Carbon $from, Carbon $to, ?User $user = null): float
    {
        return Payment::query()->visibleTo($user)->with('currency')->completed()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->get()
            ->sum(fn (Payment $p) => $p->baseAmount());
    }

    /** @return array{labels: array<int, string>, revenue: array<int, float>, invoiced: array<int, float>} */
    public function revenueByMonth(int $months = 12, ?User $user = null): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow($months - 1);

        $payments = Payment::query()->visibleTo($user)->with('currency')->completed()
            ->whereDate('paid_at', '>=', $start)
            ->get()
            ->groupBy(fn (Payment $p) => $p->paid_at->format('Y-m'));

        $invoices = Invoice::query()->visibleTo($user)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->whereDate('issue_date', '>=', $start)
            ->whereDate('issue_date', '<=', today())
            ->get()
            ->groupBy(fn (Invoice $i) => $i->issue_date->format('Y-m'));

        $labels = $revenue = $invoiced = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $labels[] = ucfirst($month->translatedFormat('M Y'));
            $revenue[] = round(($payments[$key] ?? collect())->sum(fn (Payment $p) => $p->baseAmount()), 2);
            $invoiced[] = round(($invoices[$key] ?? collect())->sum(fn (Invoice $inv) => $inv->totalInBase()), 2);
        }

        return compact('labels', 'revenue', 'invoiced');
    }

    /** @return array<string, int> */
    public function subscriptionsByStatus(?User $user = null): array
    {
        $counts = Subscription::query()->visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(Subscription::statusOptions())
            ->mapWithKeys(fn ($label, $status) => [$label => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    /** @return Collection<int, array{plan: string, subscriptions: int, mrr: float}> */
    public function mrrByPlan(int $limit = 6, ?User $user = null): Collection
    {
        return Subscription::query()->visibleTo($user)->with('plan.product', 'currency')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->get()
            ->groupBy('plan_id')
            ->map(fn (Collection $group) => [
                'plan' => $group->first()->plan?->fullName() ?? '—',
                'subscriptions' => $group->count(),
                'mrr' => round($group->sum(fn (Subscription $s) => $s->monthlyAmountInBase()), 2),
            ])
            ->sortByDesc('mrr')
            ->take($limit)
            ->values();
    }

    public function upcomingRenewals(int $days = 14, int $limit = 8, ?User $user = null): Collection
    {
        return Subscription::query()->visibleTo($user)->with('customer', 'plan.product', 'currency')
            ->live()
            ->whereBetween('next_billing_date', [today()->toDateString(), today()->addDays($days)->toDateString()])
            ->orderBy('next_billing_date')
            ->take($limit)
            ->get();
    }

    /** Compact snapshot sent to Gemini as grounding context. */
    public function aiContext(): array
    {
        $summary = $this->summary();
        $revenue = $this->revenueByMonth(6);

        $topCustomers = Subscription::query()->with('customer', 'plan', 'currency')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->get()
            ->groupBy('customer_id')
            ->map(fn (Collection $subs) => [
                'customer' => $subs->first()->customer?->displayName(),
                'mrr' => round($subs->sum(fn (Subscription $s) => $s->monthlyAmountInBase()), 2),
                'subscriptions' => $subs->count(),
            ])
            ->sortByDesc('mrr')
            ->take(5)
            ->values();

        $overdueInvoices = Invoice::query()->with('customer', 'currency')
            ->where('status', Invoice::STATUS_OVERDUE)
            ->orderBy('due_date')
            ->take(10)
            ->get()
            ->map(fn (Invoice $i) => [
                'number' => $i->number,
                'customer' => $i->customer?->displayName(),
                'balance' => $i->format($i->balance()),
                'due_date' => $i->due_date?->toDateString(),
                'days_overdue' => (int) $i->due_date?->diffInDays(today()),
            ]);

        $sellers = Seller::query()->active()->get()->map(fn (Seller $s) => [
            'seller' => $s->name,
            'commission_rate' => (float) $s->commission_rate,
            'revenue_this_month' => round($s->revenueBetween(now()->startOfMonth(), now()), 2),
            'customers' => $s->customers()->count(),
        ]);

        return [
            'today' => today()->toDateString(),
            'base_currency' => base_currency()?->code,
            'kpis' => $summary,
            'revenue_last_6_months' => array_combine($revenue['labels'], $revenue['revenue']),
            'invoiced_last_6_months' => array_combine($revenue['labels'], $revenue['invoiced']),
            'subscriptions_by_status' => $this->subscriptionsByStatus(),
            'mrr_by_plan' => $this->mrrByPlan(10),
            'top_customers_by_mrr' => $topCustomers,
            'overdue_invoices' => $overdueInvoices,
            'upcoming_renewals_14_days' => $this->upcomingRenewals(14, 15)->map(fn (Subscription $s) => [
                'reference' => $s->reference,
                'customer' => $s->customer?->displayName(),
                'plan' => $s->plan?->fullName(),
                'date' => $s->next_billing_date?->toDateString(),
                'auto_renew' => $s->auto_renew,
            ]),
            'sellers' => $sellers,
        ];
    }
}
