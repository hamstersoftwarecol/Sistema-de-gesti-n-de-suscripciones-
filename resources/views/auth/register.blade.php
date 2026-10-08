<x-guest-layout :title="__('Register')">
    <h1 class="text-2xl font-bold tracking-tight">{{ __('Create your account') }}</h1>
    <p class="mb-6 mt-1 text-sm text-gray-500">{{ __('Access your subscriptions, invoices and payments.') }}</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="company" :value="__('Company (optional)')" />
            <x-text-input id="company" type="text" name="company" :value="old('company')" autocomplete="organization" />
        </div>
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <x-primary-button class="w-full py-2.5">{{ __('Register') }}</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500"><a class="link" href="{{ route('login') }}">{{ __('Already registered?') }}</a></p>
</x-guest-layout>
