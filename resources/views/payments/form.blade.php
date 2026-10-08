<x-app-layout :title="$payment->exists ? __('Edit payment') : __('Record payment')">
    <x-page-header :title="$payment->exists ? __('Edit payment :reference', ['reference' => $payment->reference]) : __('Record payment')"
                   :back="$payment->exists ? route('payments.show', $payment) : route('payments.index')" />

    @php
        $invoiceData = $invoices->mapWithKeys(fn ($i) => [$i->id => ['customer' => $i->customer_id, 'currency' => $i->currency_id, 'balance' => $i->balance()]]);
    @endphp

    <form method="POST" action="{{ $payment->exists ? route('payments.update', $payment) : route('payments.store') }}"
          x-data="{ invoices: @js($invoiceData), invoiceId: @js((string) old('invoice_id', $payment->invoice_id)) }"
          class="card max-w-3xl">
        @csrf
        @if ($payment->exists) @method('PUT') @endif
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="invoice_id">{{ __('Invoice') }}</label>
                <select id="invoice_id" name="invoice_id" class="input" x-model="invoiceId"
                        @change="const i = invoices[invoiceId]; if (i) { $refs.amount.value = i.balance; $refs.currency.value = i.currency; $refs.customer.value = i.customer; }">
                    <option value="">{{ __('Payment without invoice (on account)') }}</option>
                    @foreach ($invoices as $invoice)
                        <option value="{{ $invoice->id }}">{{ $invoice->number }} — {{ $invoice->customer?->name }} — {{ __('Balance') }} {{ $invoice->format($invoice->balance()) }}</option>
                    @endforeach
                    @if ($payment->invoice_id && ! $invoices->contains('id', $payment->invoice_id))
                        <option value="{{ $payment->invoice_id }}">{{ $payment->invoice?->number }}</option>
                    @endif
                </select>
                @error('invoice_id') <p class="input-error">{{ $message }}</p> @enderror
            </div>
            <div x-show="!invoiceId" class="sm:col-span-2">
                <x-forms.select name="customer_id" :label="__('Customer')" :options="$customers" :value="$payment->customer_id" :placeholder="__('Select a customer...')" x-ref="customer" />
            </div>
            <div>
                <label class="label" for="amount">{{ __('Amount') }} <span class="text-red-500">*</span></label>
                <input id="amount" x-ref="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $payment->amount) }}" class="input" required>
                @error('amount') <p class="input-error">{{ $message }}</p> @enderror
            </div>
            <x-forms.select name="currency_id" :label="__('Currency')" :options="$currencies" :value="$payment->currency_id" required x-ref="currency" />
            <x-forms.select name="method" :label="__('Payment method')" :options="\App\Models\Payment::methodOptions()" :value="$payment->method" required />
            <x-forms.select name="status" :label="__('Status')" :options="\App\Models\Payment::statusOptions()" :value="$payment->status" required />
            <x-forms.input name="paid_at" type="date" :label="__('Payment date')" :value="$payment->paid_at" required />
            <x-forms.input name="transaction_id" :label="__('Transaction ID / reference')" :value="$payment->transaction_id" />
            <x-forms.textarea name="notes" :label="__('Notes')" :value="$payment->notes" class="sm:col-span-2" />
            @unless ($payment->exists)
                <x-forms.checkbox name="send_receipt" :label="__('E-mail a receipt to the customer')" class="sm:col-span-2" />
            @endunless
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-800">
            <a href="{{ url()->previous() }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save payment') }}</button>
        </div>
    </form>
</x-app-layout>
