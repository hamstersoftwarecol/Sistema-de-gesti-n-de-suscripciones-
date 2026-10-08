<x-app-layout :title="$payment->reference">
    <x-page-header :title="__('Payment :reference', ['reference' => $payment->reference])" :back="route('payments.index')">
        <x-slot:actions>
            @if (auth()->user()->isStaff())
                <form method="POST" action="{{ route('payments.receipt', $payment) }}">@csrf
                    <button class="btn-secondary"><x-icon name="send" class="h-4 w-4" /> {{ __('Send receipt') }}</button>
                </form>
                <a href="{{ route('payments.edit', $payment) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</a>
                <x-delete-button :action="route('payments.destroy', $payment)" :label="__('Delete')" size="md" />
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="card max-w-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-800">
            <div>
                <p class="text-sm text-gray-500">{{ __('Amount') }}</p>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $payment->format() }}</p>
                @if ($payment->currency && ! $payment->currency->is_default)
                    <p class="text-xs text-gray-500">≈ {{ money($payment->baseAmount()) }}</p>
                @endif
            </div>
            <x-status :value="$payment->status" type="payment" />
        </div>
        <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
            <div class="dl-row"><dt>{{ __('Customer') }}</dt><dd><a href="{{ route('customers.show', $payment->customer_id) }}" class="link">{{ $payment->customer?->displayName() }}</a></dd></div>
            <div class="dl-row"><dt>{{ __('Invoice') }}</dt><dd>@if ($payment->invoice)<a href="{{ route('invoices.show', $payment->invoice) }}" class="link">{{ $payment->invoice->number }}</a>@else — @endif</dd></div>
            <div class="dl-row"><dt>{{ __('Payment date') }}</dt><dd>{{ fdate($payment->paid_at) }}</dd></div>
            <div class="dl-row"><dt>{{ __('Payment method') }}</dt><dd>{{ \App\Models\Payment::methodOptions()[$payment->method] ?? $payment->method }}</dd></div>
            <div class="dl-row"><dt>{{ __('Transaction ID / reference') }}</dt><dd>{{ $payment->transaction_id ?? '—' }}</dd></div>
            <div class="dl-row"><dt>{{ __('Recorded by') }}</dt><dd>{{ $payment->recorder?->name ?? '—' }}</dd></div>
            <div class="dl-row"><dt>{{ __('Created on') }}</dt><dd>{{ fdate($payment->created_at, true) }}</dd></div>
        </dl>
        @if ($payment->notes)
            <div class="border-t border-gray-200 p-6 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">{!! nl2br(e($payment->notes)) !!}</div>
        @endif
    </div>
</x-app-layout>
