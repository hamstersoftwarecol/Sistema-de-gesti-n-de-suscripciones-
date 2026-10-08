<x-app-layout :title="__('Currencies')">
    @php $base = $currencies->firstWhere('is_default', true); @endphp
    <x-page-header :title="__('Currencies & exchange rates')" :subtitle="__('Base currency: :code. Rates are expressed as units per 1 :code.', ['code' => $base?->code])" />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('currencies.store') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Add currency') }}</h2></div>
            <div class="card-body grid grid-cols-2 gap-4">
                <x-forms.input name="code" :label="__('ISO code')" maxlength="3" required placeholder="EUR" />
                <x-forms.input name="symbol" :label="__('Symbol')" required placeholder="€" />
                <x-forms.input name="name" :label="__('Name')" required class="col-span-2" />
                <x-forms.input name="exchange_rate" type="number" step="0.000001" min="0.000001" :label="__('Exchange rate')" value="1" required />
                <x-forms.input name="decimal_places" type="number" min="0" max="4" :label="__('Decimals')" value="2" required />
                <x-forms.checkbox name="is_active" :label="__('Active')" :checked="true" class="col-span-2" />
                <button class="btn-primary col-span-2">{{ __('Add currency') }}</button>
            </div>
        </form>

        <div class="card lg:col-span-2">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Currency') }}</th><th>{{ __('Rate') }}</th><th>{{ __('Example') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($currencies as $currency)
                            <tr x-data="{ editing: false }">
                                <td>
                                    <p class="font-semibold">{{ $currency->code }} <span class="font-normal text-gray-500">{{ $currency->symbol }}</span>
                                        @if ($currency->is_default) <span class="badge ml-1 bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ __('Base') }}</span> @endif</p>
                                    <p class="text-xs text-gray-500">{{ $currency->name }}</p>
                                </td>
                                <td>{{ rtrim(rtrim(number_format((float) $currency->exchange_rate, 6), '0'), '.') }}</td>
                                <td>{{ $currency->format(1234.5) }}</td>
                                <td><x-status :value="(bool) $currency->is_active" type="bool" /></td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        @unless ($currency->is_default)
                                            <form method="POST" action="{{ route('currencies.default', $currency) }}" onsubmit="return confirm(@js(__('Make :code the base currency? All rates will be recalculated.', ['code' => $currency->code])))">
                                                @csrf <button class="btn-ghost btn-sm">{{ __('Make base') }}</button>
                                            </form>
                                        @endunless
                                        <button type="button" class="btn-icon h-8 w-8" @click="$dispatch('open-modal', 'currency-{{ $currency->id }}')"><x-icon name="pencil" class="h-4 w-4" /></button>
                                        @unless ($currency->is_default)
                                            <x-delete-button :action="route('currencies.destroy', $currency)" />
                                        @endunless
                                    </div>

                                    <x-modal :name="'currency-'.$currency->id" maxWidth="md">
                                        <form method="POST" action="{{ route('currencies.update', $currency) }}" class="grid grid-cols-2 gap-4 p-6 text-left">
                                            @csrf @method('PUT')
                                            <h2 class="col-span-2 text-lg font-semibold">{{ __('Edit currency') }}</h2>
                                            <x-forms.input name="code" :label="__('ISO code')" :value="$currency->code" :id="'c_code_'.$currency->id" maxlength="3" required />
                                            <x-forms.input name="symbol" :label="__('Symbol')" :value="$currency->symbol" :id="'c_symbol_'.$currency->id" required />
                                            <x-forms.input name="name" :label="__('Name')" :value="$currency->name" :id="'c_name_'.$currency->id" required class="col-span-2" />
                                            <x-forms.input name="exchange_rate" type="number" step="0.000001" min="0.000001" :label="__('Exchange rate')" :value="(float) $currency->exchange_rate" :id="'c_rate_'.$currency->id" required :readonly="$currency->is_default" />
                                            <x-forms.input name="decimal_places" type="number" min="0" max="4" :label="__('Decimals')" :value="$currency->decimal_places" :id="'c_dec_'.$currency->id" required />
                                            <x-forms.checkbox name="is_active" :label="__('Active')" :checked="$currency->is_active" :id="'c_active_'.$currency->id" class="col-span-2" />
                                            <div class="col-span-2 flex justify-end gap-2">
                                                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Cancel') }}</button>
                                                <button class="btn-primary">{{ __('Save') }}</button>
                                            </div>
                                        </form>
                                    </x-modal>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
