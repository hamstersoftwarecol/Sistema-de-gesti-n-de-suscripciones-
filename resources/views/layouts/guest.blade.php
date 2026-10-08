<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('layouts.partials.head')
</head>
<body class="h-full font-sans antialiased">
    <div class="grid min-h-full lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-primary-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-10 w-10 [&_rect]:fill-white/20" />
                <span class="text-xl font-bold">{{ setting('company_name', config('app.name')) }}</span>
            </div>
            <div class="relative z-10 max-w-md">
                <h2 class="text-3xl font-bold leading-tight">{{ __('Recurring billing, renewals and customers in one place.') }}</h2>
                <ul class="mt-6 space-y-3 text-white/85">
                    @foreach ([__('Automatic renewals and invoices'), __('Reminders by e-mail'), __('Gemini AI assistant'), __('Self-service customer portal')] as $feature)
                        <li class="flex items-center gap-3"><x-icon name="check-circle" class="h-5 w-5 text-white" /> {{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
            <p class="text-sm text-white/60">© {{ date('Y') }} {{ setting('company_name', config('app.name')) }}</p>
            <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute -top-24 right-24 h-64 w-64 rounded-full bg-white/5 blur-xl"></div>
        </div>

        <div class="flex flex-col px-4 py-6 sm:px-6">
            <div class="flex justify-end gap-1">
                <x-locale-menu />
                <x-theme-menu />
            </div>
            <div class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-sm">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-application-logo class="h-10 w-10" />
                        <span class="text-xl font-bold">{{ setting('company_name', config('app.name')) }}</span>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
