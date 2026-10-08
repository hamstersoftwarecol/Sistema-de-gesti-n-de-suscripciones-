{{-- Shared fields for creating / editing a pricing plan --}}
<div class="grid gap-4 sm:grid-cols-2">
    <x-forms.input name="name" :label="__('Plan name')" :value="$plan->name" required class="sm:col-span-2" :id="'plan_name_'.($plan->id ?? 'new')" />
    <x-forms.input name="price" type="number" step="0.01" min="0" :label="__('Price')" :value="$plan->price" required :id="'plan_price_'.($plan->id ?? 'new')" />
    <x-forms.select name="currency_id" :label="__('Currency')" :options="$currencies" :value="$plan->currency_id ?? base_currency()?->id" required :id="'plan_currency_'.($plan->id ?? 'new')" />
    <x-forms.select name="billing_cycle" :label="__('Billing cycle')" :options="\App\Models\Plan::cycleOptions()" :value="$plan->billing_cycle ?? 'monthly'" required :id="'plan_cycle_'.($plan->id ?? 'new')" />
    <x-forms.input name="interval_count" type="number" min="1" max="36" :label="__('Every (periods)')" :value="$plan->interval_count ?? 1" required :id="'plan_interval_'.($plan->id ?? 'new')" />
    <x-forms.input name="trial_days" type="number" min="0" max="365" :label="__('Trial days')" :value="$plan->trial_days ?? 0" :id="'plan_trial_'.($plan->id ?? 'new')" />
    <x-forms.input name="setup_fee" type="number" step="0.01" min="0" :label="__('Setup fee')" :value="$plan->setup_fee ?? 0" :id="'plan_setup_'.($plan->id ?? 'new')" />
    <x-forms.textarea name="features" :label="__('Features (one per line)')" :value="$plan->features" class="sm:col-span-2" :id="'plan_features_'.($plan->id ?? 'new')" />
    <x-forms.checkbox name="is_active" :label="__('Active')" :checked="$plan->is_active ?? true" class="sm:col-span-2" :id="'plan_active_'.($plan->id ?? 'new')" />
</div>
