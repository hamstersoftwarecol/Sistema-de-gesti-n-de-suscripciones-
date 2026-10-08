<x-app-layout :title="$seller->name">
    @php $self = $self ?? false; @endphp
    <x-page-header :title="$self ? __('My commissions') : $seller->name" :subtitle="__('Commission rate: :rate%', ['rate' => (float) $seller->commission_rate])" :back="$self ? null : route('sellers.index')">
        @unless ($self)
            <x-slot:actions>
                <a href="{{ route('sellers.edit', $seller) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</a>
                <x-delete-button :action="route('sellers.destroy', $seller)" :label="__('Delete')" size="md" />
            </x-slot:actions>
        @endunless
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat :label="__('Sales this month')" :value="money($current['revenue'])" icon="banknotes" color="green" />
        <x-stat :label="__('Commission this month')" :value="money($current['commission'])" icon="currency" />
        <x-stat :label="__('Active subscriptions')" :value="$liveSubscriptions->count()" icon="refresh" color="blue"
                :hint="__('MRR :amount', ['amount' => money($liveSubscriptions->sum(fn ($s) => $s->monthlyAmountInBase()))])" />
        <x-stat :label="__('Monthly target')" :value="$targetProgress !== null ? $targetProgress.'%' : '—'" icon="trending-up" color="amber"
                :hint="(float) $seller->monthly_target ? money($seller->monthly_target) : __('No target set')" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Sales and commissions (6 months)') }}</h2></div>
            <div class="card-body">
                <div class="h-64">
                    <canvas x-data="chart('bar', @js([
                        'labels' => $months->pluck('label'),
                        'datasets' => [
                            ['label' => __('Sales'), 'data' => $months->pluck('revenue')],
                            ['label' => __('Commission'), 'data' => $months->pluck('commission'), 'color' => 'rgb(16 185 129)'],
                        ],
                    ]), { money: @js(base_currency()?->symbol ?? '$') })"></canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Monthly breakdown') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Month') }}</th><th class="text-right">{{ __('Sales') }}</th><th class="text-right">{{ __('Commission') }}</th></tr></thead>
                    <tbody>
                        @foreach ($months->reverse() as $month)
                            <tr><td>{{ $month['label'] }}</td><td class="text-right">{{ money($month['revenue']) }}</td><td class="text-right font-medium text-emerald-600">{{ money($month['commission']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Customers') }}</h2></div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($seller->customers as $customer)
                    <li class="flex items-center justify-between px-4 py-3 sm:px-6">
                        <a href="{{ route('customers.show', $customer) }}" class="link truncate">{{ $customer->displayName() }}</a>
                        <span class="text-xs text-gray-500">{{ trans_choice(':count active subscription|:count active subscriptions', $customer->live_count, ['count' => $customer->live_count]) }}</span>
                    </li>
                @empty
                    <li><x-empty icon="users" /></li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Latest attributed payments') }}</h2></div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($recentPayments as $payment)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $payment->customer?->displayName() }}</p>
                            <p class="text-xs text-gray-500">{{ fdate($payment->paid_at) }} · {{ $payment->invoice?->number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold">{{ $payment->format() }}</p>
                            <p class="text-xs text-emerald-600">+{{ money($payment->baseAmount() * (float) $seller->commission_rate / 100) }}</p>
                        </div>
                    </li>
                @empty
                    <li><x-empty icon="banknotes" /></li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
