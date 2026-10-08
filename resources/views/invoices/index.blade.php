<x-app-layout :title="__('Invoices')">
    <x-page-header :title="__('Invoices')" :subtitle="$customer ? __('Customer: :name', ['name' => $customer->displayName()]) : __(':count records', ['count' => $invoices->total()])">
        <x-slot:actions>
            @if (auth()->user()->isStaff())
                <a href="{{ route('invoices.create', ['customer_id' => $customer?->id]) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New invoice') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat :label="__('Invoiced')" :value="money($summary['total'])" icon="document" />
        <x-stat :label="__('Collected')" :value="money($summary['paid'])" icon="banknotes" color="green" />
        <x-stat :label="__('Outstanding')" :value="money($summary['outstanding'])" icon="clock" color="amber" />
        <x-stat :label="__('Overdue')" :value="$summary['overdue']" icon="warning" color="red" />
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            @if ($customer) <input type="hidden" name="customer_id" value="{{ $customer->id }}"> @endif
            <div class="min-w-[12rem] flex-1">
                <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Number or customer') }}">
            </div>
            <select name="status" class="input w-auto">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\Invoice::statusOptions() as $value => $label)
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

        @if ($invoices->isEmpty())
            <x-empty :message="__('No invoices found.')" icon="document" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Due date') }}</th>
                            <th class="text-right">{{ __('Total') }}</th>
                            <th class="text-right">{{ __('Balance') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a>
                                    @if ($invoice->subscription)
                                        <p class="text-xs text-gray-500">{{ $invoice->subscription->reference }}</p>
                                    @endif
                                </td>
                                <td>{{ $invoice->customer?->displayName() }}</td>
                                <td>{{ fdate($invoice->issue_date) }}</td>
                                <td @class(['text-red-600 dark:text-red-400' => $invoice->status === 'overdue'])>{{ fdate($invoice->due_date) }}</td>
                                <td class="text-right font-medium">{{ $invoice->format($invoice->total) }}</td>
                                <td class="text-right">{{ $invoice->format($invoice->balance()) }}</td>
                                <td><x-status :value="$invoice->status" type="invoice" /></td>
                                <td class="text-right">
                                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn-icon h-8 w-8" title="PDF"><x-icon name="download" class="h-4 w-4" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $invoices->links() }}</div>
        @endif
    </div>
</x-app-layout>
