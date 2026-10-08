<x-guest-layout :title="__('Log in')">
    <h1 class="text-2xl font-bold tracking-tight">{{ __('Welcome back') }}</h1>
    <p class="mb-6 mt-1 text-sm text-gray-500">{{ __('Sign in to manage your subscriptions.') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (\App\Http\Controllers\Auth\GoogleController::enabled())
        <a href="{{ route('auth.google') }}" class="btn-secondary w-full py-2.5">
            <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.3-.4-3.5z"/></svg>
            {{ __('Continue with Google') }}
        </a>
        <div class="my-6 flex items-center gap-3 text-xs uppercase text-gray-400"><span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>{{ __('or') }}<span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span></div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="link text-xs" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="checkbox" name="remember">
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
        </label>
        <x-primary-button class="w-full py-2.5">{{ __('Log in') }}</x-primary-button>
    </form>

    @if (filter_var(setting('allow_registration', false), FILTER_VALIDATE_BOOLEAN))
        <p class="mt-6 text-center text-sm text-gray-500">{{ __("Don't have an account?") }} <a href="{{ route('register') }}" class="link">{{ __('Register') }}</a></p>
    @endif
</x-guest-layout>
