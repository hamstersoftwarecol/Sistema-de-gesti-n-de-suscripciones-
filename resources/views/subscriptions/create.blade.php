<x-app-layout :title="__('New subscription')">
    <x-page-header :title="__('New subscription')" :back="route('subscriptions.index')" />

    @php
        $planData = $plans->mapWithKeys(fn ($p) => [$p->id => [
            'price' => (float) $p->price,
            'trial' => $p->trial_days,
            'setup' => (float) $p->setup_fee,
            'symbol' => $p->currency?->symbol ?? base_currency()?->symbol,
            'cycle' => $p->cycleLabel(),
        ]]);
    @endphp

    <form method="POST" action="{{ route('subscriptions.store') }}" class="grid gap-6 lg:grid-cols-3"
          x-data="{
              plans: @js($planData),
              planId: @js((string) old('plan_id', '')),
              price: @js(old('price', '')),
              quantity: @js((int) old('quantity', 1)),
              discount: @js((float) old('discount', 0)),
              trial: @js(old('trial_days', '')),
              get plan() { return this.plans[this.planId] || null },
              pick() { if (this.plan) { this.price = this.plan.price; this.trial = this.plan.trial; } },
              get total() { const p = Number(this.price || 0) * Number(this.quantity || 1); return p - p * (Number(this.discount || 0) / 100); },
          }">
        @csrf
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Subscription') }}</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <x-forms.select name="customer_id" :label="__('Customer')" :options="$customers" :value="$subscription->customer_id" :placeholder="__('Select a customer...')" required class="sm:col-span-2" />

                    <div class="sm:col-span-2">
                        <label class="label" for="plan_id">{{ __('Plan') }} <span class="text-red-500">*</span></label>
                        <select id="plan_id" name="plan_id" class="input" x-model="planId" @change="pick()" required>
                            <option value="">{{ __('Select a plan...') }}</option>
                            @foreach ($plans->groupBy(fn ($p) => $p->product->name) as $product => $group)
                                <optgroup label="{{ $product }}">
                                    @foreach ($group as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->currency?->format($plan->price) }} / {{ mb_strtolower($plan->cycleLabel()) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('plan_id') <p class="input-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="price">{{ __('Price per period') }}</label>
                        <input id="price" type="number" step="0.01" min="0" name="price" class="input" x-model="price">
                        <p class="hint">{{ __('Defaults to the plan price.') }}</p>
                        @error('price') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="quantity">{{ __('Quantity / seats') }}</label>
                        <input id="quantity" type="number" min="1" name="quantity" class="input" x-model="quantity" required>
                    </div>
                    <div>
                        <label class="label" for="discount">{{ __('Discount (%)') }}</label>
                        <input id="discount" type="number" step="0.01" min="0" max="100" name="discount" class="input" x-model="discount">
                    </div>
                    <x-forms.input name="start_date" type="date" :label="__('Start date')" :value="$subscription->start_date" required />
                    <div>
                        <label class="label" for="trial_days">{{ __('Trial days') }}</label>
                        <input id="trial_days" type="number" min="0" max="365" name="trial_days" class="input" x-model="trial">
                        <p class="hint">{{ __('With a trial, the first invoice is issued when the trial ends.') }}</p>
                    </div>
                    @if (auth()->user()->isStaff())
                        <x-forms.select name="seller_id" :label="__('Seller')" :options="$sellers" :value="$subscription->seller_id" :placeholder="__('Customer seller')" />
                    @endif
                    <div class="space-y-3 sm:col-span-2">
                        <x-forms.checkbox name="auto_renew" :label="__('Automatic renewal')" :hint="__('An invoice is generated automatically at the start of every period.')" :checked="true" />
                        <x-forms.checkbox name="issue_invoice" :label="__('Issue the first invoice now')" :checked="true" />
                    </div>
                    <x-forms.textarea name="notes" :label="__('Notes')" class="sm:col-span-2" />
                </div>
            </div>

            <x-custom-fields :model="$subscription" />
        </div>

        <div>
            <div class="card sticky top-20">
                <div class="card-header"><h2 class="card-title">{{ __('Summary') }}</h2></div>
                <div class="card-body space-y-2 text-sm">
                    <template x-if="plan">
                        <div class="space-y-2">
                            <div class="dl-row"><dt>{{ __('Billing cycle') }}</dt><dd x-text="plan.cycle"></dd></div>
                            <div class="dl-row"><dt>{{ __('Trial') }}</dt><dd x-text="(trial || 0) + ' {{ __('days') }}'"></dd></div>
                            <div class="dl-row" x-show="plan.setup > 0"><dt>{{ __('Setup fee') }}</dt><dd x-text="plan.symbol + plan.setup.toFixed(2)"></dd></div>
                            <div class="flex items-baseline justify-between border-t border-gray-200 pt-3 dark:border-gray-800">
                                <span class="text-gray-500">{{ __('Amount per period') }}</span>
                                <span class="text-2xl font-bold" x-text="plan.symbol + total.toFixed(2)"></span>
                            </div>
                        </div>
                    </template>
                    <p x-show="!plan" class="text-gray-500">{{ __('Select a plan to see the summary.') }}</p>
                    <button class="btn-primary mt-4 w-full"><x-icon name="check" class="h-4 w-4" /> {{ __('Create subscription') }}</button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
