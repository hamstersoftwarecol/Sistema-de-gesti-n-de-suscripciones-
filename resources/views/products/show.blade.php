<x-app-layout :title="$product->name">
    <x-page-header :title="$product->name" :subtitle="($product->sku ? $product->sku.' · ' : '').(\App\Models\Product::typeOptions()[$product->type] ?? $product->type)" :back="route('products.index')">
        <x-slot:actions>
            <button type="button" class="btn-primary" x-data @click="$dispatch('open-modal', 'plan-new')"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add plan') }}</button>
            <a href="{{ route('products.edit', $product) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</a>
            <x-delete-button :action="route('products.destroy', $product)" :label="__('Delete')" size="md" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Details') }}</h2><x-status :value="(bool) $product->is_active" type="bool" /></div>
            <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
                <div class="dl-row"><dt>{{ __('Category') }}</dt><dd>{{ $product->category?->name ?? '—' }}</dd></div>
                <div class="dl-row"><dt>{{ __('Supplier') }}</dt><dd>@if ($product->supplier)<a href="{{ route('suppliers.show', $product->supplier) }}" class="link">{{ $product->supplier->name }}</a>@else — @endif</dd></div>
                <div class="dl-row"><dt>{{ __('Unit cost (per period)') }}</dt><dd>{{ money($product->cost) }}</dd></div>
            </dl>
            <x-custom-fields-display :model="$product" class="border-t border-gray-100 px-4 py-2 dark:border-gray-800 sm:px-6" />
            @if ($product->description)
                <p class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400 sm:px-6">{!! nl2br(e($product->description)) !!}</p>
            @endif
        </div>

        <div class="lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($product->plans as $plan)
                    <div class="card flex flex-col p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold">{{ $plan->name }}</p>
                                <p class="text-xs text-gray-500">{{ $plan->cycleLabel() }}</p>
                            </div>
                            <x-status :value="(bool) $plan->is_active" type="bool" />
                        </div>
                        <p class="mt-3 text-3xl font-bold">{{ $plan->currency?->format($plan->price) }}</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            @if ($plan->trial_days) <span class="badge bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">{{ __(':days-day trial', ['days' => $plan->trial_days]) }}</span> @endif
                            @if ((float) $plan->setup_fee) <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Setup fee') }} {{ $plan->currency?->format($plan->setup_fee) }}</span> @endif
                            <span class="badge bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ trans_choice(':count active subscription|:count active subscriptions', $plan->live_count, ['count' => $plan->live_count]) }}</span>
                        </div>
                        @if ($plan->featureList())
                            <ul class="mt-4 flex-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                @foreach ($plan->featureList() as $feature)
                                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-4 flex gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                            <button type="button" class="btn-secondary btn-sm" x-data @click="$dispatch('open-modal', 'plan-{{ $plan->id }}')"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</button>
                            <a href="{{ route('subscriptions.create') }}" class="btn-ghost btn-sm"><x-icon name="plus" class="h-4 w-4" /> {{ __('Subscribe') }}</a>
                            <div class="ml-auto"><x-delete-button :action="route('plans.destroy', $plan)" /></div>
                        </div>
                    </div>

                    <x-modal :name="'plan-'.$plan->id" maxWidth="xl">
                        <form method="POST" action="{{ route('plans.update', $plan) }}" class="p-6">
                            @csrf @method('PUT')
                            <h2 class="mb-4 text-lg font-semibold">{{ __('Edit plan') }}</h2>
                            @include('products._plan-form', ['plan' => $plan])
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Cancel') }}</button>
                                <button class="btn-primary">{{ __('Save') }}</button>
                            </div>
                        </form>
                    </x-modal>
                @empty
                    <div class="card sm:col-span-2">
                        <x-empty :message="__('This product has no pricing plans yet.')" icon="tag">
                            <button type="button" class="btn-primary" x-data @click="$dispatch('open-modal', 'plan-new')">{{ __('Add plan') }}</button>
                        </x-empty>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <x-modal name="plan-new" maxWidth="xl" :show="$errors->any()">
        <form method="POST" action="{{ route('plans.store', $product) }}" class="p-6">
            @csrf
            <h2 class="mb-4 text-lg font-semibold">{{ __('New plan') }}</h2>
            @include('products._plan-form', ['plan' => new \App\Models\Plan()])
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Cancel') }}</button>
                <button class="btn-primary">{{ __('Create plan') }}</button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
