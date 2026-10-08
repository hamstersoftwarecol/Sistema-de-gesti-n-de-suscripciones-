<x-app-layout :title="$user->exists ? __('Edit user') : __('New user')">
    <x-page-header :title="$user->exists ? __('Edit user') : __('New user')" :back="route('admin.users.index')" />

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card max-w-3xl" x-data="{ role: @js(old('role', $user->role)) }">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-forms.input name="name" :label="__('Name')" :value="$user->name" required />
            <x-forms.input name="email" type="email" :label="__('Email')" :value="$user->email" required />
            <div>
                <label class="label" for="role">{{ __('Role') }} <span class="text-red-500">*</span></label>
                <select id="role" name="role" class="input" x-model="role" required>
                    @foreach (\App\Models\User::roleOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="hint" x-show="role === 'admin'">{{ __('Full access, including settings and backups.') }}</p>
                <p class="hint" x-show="role === 'staff'">{{ __('Manages customers, billing and catalog. No system settings.') }}</p>
                <p class="hint" x-show="role === 'seller'">{{ __('Only sees their own customers. Link it from the Sellers module.') }}</p>
                <p class="hint" x-show="role === 'customer'">{{ __('Only has access to the customer portal.') }}</p>
            </div>
            <div x-show="role === 'customer'">
                <x-forms.select name="customer_id" :label="__('Customer')" :options="$customers" :value="$user->customer_id" :placeholder="__('Select a customer...')" />
            </div>
            <x-forms.input name="phone" :label="__('Phone')" :value="$user->phone" />
            <x-forms.select name="locale" :label="__('Language')" :options="available_locales()" :value="$user->locale" :placeholder="__('Default')" />
            <x-forms.input name="password" type="password" :label="$user->exists ? __('New password (optional)') : __('Password')" :required="! $user->exists" autocomplete="new-password" />
            <x-forms.checkbox name="is_active" :label="__('Active account')" :checked="$user->is_active" class="pt-6" />
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-800">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
