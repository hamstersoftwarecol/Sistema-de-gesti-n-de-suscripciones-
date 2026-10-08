<x-app-layout :title="__('Subscriptions')">
    <x-page-header :title="__('Subscriptions')" :subtitle="__(':count records', ['count' => $subscriptions->total()])">
        <x-slot:actions>
            <a href="{{ route('subscriptions.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New subscription') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('subscriptions.index') }}" @class(['btn-sm', 'btn-primary' => ! request('status'), 'btn-secondary' => request('status')])>{{ __('All') }} <span class="opacity-70">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\Subscription::statusOptions() as $value => $label)
            <a href="{{ route('subscriptions.index', ['status' => $value]) }}" @class(['btn-sm', 'btn-primary' => request('status') === $value, 'btn-secondary' => request('status') !== $value])>
                {{ $label }} <span class="opacity-70">{{ $counts[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="min-w-[12rem] flex-1">
                <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Reference or customer') }}">
            </div>
            <select name="plan_id" class="input w-auto max-w-xs">
                <option value="">{{ __('All plans') }}</option>
                @foreach ($plans as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('plan_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="renewal" class="input w-auto">
                <option value="">{{ __('Any renewal date') }}</option>
                <option value="week" @selected(request('renewal') === 'week')>{{ __('Renews in 7 days') }}</option>
                <option value="month" @selected(request('renewal') === 'month')>{{ __('Renews in 30 days') }}</option>
            </select>
            @if (auth()->user()->isStaff())
                <select name="seller_id" class="input w-auto">
                    <option value="">{{ __('All sellers') }}</option>
                    @foreach ($sellers as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('seller_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            @endif
            <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
        </form>

        @if ($subscriptions->isEmpty())
            <x-empty :message="__('No subscriptions found.')" icon="refresh" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th class="text-right">{{ __('Amount') }}</th>
                            <th>{{ __('Next billing') }}</th>
                            <th>{{ __('Auto-renew') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td><a href="{{ route('subscriptions.show', $subscription) }}" class="link">{{ $subscription->reference }}</a></td>
                                <td>
                                    <a href="{{ route('customers.show', $subscription->customer_id) }}" class="hover:underline">{{ $subscription->customer?->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $subscription->customer?->company }}</p>
                                </td>
                                <td>
                                    {{ $subscription->plan?->fullName() }}
                                    <p class="text-xs text-gray-500">{{ $subscription->plan?->cycleLabel() }}{{ $subscription->quantity > 1 ? ' · ×'.$subscription->quantity : '' }}</p>
                                </td>
                                <td class="text-right font-medium">{{ $subscription->currency?->format($subscription->periodAmount()) }}</td>
                                <td>
                                    {{ fdate($subscription->next_billing_date) }}
                                    @if ($subscription->isLive() && $subscription->daysUntilRenewal() !== null && $subscription->daysUntilRenewal() <= 7)
                                        <p class="text-xs text-amber-600">{{ trans_choice('in :count day|in :count days', $subscription->daysUntilRenewal(), ['count' => $subscription->daysUntilRenewal()]) }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if ($subscription->auto_renew)
                                        <x-icon name="refresh" class="h-4 w-4 text-emerald-500" />
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td><x-status :value="$subscription->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</x-app-layout>
