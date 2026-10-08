<x-app-layout :title="__('Sellers')">
    <x-page-header :title="__('Sales team')" :subtitle="__('Sellers, commissions and targets')">
        <x-slot:actions>
            <a href="{{ route('sellers.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New seller') }}</a>
        </x-slot:actions>
    </x-page-header>

    @if ($sellers->isEmpty())
        <div class="card"><x-empty :message="__('No sellers yet.')" icon="briefcase" /></div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($sellers as $seller)
                @php $progress = (float) $seller->monthly_target > 0 ? min(100, round($seller->revenue_month / (float) $seller->monthly_target * 100)) : null; @endphp
                <a href="{{ route('sellers.show', $seller) }}" class="card block p-5 transition hover:ring-primary-300 dark:hover:ring-primary-700">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                            {{ collect(explode(' ', $seller->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $seller->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $seller->email }} @if ($seller->user) · <span class="text-emerald-600">{{ __('has login') }}</span> @endif</p>
                        </div>
                        <x-status :value="(bool) $seller->is_active" type="bool" />
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div><p class="text-xs text-gray-500">{{ __('Customers') }}</p><p class="font-semibold">{{ $seller->customers_count }}</p></div>
                        <div><p class="text-xs text-gray-500">{{ __('Active subs.') }}</p><p class="font-semibold">{{ $seller->live_subscriptions_count }}</p></div>
                        <div><p class="text-xs text-gray-500">{{ __('Commission') }}</p><p class="font-semibold">{{ (float) $seller->commission_rate }}%</p></div>
                    </div>
                    <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('Sales this month') }}</span><span class="font-semibold">{{ money($seller->revenue_month) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('Commission this month') }}</span><span class="font-semibold text-emerald-600">{{ money($seller->commission_month) }}</span></div>
                        @if ($progress !== null)
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full bg-primary-500" style="width: {{ $progress }}%"></div></div>
                            <p class="mt-1 text-xs text-gray-500">{{ __(':percent% of the :target target', ['percent' => $progress, 'target' => money($seller->monthly_target)]) }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
