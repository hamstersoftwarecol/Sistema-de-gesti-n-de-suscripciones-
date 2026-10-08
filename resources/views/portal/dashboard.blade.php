<x-portal-layout :title="__('Customer portal')">
    <x-page-header :title="__('Hello, :name', ['name' => \Illuminate\Support\Str::of($customer->name)->before(' ')])" :subtitle="$customer->company ?? __('Welcome to your customer portal')" />

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
        <x-stat :label="__('Active subscriptions')" :value="$subscriptions->count()" icon="refresh" />
        <x-stat :label="__('Pending invoices')" :value="$openInvoices->count()" icon="document" color="amber" />
        <x-stat :label="__('Balance due')" :value="money($balance, $customer->currency)" icon="banknotes" :color="$balance > 0 ? 'red' : 'green'" class="col-span-2 lg:col-span-1" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('My subscriptions') }}</h2><a href="{{ route('portal.subscriptions') }}" class="link text-sm">{{ __('View all') }}</a></div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($subscriptions as $subscription)
                    <li>
                        <a href="{{ route('portal.subscriptions.show', $subscription) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $subscription->plan?->fullName() }}</p>
                                <p class="text-xs text-gray-500">{{ $subscription->auto_renew ? __('Renews on :date', ['date' => fdate($subscription->next_billing_date)]) : __('Ends on :date', ['date' => fdate($subscription->ends_at ?? $subscription->current_period_end)]) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ $subscription->currency?->format($subscription->periodAmount()) }}</p>
                                <x-status :value="$subscription->status" />
                            </div>
                        </a>
                    </li>
                @empty
                    <li><x-empty :message="__('You have no active subscriptions.')" icon="refresh" /></li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Pending invoices') }}</h2><a href="{{ route('portal.invoices') }}" class="link text-sm">{{ __('View all') }}</a></div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($openInvoices as $invoice)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div>
                            <a href="{{ route('portal.invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a>
                            <p class="text-xs text-gray-500">{{ __('Due :date', ['date' => fdate($invoice->due_date)]) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold">{{ $invoice->format($invoice->balance()) }}</p>
                            <x-status :value="$invoice->status" type="invoice" />
                        </div>
                    </li>
                @empty
                    <li><x-empty :message="__('You are up to date. Thank you!')" icon="check-circle" /></li>
                @endforelse
            </ul>
            @if ($openInvoices->isNotEmpty() && setting('invoice_notes'))
                <div class="border-t border-gray-200 p-4 text-xs text-gray-500 dark:border-gray-800 sm:px-6">
                    <p class="mb-1 font-semibold uppercase">{{ __('How to pay') }}</p>
                    {!! nl2br(e(setting('invoice_notes'))) !!}
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header"><h2 class="card-title">{{ __('Latest payments') }}</h2><a href="{{ route('portal.payments') }}" class="link text-sm">{{ __('View all') }}</a></div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($recentPayments as $payment)
                <li class="flex items-center justify-between px-4 py-3 text-sm sm:px-6">
                    <span>{{ fdate($payment->paid_at) }} · {{ $payment->invoice?->number ?? '—' }}</span>
                    <span class="font-semibold text-emerald-600">{{ $payment->format() }}</span>
                </li>
            @empty
                <li><x-empty icon="banknotes" /></li>
            @endforelse
        </ul>
    </div>
</x-portal-layout>
