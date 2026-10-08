<x-portal-layout :title="$subscription->reference">
    <x-page-header :title="$subscription->plan?->fullName()" :subtitle="$subscription->reference" :back="route('portal.subscriptions')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Details') }}</h2><x-status :value="$subscription->status" /></div>
                <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
                    <div class="dl-row"><dt>{{ __('Amount per period') }}</dt><dd>{{ $subscription->currency?->format($subscription->periodAmount()) }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Billing cycle') }}</dt><dd>{{ $subscription->plan?->cycleLabel() }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Quantity') }}</dt><dd>{{ $subscription->quantity }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Start date') }}</dt><dd>{{ fdate($subscription->start_date) }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Current period') }}</dt><dd>{{ fdate($subscription->current_period_start) }} → {{ fdate($subscription->current_period_end) }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Next billing') }}</dt><dd>{{ fdate($subscription->next_billing_date) }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Auto-renew') }}</dt><dd>{{ $subscription->auto_renew ? __('Yes') : __('No') }}</dd></div>
                </dl>
                @if ($subscription->plan?->featureList())
                    <ul class="space-y-1 border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800 sm:px-6">
                        @foreach ($subscription->plan->featureList() as $feature)
                            <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-500" /> {{ $feature }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($subscription->isLive())
                <div class="card card-body space-y-3">
                    <form method="POST" action="{{ route('portal.subscriptions.auto-renew', $subscription) }}">
                        @csrf
                        <button class="btn-secondary w-full">
                            <x-icon name="refresh" class="h-4 w-4" />
                            {{ $subscription->auto_renew ? __('Turn off automatic renewal') : __('Turn on automatic renewal') }}
                        </button>
                    </form>
                    @if ($subscription->auto_renew)
                        <button type="button" class="btn-ghost w-full text-red-600" x-data @click="$dispatch('open-modal', 'portal-cancel')">{{ __('Cancel subscription') }}</button>
                    @endif
                </div>
            @endif
        </div>

        <div class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Invoices') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th class="text-right">{{ __('Total') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($subscription->invoices as $invoice)
                            <tr>
                                <td><a href="{{ route('portal.invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a></td>
                                <td>{{ fdate($invoice->issue_date) }}</td>
                                <td class="text-right">{{ $invoice->format($invoice->total) }}</td>
                                <td><x-status :value="$invoice->status" type="invoice" /></td>
                                <td class="text-right"><a href="{{ route('portal.invoices.pdf', $invoice) }}" class="btn-icon h-8 w-8"><x-icon name="download" class="h-4 w-4" /></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="document" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-modal name="portal-cancel" maxWidth="md">
        <form method="POST" action="{{ route('portal.subscriptions.cancel', $subscription) }}" class="space-y-4 p-6">
            @csrf
            <h2 class="text-lg font-semibold">{{ __('Cancel subscription') }}</h2>
            <p class="text-sm text-gray-500">{{ __('Your subscription will remain active until :date and will not be renewed.', ['date' => fdate($subscription->current_period_end)]) }}</p>
            <x-forms.textarea name="reason" :label="__('Tell us why (optional)')" />
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Back') }}</button>
                <button class="btn-danger">{{ __('Confirm cancellation') }}</button>
            </div>
        </form>
    </x-modal>
</x-portal-layout>
