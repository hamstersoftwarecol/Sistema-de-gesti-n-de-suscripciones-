@php
    $user = auth()->user();
    $links = [
        ['portal.dashboard', 'home', __('Home'), 'portal.dashboard'],
        ['portal.subscriptions', 'refresh', __('Subscriptions'), 'portal.subscriptions*'],
        ['portal.invoices', 'document', __('Invoices'), 'portal.invoices*'],
        ['portal.payments', 'banknotes', __('Payments'), 'portal.payments'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('layouts.partials.head')
</head>
<body class="h-full font-sans antialiased">
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2">
                <x-application-logo class="h-8 w-8" />
                <span class="hidden font-bold sm:inline">{{ setting('company_name', config('app.name')) }}</span>
                <span class="badge bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ __('Customer portal') }}</span>
            </a>
            <nav class="ml-6 hidden gap-1 md:flex">
                @foreach ($links as [$route, $icon, $label, $active])
                    <a href="{{ route($route) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($active)])><x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}</a>
                @endforeach
            </nav>
            <div class="ml-auto flex items-center gap-1">
                <x-pwa-install />
                <x-locale-menu />
                <x-theme-menu />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="btn-icon" aria-label="{{ __('Account') }}"><x-icon name="user" /></button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="border-b border-gray-100 px-4 py-2 dark:border-gray-700">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 pb-24 pt-6 md:pb-10">
        <x-flash />
        {{ $slot }}
    </main>

    {{-- Mobile bottom navigation --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] dark:border-gray-800 dark:bg-gray-900 md:hidden">
        @foreach ($links as [$route, $icon, $label, $active])
            <a href="{{ route($route) }}" @class(['flex flex-col items-center gap-1 py-2 text-[11px] font-medium', 'text-primary-600 dark:text-primary-400' => request()->routeIs($active), 'text-gray-500' => ! request()->routeIs($active)])>
                <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
            </a>
        @endforeach
    </nav>
</body>
</html>
