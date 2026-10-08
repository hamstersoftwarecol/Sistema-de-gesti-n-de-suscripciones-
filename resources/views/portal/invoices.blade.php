<x-portal-layout :title="__('My invoices')">
    <x-page-header :title="__('My invoices')" />
    <div class="card">
        @if ($invoices->isEmpty())
            <x-empty icon="document" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due date') }}</th><th class="text-right">{{ __('Total') }}</th><th class="text-right">{{ __('Balance') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td><a href="{{ route('portal.invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a></td>
                                <td>{{ fdate($invoice->issue_date) }}</td>
                                <td>{{ fdate($invoice->due_date) }}</td>
                                <td class="text-right">{{ $invoice->format($invoice->total) }}</td>
                                <td class="text-right">{{ $invoice->format($invoice->balance()) }}</td>
                                <td><x-status :value="$invoice->status" type="invoice" /></td>
                                <td class="text-right"><a href="{{ route('portal.invoices.pdf', $invoice) }}" class="btn-icon h-8 w-8" title="PDF"><x-icon name="download" class="h-4 w-4" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $invoices->links() }}</div>
        @endif
    </div>
</x-portal-layout>
