<x-app-layout :title="__('Edit subscription')">
    <x-page-header :title="__('Edit subscription')" :subtitle="$subscription->reference.' · '.$subscription->customer?->displayName()" :back="route('subscriptions.show', $subscription)" />

    <form method="POST" action="{{ route('subscriptions.update', $subscription) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-forms.select name="plan_id" :label="__('Plan')" required class="sm:col-span-2"
                                :options="$plans->mapWithKeys(fn ($p) => [$p->id => $p->fullName().' — '.$p->currency?->format($p->price).' / '.mb_strtolower($p->cycleLabel())])->all() + [$subscription->plan_id => $subscription->plan?->fullName()]"
                                :value="$subscription->plan_id" />
                <x-forms.select name="status" :label="__('Status')" :options="\App\Models\Subscription::statusOptions()" :value="$subscription->status" required />
                <x-forms.input name="price" type="number" step="0.01" min="0" :label="__('Price per period')" :value="$subscription->price" required />
                <x-forms.input name="quantity" type="number" min="1" :label="__('Quantity / seats')" :value="$subscription->quantity" required />
                <x-forms.input name="discount" type="number" step="0.01" min="0" max="100" :label="__('Discount (%)')" :value="$subscription->discount" />
                <x-forms.input name="current_period_end" type="date" :label="__('Current period ends')" :value="$subscription->current_period_end" />
                <x-forms.input name="next_billing_date" type="date" :label="__('Next billing date')" :value="$subscription->next_billing_date" />
                @if (auth()->user()->isStaff())
                    <x-forms.select name="seller_id" :label="__('Seller')" :options="$sellers" :value="$subscription->seller_id" :placeholder="__('No seller')" />
                @endif
                <x-forms.checkbox name="auto_renew" :label="__('Automatic renewal')" :checked="$subscription->auto_renew" class="sm:col-span-2 lg:col-span-3" />
                <x-forms.textarea name="notes" :label="__('Notes')" :value="$subscription->notes" class="sm:col-span-2 lg:col-span-3" />
            </div>
        </div>

        <x-custom-fields :model="$subscription" />

        <div class="flex justify-end gap-2">
            <a href="{{ route('subscriptions.show', $subscription) }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
