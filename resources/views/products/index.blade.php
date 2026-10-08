<x-app-layout :title="__('Products & plans')">
    <x-page-header :title="__('Product catalog')" :subtitle="__('Products, services and their pricing plans')">
        <x-slot:actions>
            <a href="{{ route('categories.index') }}" class="btn-secondary"><x-icon name="tag" class="h-4 w-4" /> {{ __('Categories') }}</a>
            <a href="{{ route('products.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New product') }}</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-[12rem] flex-1"><input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Name or SKU') }}"></div>
        <select name="category" class="input w-auto">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $id => $name)
                <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="supplier" class="input w-auto">
            <option value="">{{ __('All suppliers') }}</option>
            @foreach ($suppliers as $id => $name)
                <option value="{{ $id }}" @selected((string) request('supplier') === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
    </form>

    @if ($products->isEmpty())
        <div class="card"><x-empty :message="__('No products yet.')" icon="cube"><a href="{{ route('products.create') }}" class="btn-primary">{{ __('New product') }}</a></x-empty></div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($products as $product)
                <a href="{{ route('products.show', $product) }}" class="card group flex flex-col p-5 transition hover:ring-primary-300 dark:hover:ring-primary-700">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900 group-hover:text-primary-600 dark:text-white">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $product->sku ?? '—' }} · {{ \App\Models\Product::typeOptions()[$product->type] ?? $product->type }}</p>
                        </div>
                        @if ($product->category)
                            <span class="badge shrink-0" style="background: {{ $product->category->color }}20; color: {{ $product->category->color }}">{{ $product->category->name }}</span>
                        @endif
                    </div>
                    <div class="mt-4 flex-1 space-y-1">
                        @forelse ($product->plans->take(3) as $plan)
                            <div class="flex justify-between text-sm">
                                <span class="truncate text-gray-600 dark:text-gray-400">{{ $plan->name }}</span>
                                <span class="shrink-0 font-medium">{{ $plan->currency?->format($plan->price) }} <span class="text-xs text-gray-500">/ {{ mb_strtolower($plan->cycleLabel()) }}</span></span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No plans yet.') }}</p>
                        @endforelse
                        @if ($product->plans->count() > 3)
                            <p class="text-xs text-gray-500">+{{ $product->plans->count() - 3 }} {{ __('more') }}</p>
                        @endif
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800">
                        <span>{{ trans_choice(':count active subscription|:count active subscriptions', $product->live_subscriptions_count, ['count' => $product->live_subscriptions_count]) }}</span>
                        <x-status :value="(bool) $product->is_active" type="bool" />
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-app-layout>
