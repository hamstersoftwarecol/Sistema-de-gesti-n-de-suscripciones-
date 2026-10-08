<x-app-layout :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $base = base_currency();
    @endphp

    <x-page-header :title="__('Hello, :name', ['name' => \Illuminate\Support\Str::of($user->name)->before(' ')])"
                   :subtitle="ucfirst(now()->translatedFormat('l, j F Y')).' · '.__('Amounts in :currency', ['currency' => $base?->code])">
        <x-slot:actions>
            <a href="{{ route('subscriptions.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New subscription') }}</a>
            @if ($user->isStaff())
                <a href="{{ route('invoices.create') }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4" /> {{ __('New invoice') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <x-stat :label="__('MRR')" :value="money($summary['mrr'])" icon="refresh" :hint="__('Monthly recurring revenue')" class="col-span-2 sm:col-span-1" />
        <x-stat :label="__('ARR')" :value="money($summary['arr'])" icon="chart" color="blue" :hint="__('Annual run rate')" />
        <x-stat :label="__('Active subscriptions')" :value="$summary['active_subscriptions']" icon="check-circle" color="green"
                :hint="trans_choice(':count in trial|:count in trial', $summary['trial_subscriptions'], ['count' => $summary['trial_subscriptions']])" />
        <x-stat :label="__('Revenue this month')" :value="money($summary['revenue_month'])" icon="banknotes" color="green"
                :trend="$summary['revenue_growth']" :hint="__('vs last month')" />
        <x-stat :label="__('Outstanding')" :value="money($summary['outstanding'])" icon="clock" color="amber"
                :hint="__(':count overdue', ['count' => $summary['overdue_count']])" />
        <x-stat :label="__('Churn (30 days)')" :value="$summary['churn_rate'].'%'" icon="trending-down" color="red"
                :hint="__('ARPU :amount', ['amount' => money($summary['arpu'])])" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">{{ __('Revenue (last 12 months)') }}</h2>
                <span class="text-xs text-gray-500">{{ __('Collected vs invoiced') }}</span>
            </div>
            <div class="card-body">
                <div class="h-72">
                    <canvas x-data="chart('bar', @js([
                        'labels' => $revenue['labels'],
                        'datasets' => [
                            ['label' => __('Collected'), 'data' => $revenue['revenue']],
                            ['label' => __('Invoiced'), 'data' => $revenue['invoiced'], 'type' => 'line', 'color' => 'rgb(16 185 129)'],
                        ],
                    ]), { money: @js($base?->symbol ?? '$') })"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Subscriptions by status') }}</h2></div>
            <div class="card-body">
                <div class="h-72">
                    <canvas x-data="chart('doughnut', @js([
                        'labels' => array_keys($byStatus),
                        'datasets' => [['data' => array_values($byStatus)]],
                    ]), { legend: true })"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('Upcoming renewals (14 days)') }}</h2>
                <a href="{{ route('calendar.index') }}" class="link text-sm">{{ __('View calendar') }}</a>
            </div>
            @if ($renewals->isEmpty())
                <x-empty :message="__('No renewals in the next 14 days.')" icon="calendar" />
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($renewals as $subscription)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('subscriptions.show', $subscription) }}" class="link block truncate">{{ $subscription->customer?->displayName() }}</a>
                                <p class="truncate text-xs text-gray-500">{{ $subscription->plan?->fullName() }} · {{ $subscription->reference }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold">{{ $subscription->currency?->format($subscription->periodAmount()) ?? money($subscription->periodAmount()) }}</p>
                                <p class="text-xs {{ $subscription->daysUntilRenewal() <= 3 ? 'text-amber-600' : 'text-gray-500' }}">
                                    {{ fdate($subscription->next_billing_date) }} · {{ trans_choice('in :count day|in :count days', $subscription->daysUntilRenewal(), ['count' => $subscription->daysUntilRenewal()]) }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('Overdue invoices') }}</h2>
                <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="link text-sm">{{ __('View all') }}</a>
            </div>
            @if ($overdueInvoices->isEmpty())
                <x-empty :message="__('No overdue invoices. Great job!')" icon="check-circle" />
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($overdueInvoices as $invoice)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a>
                                <p class="truncate text-xs text-gray-500">{{ $invoice->customer?->displayName() }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $invoice->format($invoice->balance()) }}</p>
                                <p class="text-xs text-gray-500">{{ __(':days days late', ['days' => (int) $invoice->due_date->diffInDays(today())]) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('MRR by plan') }}</h2></div>
            <div class="card-body space-y-4">
                @php $maxMrr = max(1, $byPlan->max('mrr') ?? 1); @endphp
                @forelse ($byPlan as $row)
                    <div>
                        <div class="mb-1 flex justify-between gap-2 text-sm">
                            <span class="truncate text-gray-700 dark:text-gray-300">{{ $row['plan'] }}</span>
                            <span class="shrink-0 font-semibold">{{ money($row['mrr']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ round($row['mrr'] / $maxMrr * 100) }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ trans_choice(':count subscription|:count subscriptions', $row['subscriptions'], ['count' => $row['subscriptions']]) }}</p>
                    </div>
                @empty
                    <x-empty />
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('Recent payments') }}</h2>
                <a href="{{ route('payments.index') }}" class="link text-sm">{{ __('View all') }}</a>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($recentPayments as $payment)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $payment->customer?->displayName() }}</p>
                            <p class="text-xs text-gray-500">{{ fdate($payment->paid_at) }} · {{ \App\Models\Payment::methodOptions()[$payment->method] ?? $payment->method }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-emerald-600 dark:text-emerald-400">+{{ $payment->format() }}</span>
                    </li>
                @empty
                    <li><x-empty /></li>
                @endforelse
            </ul>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('My tasks') }}</h2>
                    <a href="{{ route('kanban.index') }}" class="link text-sm">{{ __('Kanban') }}</a>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($myTasks as $card)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $card->title }}</p>
                                <p class="text-xs text-gray-500">{{ $card->column?->name }} @if ($card->due_date) · <span @class(['text-red-600' => $card->isOverdue()])>{{ fdate($card->due_date) }}</span> @endif</p>
                            </div>
                            <x-status :value="$card->priority" type="priority" />
                        </li>
                    @empty
                        <li><x-empty :message="__('No pending tasks assigned to you.')" icon="check" /></li>
                    @endforelse
                </ul>
            </div>

            @if ($user->isStaff())
                <a href="{{ route('ai.insights') }}" class="card group block overflow-hidden bg-gradient-to-br from-primary-600 to-primary-800 p-5 text-white ring-0 dark:from-primary-700 dark:to-primary-950">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15"><x-icon name="sparkles" /></span>
                        <div>
                            <p class="font-semibold">{{ __('AI business analysis') }}</p>
                            <p class="text-sm text-white/80">{{ __('Ask Gemini for insights about churn, revenue and pricing.') }}</p>
                        </div>
                    </div>
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
