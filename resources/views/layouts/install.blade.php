<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system" data-accent="indigo" data-surface="gray">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Setup wizard') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
    <script>if (window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    @php
        $steps = ['install.welcome' => __('Requirements'), 'install.database' => __('Database'), 'install.setup' => __('Company & admin')];
        $current = array_search(request()->route()?->getName(), array_keys($steps));
    @endphp
    <div class="mx-auto max-w-3xl px-4 py-10">
        <div class="mb-8 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-10 w-10" />
                <div>
                    <p class="text-lg font-bold">SubsERP</p>
                    <p class="text-xs text-gray-500">{{ __('Setup wizard') }}</p>
                </div>
            </div>
            <div class="flex gap-1 text-xs">
                @foreach (config('app.available_locales') as $code => $name)
                    <a href="{{ route('install.welcome', ['lang' => $code]) }}" @class(['rounded px-2 py-1', 'bg-primary-600 text-white' => app()->getLocale() === $code, 'text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-800' => app()->getLocale() !== $code])>{{ strtoupper($code) }}</a>
                @endforeach
            </div>
        </div>

        <ol class="mb-8 grid grid-cols-3 gap-2">
            @foreach (array_values($steps) as $i => $label)
                <li class="flex items-center gap-2">
                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-emerald-500 text-white' => $current !== false && $i < $current,
                        'bg-primary-600 text-white' => $i === $current,
                        'bg-gray-200 text-gray-500 dark:bg-gray-800' => $current === false || $i > $current,
                    ])>{{ $current !== false && $i < $current ? '✓' : $i + 1 }}</span>
                    <span class="hidden text-sm font-medium sm:inline">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        <x-flash />
        {{ $slot }}

        <p class="mt-8 text-center text-xs text-gray-400">Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</p>
    </div>
</body>
</html>
