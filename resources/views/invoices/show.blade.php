<x-app-layout :title="$invoice->number">
    @php
        $isStaff = auth()->user()->isStaff();
        $company = [
            'name' => setting('company_name', config('app.name')),
            'address' => setting('company_address'),
            'email' => setting('company_email'),
            'phone' => setting('company_phone'),
            'tax_id' => setting('company_tax_id'),
        ];
    @endphp

    <x-page-header :title="__('Invoice :number', ['number' => $invoice->number])" :back="route('invoices.index')">
        <x-slot:subtitle>{{ $invoice->customer?->displayName() }}</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> PDF</a>
            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn-secondary"><x-icon name="printer" class="h-4 w-4" /> {{ __('Print') }}</a>
            @if ($isStaff)
                <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                    @csrf
                    <button class="btn-secondary"><x-icon name="send" class="h-4 w-4" /> {{ __('Send by e-mail') }}</button>
                </form>
                @if ($invoice->isOpen())
                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn-success"><x-icon name="banknotes" class="h-4 w-4" /> {{ __('Record payment') }}</a>
                @endif
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger"><button class="btn-secondary px-2.5" aria-label="{{ __('More') }}"><x-icon name="dots" class="h-4 w-4" /></button></x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('invoices.edit', $invoice)">{{ __('Edit') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}">@csrf
                            <button class="block w-full px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('Duplicate') }}</button>
                        </form>
                        @if ($invoice->status !== 'cancelled')
                            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm(@js(__('Cancel this invoice?')))">@csrf
                                <button class="block w-full px-4 py-2 text-start text-sm text-amber-600 hover:bg-gray-100 dark:hover:bg-gray-800">{{ __('Cancel invoice') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm(@js(__('Are you sure? This action cannot be undone.')))">@csrf @method('DELETE')
                            <button class="block w-full px-4 py-2 text-start text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-800">{{ __('Delete') }}</button>
                        </form>
                    </x-slot>
                </x-dropdown>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-6 border-b border-gray-200 p-6 dark:border-gray-800">
                <div class="flex items-start gap-3">
                    <x-application-logo class="h-12 w-12 shrink-0" />
                    <div class="text-sm">
                        <p class="text-base font-bold">{{ $company['name'] }}</p>
                        @foreach (['address', 'email', 'phone'] as $field)
                            @if ($company[$field]) <p class="text-gray-500">{{ $company[$field] }}</p> @endif
                        @endforeach
                        @if ($company['tax_id']) <p class="text-gray-500">{{ __('Tax ID') }}: {{ $company['tax_id'] }}</p> @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400">{{ __('Invoice') }}</p>
                    <p class="font-mono text-sm">{{ $invoice->number }}</p>
                    <div class="mt-2"><x-status :value="$invoice->status" type="invoice" /></div>
                </div>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <div class="text-sm">
                    <p class="mb-1 text-xs font-semibold uppercase text-gray-500">{{ __('Bill to') }}</p>
                    <p class="font-semibold">{{ $invoice->customer?->displayName() }}</p>
                    @if ($invoice->customer?->tax_id) <p class="text-gray-500">{{ __('Tax ID') }}: {{ $invoice->customer->tax_id }}</p> @endif
                    <p class="text-gray-500">{{ collect([$invoice->customer?->address, $invoice->customer?->city, $invoice->customer?->country])->filter()->implode(', ') }}</p>
                    <p class="text-gray-500">{{ $invoice->customer?->email }}</p>
                </div>
                <dl class="text-sm sm:text-right">
                    <div><dt class="inline text-gray-500">{{ __('Issue date') }}:</dt> <dd class="inline font-medium">{{ fdate($invoice->issue_date) }}</dd></div>
                    <div><dt class="inline text-gray-500">{{ __('Due date') }}:</dt> <dd class="inline font-medium">{{ fdate($invoice->due_date) }}</dd></div>
                    @if ($invoice->subscription)
                        <div><dt class="inline text-gray-500">{{ __('Subscription') }}:</dt> <dd class="inline"><a href="{{ route('subscriptions.show', $invoice->subscription) }}" class="link">{{ $invoice->subscription->reference }}</a></dd></div>
                    @endif
                    <div><dt class="inline text-gray-500">{{ __('Currency') }}:</dt> <dd class="inline font-medium">{{ $invoice->currency?->code }}</dd></div>
                </dl>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Description') }}</th><th class="text-right">{{ __('Qty') }}</th><th class="text-right">{{ __('Price') }}</th><th class="text-right">{{ __('Disc.') }}</th><th class="text-right">{{ __('Total') }}</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td class="whitespace-normal">{{ $item->description }}</td>
                                <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                <td class="text-right">{{ $invoice->format($item->unit_price) }}</td>
                                <td class="text-right">{{ (float) $item->discount ? (float) $item->discount.'%' : '—' }}</td>
                                <td class="text-right font-medium">{{ $invoice->format($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-gray-200 p-6 dark:border-gray-800">
                <dl class="w-full max-w-xs space-y-1 text-sm">
                    <div class="dl-row"><dt>{{ __('Subtotal') }}</dt><dd>{{ $invoice->format($invoice->subtotal) }}</dd></div>
                    @if ((float) $invoice->discount_total)
                        <div class="dl-row"><dt>{{ __('Discounts') }}</dt><dd>-{{ $invoice->format($invoice->discount_total) }}</dd></div>
                    @endif
                    <div class="dl-row"><dt>{{ __('Tax') }} ({{ (float) $invoice->tax_rate }}%)</dt><dd>{{ $invoice->format($invoice->tax_total) }}</dd></div>
                    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold dark:border-gray-800"><span>{{ __('Total') }}</span><span>{{ $invoice->format($invoice->total) }}</span></div>
                    <div class="dl-row"><dt>{{ __('Paid') }}</dt><dd class="text-emerald-600">{{ $invoice->format($invoice->amount_paid) }}</dd></div>
                    <div class="flex justify-between text-base font-bold"><span>{{ __('Balance due') }}</span><span @class(['text-red-600' => $invoice->balance() > 0])>{{ $invoice->format($invoice->balance()) }}</span></div>
                </dl>
            </div>

            @if ($invoice->notes || $invoice->terms)
                <div class="grid gap-4 border-t border-gray-200 p-6 text-sm dark:border-gray-800 sm:grid-cols-2">
                    @if ($invoice->notes)
                        <div><p class="mb-1 text-xs font-semibold uppercase text-gray-500">{{ __('Notes') }}</p><p class="text-gray-600 dark:text-gray-400">{!! nl2br(e($invoice->notes)) !!}</p></div>
                    @endif
                    @if ($invoice->terms)
                        <div><p class="mb-1 text-xs font-semibold uppercase text-gray-500">{{ __('Terms & conditions') }}</p><p class="text-gray-600 dark:text-gray-400">{!! nl2br(e($invoice->terms)) !!}</p></div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Payments') }}</h2></div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div>
                                <a href="{{ route('payments.show', $payment) }}" class="link text-sm">{{ $payment->reference }}</a>
                                <p class="text-xs text-gray-500">{{ fdate($payment->paid_at) }} · {{ \App\Models\Payment::methodOptions()[$payment->method] ?? $payment->method }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ $payment->format() }}</p>
                                <x-status :value="$payment->status" type="payment" />
                            </div>
                        </li>
                    @empty
                        <li><x-empty :message="__('No payments recorded.')" icon="banknotes" /></li>
                    @endforelse
                </ul>
            </div>

            <x-custom-fields-display :model="$invoice" class="card px-4 py-2 sm:px-6" />

            <div class="card card-body text-sm text-gray-500">
                @if ($invoice->sent_at) <p>{{ __('Sent on :date', ['date' => fdate($invoice->sent_at, true)]) }}</p> @endif
                @if ($invoice->paid_at) <p>{{ __('Paid on :date', ['date' => fdate($invoice->paid_at, true)]) }}</p> @endif
                <p>{{ __('Created on :date', ['date' => fdate($invoice->created_at, true)]) }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
