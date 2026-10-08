<x-portal-layout :title="__('My payments')">
    <x-page-header :title="__('My payments')" />
    <div class="card">
        @if ($payments->isEmpty())
            <x-empty icon="banknotes" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Method') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ fdate($payment->paid_at) }}</td>
                                <td>{{ $payment->reference }}</td>
                                <td>@if ($payment->invoice)<a href="{{ route('portal.invoices.show', $payment->invoice) }}" class="link">{{ $payment->invoice->number }}</a>@else — @endif</td>
                                <td>{{ \App\Models\Payment::methodOptions()[$payment->method] ?? $payment->method }}</td>
                                <td class="text-right font-medium">{{ $payment->format() }}</td>
                                <td><x-status :value="$payment->status" type="payment" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $payments->links() }}</div>
        @endif
    </div>
</x-portal-layout>
