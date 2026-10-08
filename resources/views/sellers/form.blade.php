<x-app-layout :title="$seller->exists ? __('Edit seller') : __('New seller')">
    <x-page-header :title="$seller->exists ? __('Edit seller') : __('New seller')" :back="$seller->exists ? route('sellers.show', $seller) : route('sellers.index')" />

    <form method="POST" action="{{ $seller->exists ? route('sellers.update', $seller) : route('sellers.store') }}" class="space-y-6" x-data="{ login: @js((bool) old('create_login', (bool) $seller->user_id)) }">
        @csrf
        @if ($seller->exists) @method('PUT') @endif
        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-forms.input name="name" :label="__('Full name')" :value="$seller->name" required autofocus />
                <x-forms.input name="email" type="email" :label="__('Email')" :value="$seller->email" />
                <x-forms.input name="phone" :label="__('Phone')" :value="$seller->phone" />
                <x-forms.input name="commission_rate" type="number" step="0.01" min="0" max="100" :label="__('Commission (%)')" :value="$seller->commission_rate" required />
                <x-forms.input name="monthly_target" type="number" step="0.01" min="0" :label="__('Monthly sales target')" :value="$seller->monthly_target" :prefix="base_currency()?->symbol" />
                <x-forms.checkbox name="is_active" :label="__('Active')" :checked="$seller->is_active" class="pt-6" />
                <x-forms.textarea name="notes" :label="__('Notes')" :value="$seller->notes" class="sm:col-span-2" />
            </div>
        </div>

        <div class="card">
            <div class="card-body space-y-4">
                <label class="inline-flex items-start gap-2">
                    <input type="hidden" name="create_login" value="0">
                    <input type="checkbox" name="create_login" value="1" class="checkbox mt-0.5" x-model="login">
                    <span><span class="text-sm font-medium">{{ __('Give this seller access to the system') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Sellers only see their own customers, subscriptions, invoices and commissions.') }}</span></span>
                </label>
                <div x-show="login" x-cloak class="max-w-sm">
                    <x-forms.input name="password" type="password" :label="$seller->user_id ? __('New password (optional)') : __('Password')" autocomplete="new-password" />
                </div>
            </div>
        </div>

        <x-custom-fields :model="$seller" />

        <div class="flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
