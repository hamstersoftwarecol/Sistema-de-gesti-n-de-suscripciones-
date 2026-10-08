<x-portal-layout :title="__('My subscriptions')">
    <x-page-header :title="__('My subscriptions')" />
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($subscriptions as $subscription)
            <a href="{{ route('portal.subscriptions.show', $subscription) }}" class="card block p-5 transition hover:ring-primary-300 dark:hover:ring-primary-700">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $subscription->plan?->fullName() }}</p>
                        <p class="text-xs text-gray-500">{{ $subscription->reference }} · {{ $subscription->plan?->cycleLabel() }}</p>
                    </div>
                    <x-status :value="$subscription->status" />
                </div>
                <p class="mt-3 text-2xl font-bold">{{ $subscription->currency?->format($subscription->periodAmount()) }}</p>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($subscription->isLive())
                        {{ $subscription->auto_renew ? __('Renews on :date', ['date' => fdate($subscription->next_billing_date)]) : __('Ends on :date', ['date' => fdate($subscription->ends_at ?? $subscription->current_period_end)]) }}
                    @else
                        {{ __('Ended on :date', ['date' => fdate($subscription->ends_at)]) }}
                    @endif
                </p>
            </a>
        @empty
            <div class="card sm:col-span-2"><x-empty :message="__('You have no subscriptions.')" icon="refresh" /></div>
        @endforelse
    </div>
</x-portal-layout>
