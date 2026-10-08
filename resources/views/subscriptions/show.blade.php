<x-app-layout :title="$subscription->reference">
    @php
        $isStaff = auth()->user()->isStaff();
        $currency = $subscription->currency;
        $ended = in_array($subscription->status, ['cancelled', 'expired']);
    @endphp

    <x-page-header :title="$subscription->reference" :back="route('subscriptions.index')">
        <x-slot:subtitle>{{ $subscription->customer?->displayName() }}</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</a>
            @if ($isStaff)
                <form method="POST" action="{{ route('subscriptions.renew', $subscription) }}" onsubmit="return confirm(@js(__('Issue the invoice for the next period now?')))">
                    @csrf
                    <button class="btn-primary"><x-icon name="refresh" class="h-4 w-4" /> {{ $ended ? __('Reactivate & invoice') : __('Renew now') }}</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($subscription->cancelled_at && ! $ended)
        <div class="mb-4 flex items-start gap-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <p>{{ __('Cancellation scheduled: the subscription will end on :date.', ['date' => fdate($subscription->ends_at)]) }} @if ($subscription->cancel_reason) — “{{ $subscription->cancel_reason }}” @endif</p>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat :label="__('Status')" :value="\App\Models\Subscription::statusOptions()[$subscription->status]" icon="info" />
        <x-stat :label="__('Amount per period')" :value="$currency?->format($subscription->periodAmount())" icon="banknotes" color="green" :hint="$subscription->plan?->cycleLabel()" />
        <x-stat :label="__('MRR')" :value="money($subscription->monthlyAmountInBase())" icon="refresh" color="blue" />
        <x-stat :label="__('Next billing')" :value="fdate($subscription->next_billing_date)" icon="calendar" color="amber"
                :hint="$subscription->isLive() && $subscription->daysUntilRenewal() !== null ? trans_choice('in :count day|in :count days', max(0, $subscription->daysUntilRenewal()), ['count' => max(0, $subscription->daysUntilRenewal())]) : null" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Details') }}</h2><x-status :value="$subscription->status" /></div>
                <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
                    <div class="dl-row"><dt>{{ __('Customer') }}</dt><dd><a href="{{ route('customers.show', $subscription->customer_id) }}" class="link">{{ $subscription->customer?->name }}</a></dd></div>
                    <div class="dl-row"><dt>{{ __('Plan') }}</dt><dd>{{ $subscription->plan?->fullName() }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Unit price') }}</dt><dd>{{ $currency?->format($subscription->price) }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Quantity') }}</dt><dd>{{ $subscription->quantity }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Discount') }}</dt><dd>{{ (float) $subscription->discount }}%</dd></div>
                    <div class="dl-row"><dt>{{ __('Start date') }}</dt><dd>{{ fdate($subscription->start_date) }}</dd></div>
                    @if ($subscription->trial_ends_at)
                        <div class="dl-row"><dt>{{ __('Trial ends') }}</dt><dd>{{ fdate($subscription->trial_ends_at) }}</dd></div>
                    @endif
                    <div class="dl-row"><dt>{{ __('Current period') }}</dt><dd>{{ fdate($subscription->current_period_start) }} → {{ fdate($subscription->current_period_end) }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Auto-renew') }}</dt><dd>{{ $subscription->auto_renew ? __('Yes') : __('No') }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Seller') }}</dt><dd>{{ $subscription->seller?->name ?? '—' }}</dd></div>
                    @if ($subscription->ends_at)
                        <div class="dl-row"><dt>{{ __('Ends on') }}</dt><dd>{{ fdate($subscription->ends_at) }}</dd></div>
                    @endif
                </dl>
                <x-custom-fields-display :model="$subscription" class="border-t border-gray-100 px-4 py-2 dark:border-gray-800 sm:px-6" />
                @if ($subscription->notes)
                    <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400 sm:px-6">{!! nl2br(e($subscription->notes)) !!}</div>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Actions') }}</h2></div>
                <div class="card-body space-y-2">
                    @if ($subscription->isLive())
                        <form method="POST" action="{{ route('subscriptions.remind', $subscription) }}">
                            @csrf
                            <button class="btn-secondary w-full"><x-icon name="send" class="h-4 w-4" /> {{ __('Send renewal reminder') }}</button>
                        </form>
                        <form method="POST" action="{{ route('subscriptions.pause', $subscription) }}">
                            @csrf
                            <button class="btn-secondary w-full"><x-icon name="pause" class="h-4 w-4" /> {{ __('Pause subscription') }}</button>
                        </form>
                    @endif
                    @if ($subscription->status === 'paused' || ($subscription->cancelled_at && ! $ended))
                        <form method="POST" action="{{ route('subscriptions.resume', $subscription) }}">
                            @csrf
                            <button class="btn-success w-full"><x-icon name="play" class="h-4 w-4" /> {{ __('Resume subscription') }}</button>
                        </form>
                    @endif
                    @unless ($ended)
                        <button type="button" class="btn-danger w-full" x-data @click="$dispatch('open-modal', 'cancel-subscription')">
                            <x-icon name="x-circle" class="h-4 w-4" /> {{ __('Cancel subscription') }}
                        </button>
                    @endunless
                    @if ($isStaff)
                        <div class="pt-2">
                            <x-delete-button :action="route('subscriptions.destroy', $subscription)" :label="__('Delete subscription')" class="w-full" size="md" />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Invoices') }}</h2></div>
                @if ($subscription->invoices->isEmpty())
                    <x-empty :message="__('No invoices issued yet.')" icon="document" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due date') }}</th><th class="text-right">{{ __('Total') }}</th><th class="text-right">{{ __('Balance') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @foreach ($subscription->invoices as $invoice)
                                    <tr>
                                        <td><a href="{{ route('invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a></td>
                                        <td>{{ fdate($invoice->issue_date) }}</td>
                                        <td>{{ fdate($invoice->due_date) }}</td>
                                        <td class="text-right">{{ $invoice->format($invoice->total) }}</td>
                                        <td class="text-right">{{ $invoice->format($invoice->balance()) }}</td>
                                        <td><x-status :value="$invoice->status" type="invoice" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Renewal history') }}</h2></div>
                <div class="card-body">
                    @forelse ($subscription->renewals as $renewal)
                        <div class="relative flex gap-4 pb-6 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-[15px] top-8 h-full w-px bg-gray-200 dark:bg-gray-800"></span>
                            @endunless
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $renewal->is_automatic ? 'bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                <x-icon name="refresh" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1 text-sm">
                                <p class="font-medium">{{ fdate($renewal->period_start) }} → {{ fdate($renewal->period_end) }}</p>
                                <p class="text-gray-500">
                                    {{ $renewal->is_automatic ? __('Automatic renewal') : __('Manual renewal') }} · {{ $currency?->format($renewal->amount) }}
                                    @if ($renewal->invoice) · <a href="{{ route('invoices.show', $renewal->invoice) }}" class="link">{{ $renewal->invoice->number }}</a> @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <x-empty :message="__('No renewals yet.')" icon="clock" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <x-modal name="cancel-subscription" maxWidth="md">
        <form method="POST" action="{{ route('subscriptions.cancel', $subscription) }}" class="space-y-4 p-6">
            @csrf
            <h2 class="text-lg font-semibold">{{ __('Cancel subscription') }}</h2>
            <div class="space-y-2 text-sm">
                <label class="flex items-start gap-2">
                    <input type="radio" name="when" value="period_end" class="mt-1 text-primary-600" checked>
                    <span><span class="font-medium">{{ __('At the end of the current period') }}</span><br><span class="text-gray-500">{{ __('The customer keeps the service until :date.', ['date' => fdate($subscription->current_period_end)]) }}</span></span>
                </label>
                <label class="flex items-start gap-2">
                    <input type="radio" name="when" value="now" class="mt-1 text-primary-600">
                    <span><span class="font-medium">{{ __('Immediately') }}</span><br><span class="text-gray-500">{{ __('The subscription stops now and will not be billed again.') }}</span></span>
                </label>
            </div>
            <x-forms.input name="reason" :label="__('Reason (optional)')" />
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Back') }}</button>
                <button class="btn-danger">{{ __('Confirm cancellation') }}</button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
