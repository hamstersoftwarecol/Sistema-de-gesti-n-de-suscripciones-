<x-app-layout :title="$supplier->name">
    <x-page-header :title="$supplier->name" :subtitle="$supplier->contact_name" :back="route('suppliers.index')">
        <x-slot:actions>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Details') }}</h2><x-status :value="(bool) $supplier->is_active" type="bool" /></div>
                <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
                    <div class="dl-row"><dt>{{ __('Email') }}</dt><dd>{{ $supplier->email ?? '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Phone') }}</dt><dd>{{ $supplier->phone ?? '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Website') }}</dt><dd>@if ($supplier->website)<a href="{{ $supplier->website }}" target="_blank" rel="noopener" class="link">{{ parse_url($supplier->website, PHP_URL_HOST) }}</a>@else — @endif</dd></div>
                    <div class="dl-row"><dt>{{ __('Tax ID') }}</dt><dd>{{ $supplier->tax_id ?? '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Address') }}</dt><dd>{{ collect([$supplier->address, $supplier->country])->filter()->implode(', ') ?: '—' }}</dd></div>
                </dl>
                <x-custom-fields-display :model="$supplier" class="border-t border-gray-100 px-4 py-2 dark:border-gray-800 sm:px-6" />
                @if ($supplier->notes)
                    <p class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400 sm:px-6">{!! nl2br(e($supplier->notes)) !!}</p>
                @endif
            </div>
            <x-stat :label="__('Estimated cost per period')" :value="money($monthlyCost)" icon="banknotes" color="amber" :hint="__('Unit cost × active seats')" />
        </div>

        <div class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Products supplied') }}</h2></div>
            @if ($supplier->products->isEmpty())
                <x-empty icon="cube" />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Product') }}</th><th>{{ __('Category') }}</th><th>{{ __('Unit cost') }}</th><th>{{ __('Plans') }}</th></tr></thead>
                        <tbody>
                            @foreach ($supplier->products as $product)
                                <tr>
                                    <td><a href="{{ route('products.show', $product) }}" class="link">{{ $product->name }}</a></td>
                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                    <td>{{ money($product->cost) }}</td>
                                    <td>{{ $product->plans->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
