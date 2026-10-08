<x-portal-layout :title="$invoice->number">
    <x-page-header :title="__('Invoice :number', ['number' => $invoice->number])" :back="route('portal.invoices')">
        <x-slot:actions>
            <a href="{{ route('portal.invoices.pdf', $invoice) }}" class="btn-primary"><x-icon name="download" class="h-4 w-4" /> {{ __('Download PDF') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="flex flex-wrap justify-between gap-4 border-b border-gray-200 p-6 text-sm dark:border-gray-800">
                <div>
                    <p class="text-gray-500">{{ __('Issue date') }}: <span class="font-medium text-gray-900 dark:text-gray-100">{{ fdate($invoice->issue_date) }}</span></p>
                    <p class="text-gray-500">{{ __('Due date') }}: <span class="font-medium text-gray-900 dark:text-gray-100">{{ fdate($invoice->due_date) }}</span></p>
                </div>
                <x-status :value="$invoice->status" type="invoice" />
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Description') }}</th><th class="text-right">{{ __('Qty') }}</th><th class="text-right">{{ __('Price') }}</th><th class="text-right">{{ __('Total') }}</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td class="whitespace-normal">{{ $item->description }}</td>
                                <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                <td class="text-right">{{ $invoice->format($item->unit_price) }}</td>
                                <td class="text-right">{{ $invoice->format($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end border-t border-gray-200 p-6 dark:border-gray-800">
                <dl class="w-full max-w-xs space-y-1 text-sm">
                    <div class="dl-row"><dt>{{ __('Subtotal') }}</dt><dd>{{ $invoice->format($invoice->subtotal) }}</dd></div>
                    @if ((float) $invoice->discount_total)<div class="dl-row"><dt>{{ __('Discounts') }}</dt><dd>-{{ $invoice->format($invoice->discount_total) }}</dd></div>@endif
                    <div class="dl-row"><dt>{{ __('Tax') }} ({{ (float) $invoice->tax_rate }}%)</dt><dd>{{ $invoice->format($invoice->tax_total) }}</dd></div>
                    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold dark:border-gray-800"><span>{{ __('Total') }}</span><span>{{ $invoice->format($invoice->total) }}</span></div>
                    <div class="flex justify-between text-base font-bold"><span>{{ __('Balance due') }}</span><span>{{ $invoice->format($invoice->balance()) }}</span></div>
                </dl>
            </div>
        </div>
        <div class="space-y-6">
            @if ($invoice->balance() > 0 && ($invoice->notes || setting('invoice_notes')))
                <div class="card card-body text-sm">
                    <p class="mb-2 font-semibold">{{ __('How to pay') }}</p>
                    <p class="text-gray-600 dark:text-gray-400">{!! nl2br(e($invoice->notes ?: setting('invoice_notes'))) !!}</p>
                </div>
            @endif
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Payments') }}</h2></div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex justify-between px-4 py-3 text-sm sm:px-6"><span>{{ fdate($payment->paid_at) }}</span><span class="font-semibold">{{ $payment->format() }}</span></li>
                    @empty
                        <li><x-empty :message="__('No payments recorded.')" icon="banknotes" /></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-portal-layout>
