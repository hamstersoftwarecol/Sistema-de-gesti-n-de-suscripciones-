<x-install-layout>
    <form method="POST" action="{{ route('install.finish') }}" class="card" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div class="card-body space-y-6">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Company & administrator') }}</h1>
                <p class="text-sm text-gray-500">{{ __('You can change everything later in Settings.') }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-forms.input name="company_name" :label="__('Company name')" required autofocus />
                <x-forms.input name="company_email" type="email" :label="__('Company e-mail')" />
                <x-forms.select name="locale" :label="__('Language')" :options="available_locales()" :value="app()->getLocale()" required />
                <x-forms.select name="currency" :label="__('Base currency')" :options="$currencies" value="USD" required />
                <x-forms.select name="timezone" :label="__('Time zone')" :options="array_combine($timezones, $timezones)" :value="config('app.timezone')" required class="sm:col-span-2" />
            </div>
            <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                <p class="mb-4 font-semibold">{{ __('Administrator account') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.input name="name" :label="__('Full name')" required />
                    <x-forms.input name="email" type="email" :label="__('Email')" required />
                    <x-forms.input name="password" type="password" :label="__('Password')" required autocomplete="new-password" />
                    <x-forms.input name="password_confirmation" type="password" :label="__('Confirm Password')" required autocomplete="new-password" />
                </div>
            </div>
            <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                <x-forms.input name="gemini_api_key" :label="__('Gemini API key (optional)')" :hint="__('Enables the AI assistant. Get one for free at aistudio.google.com/apikey')" />
            </div>
        </div>
        <div class="flex justify-between border-t border-gray-200 p-4 dark:border-gray-800">
            <a href="{{ route('install.database') }}" class="btn-secondary">{{ __('Back') }}</a>
            <button class="btn-primary" :disabled="loading">
                <x-icon name="rocket" class="h-4 w-4" />
                <span x-show="!loading">{{ __('Finish installation') }}</span><span x-show="loading" x-cloak>{{ __('Installing...') }}</span>
            </button>
        </div>
    </form>
</x-install-layout>
