<x-app-layout :title="__('Payments')">
    <x-page-header :title="__('Payments')" :subtitle="__('Payment tracking')">
        <x-slot:actions>
            @if (auth()->user()->isStaff())
                <a href="{{ route('payments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Record payment') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <x-stat :label="__('Collected (filtered)')" :value="money($total)" icon="banknotes" color="green" :hint="trans_choice(':count payment|:count payments', $count, ['count' => $count])" />
        <div class="card p-4 sm:p-5 lg:col-span-2">
            <p class="mb-3 text-sm font-medium text-gray-500">{{ __('By payment method') }}</p>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @forelse ($byMethod as $method => $amount)
                    <div>
                        <p class="text-xs text-gray-500">{{ \App\Models\Payment::methodOptions()[$method] ?? $method }}</p>
                        <p class="font-semibold">{{ money($amount) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">—</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <div class="min-w-[12rem] flex-1">
                <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Reference, transaction, customer or invoice') }}">
            </div>
            <select name="method" class="input w-auto">
                <option value="">{{ __('All methods') }}</option>
                @foreach (\App\Models\Payment::methodOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="input w-auto">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\Payment::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" class="input w-auto" aria-label="{{ __('From') }}">
                <span class="text-gray-400">—</span>
                <input type="date" name="to" value="{{ request('to') }}" class="input w-auto" aria-label="{{ __('To') }}">
            </div>
            <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
        </form>

        @if ($payments->isEmpty())
            <x-empty :message="__('No payments found.')" icon="banknotes" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Date') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Method') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td><a href="{{ route('payments.show', $payment) }}" class="link">{{ $payment->reference }}</a>
                                    @if ($payment->transaction_id) <p class="text-xs text-gray-500">{{ $payment->transaction_id }}</p> @endif</td>
                                <td>{{ fdate($payment->paid_at) }}</td>
                                <td>{{ $payment->customer?->displayName() }}</td>
                                <td>@if ($payment->invoice) <a href="{{ route('invoices.show', $payment->invoice) }}" class="hover:underline">{{ $payment->invoice->number }}</a> @else — @endif</td>
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
</x-app-layout>
