<x-app-layout :title="$invoice->exists ? __('Edit invoice') : __('New invoice')">
    <x-page-header :title="$invoice->exists ? __('Edit invoice :number', ['number' => $invoice->number]) : __('New invoice')"
                   :back="$invoice->exists ? route('invoices.show', $invoice) : route('invoices.index')" />

    @php
        $oldItems = old('items', $items);
        $currencyData = $currencies->mapWithKeys(fn ($c) => [$c->id => ['symbol' => $c->symbol, 'decimals' => $c->decimal_places]]);
        $selectedCurrency = $currencies->firstWhere('id', old('currency_id', $invoice->currency_id)) ?? $currencies->first();
    @endphp

    <form method="POST" action="{{ $invoice->exists ? route('invoices.update', $invoice) : route('invoices.store') }}"
          x-data="invoiceForm(@js(array_values($oldItems)), @js((float) old('tax_rate', $invoice->tax_rate)), @js($selectedCurrency?->symbol ?? '$'), @js($selectedCurrency?->decimal_places ?? 2))"
          class="space-y-6">
        @csrf
        @if ($invoice->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-forms.select name="customer_id" :label="__('Customer')" :options="$customers" :value="$invoice->customer_id" :placeholder="__('Select a customer...')" required class="sm:col-span-2" />
                <div>
                    <label class="label" for="currency_id">{{ __('Currency') }} <span class="text-red-500">*</span></label>
                    <select id="currency_id" name="currency_id" class="input" required
                            @change="const c = @js($currencyData)[$event.target.value]; if (c) { symbol = c.symbol; decimals = c.decimals; }">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected((string) old('currency_id', $invoice->currency_id) === (string) $currency->id)>{{ $currency->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-forms.select name="status" :label="__('Status')" :value="$invoice->status" required
                                :options="['draft' => __('Draft'), 'sent' => __('Sent / issued'), 'cancelled' => __('Cancelled')]" />
                <x-forms.input name="issue_date" type="date" :label="__('Issue date')" :value="$invoice->issue_date" required />
                <x-forms.input name="due_date" type="date" :label="__('Due date')" :value="$invoice->due_date" required />
                <x-forms.select name="subscription_id" :label="__('Related subscription')" :options="$subscriptions" :value="$invoice->subscription_id" :placeholder="__('None')" class="sm:col-span-2" />
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('Line items') }}</h2>
                <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add line') }}</button>
            </div>
            @error('items') <p class="input-error px-6 pt-3">{{ $message }}</p> @enderror
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid gap-3 p-4 sm:px-6 lg:grid-cols-12 lg:items-end">
                        <div class="lg:col-span-3">
                            <label class="label text-xs">{{ __('From catalog') }}</label>
                            <select class="input" @change="fromPlan(index, $event)">
                                <option value="">{{ __('Custom line') }}</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" data-product="{{ $plan->product_id }}" data-price="{{ $plan->price }}" data-description="{{ $plan->fullName() }}">{{ $plan->fullName() }} ({{ $plan->currency?->format($plan->price) }})</option>
                                @endforeach
                            </select>
                            <input type="hidden" :name="`items[${index}][plan_id]`" x-model="item.plan_id">
                            <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                        </div>
                        <div class="lg:col-span-4">
                            <label class="label text-xs">{{ __('Description') }}</label>
                            <input type="text" class="input" :name="`items[${index}][description]`" x-model="item.description" required>
                        </div>
                        <div class="grid grid-cols-3 gap-3 lg:col-span-4">
                            <div>
                                <label class="label text-xs">{{ __('Qty') }}</label>
                                <input type="number" step="0.01" min="0.01" class="input" :name="`items[${index}][quantity]`" x-model="item.quantity" required>
                            </div>
                            <div>
                                <label class="label text-xs">{{ __('Price') }}</label>
                                <input type="number" step="0.01" min="0" class="input" :name="`items[${index}][unit_price]`" x-model="item.unit_price" required>
                            </div>
                            <div>
                                <label class="label text-xs">{{ __('Disc. %') }}</label>
                                <input type="number" step="0.01" min="0" max="100" class="input" :name="`items[${index}][discount]`" x-model="item.discount">
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 lg:col-span-1 lg:flex-col lg:items-end">
                            <span class="text-sm font-semibold" x-text="money(lineTotal(item))"></span>
                            <button type="button" class="btn-icon h-8 w-8 hover:text-red-600" @click="remove(index)" title="{{ __('Remove') }}"><x-icon name="trash" class="h-4 w-4" /></button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex flex-col gap-4 border-t border-gray-200 p-4 dark:border-gray-800 sm:flex-row sm:justify-end sm:px-6">
                <dl class="w-full space-y-1 text-sm sm:w-72">
                    <div class="dl-row"><dt>{{ __('Subtotal') }}</dt><dd x-text="money(subtotal)"></dd></div>
                    <div class="dl-row"><dt>{{ __('Discounts') }}</dt><dd x-text="'-' + money(discount)"></dd></div>
                    <div class="dl-row items-center">
                        <dt class="flex items-center gap-2">{{ __('Tax') }}
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate" x-model="taxRate" class="input w-20 py-1 text-xs"> %</dt>
                        <dd x-text="money(tax)"></dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold dark:border-gray-800"><span>{{ __('Total') }}</span><span x-text="money(total)"></span></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-forms.textarea name="notes" :label="__('Notes for the customer')" :value="$invoice->notes" />
                <x-forms.textarea name="terms" :label="__('Terms & conditions')" :value="$invoice->terms" />
            </div>
        </div>

        <x-custom-fields :model="$invoice" />

        <div class="flex flex-wrap items-center justify-end gap-3">
            @unless ($invoice->exists)
                <x-forms.checkbox name="send_email" :label="__('E-mail the invoice to the customer')" />
            @endunless
            <a href="{{ url()->previous() }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save invoice') }}</button>
        </div>
    </form>
</x-app-layout>
