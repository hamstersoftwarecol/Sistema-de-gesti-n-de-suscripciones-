@php
    $user = auth()->user();
    $navigation = \App\Support\Navigation::for($user);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('layouts.partials.head')
</head>
<body class="h-full font-sans antialiased" x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false">
    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebar" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-gray-950/50 lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 dark:border-gray-800 dark:bg-gray-900 lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 shrink-0 items-center justify-between gap-2 px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <x-application-logo class="h-8 w-8" />
                <span class="truncate text-base font-bold text-gray-900 dark:text-white">{{ setting('company_name', config('app.name')) }}</span>
            </a>
            <button class="btn-icon lg:hidden" @click="sidebar = false" aria-label="{{ __('Close') }}">
                <x-icon name="x" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            @foreach ($navigation as $group)
                @if ($group['section'])
                    <p class="nav-section">{{ $group['section'] }}</p>
                @endif
                <div class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           @class(['nav-link', 'nav-link-active' => \App\Support\Navigation::isActive($item['active'])])>
                            <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="border-t border-gray-200 p-3 dark:border-gray-800">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}" alt="" class="h-9 w-9 rounded-full object-cover">
                    @else
                        {{ $user->initials() }}
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-gray-500">{{ \App\Models\User::roleOptions()[$user->role] ?? $user->role }}</span>
                </span>
            </a>
        </div>
    </aside>

    <div class="flex min-h-full flex-col lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-gray-200 bg-white/90 px-3 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90 sm:px-6">
            <button class="btn-icon lg:hidden" @click="sidebar = true" aria-label="{{ __('Open menu') }}">
                <x-icon name="menu" />
            </button>

            <form action="{{ route('search') }}" method="GET" class="relative hidden max-w-md flex-1 sm:block">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search customers, subscriptions, invoices...') }}"
                       class="input pl-9" autocomplete="off">
            </form>

            <div class="ml-auto flex items-center gap-1">
                <a href="{{ route('search') }}" class="btn-icon sm:hidden" aria-label="{{ __('Search') }}"><x-icon name="search" /></a>

                @if (filter_var(setting('maintenance_mode', false), FILTER_VALIDATE_BOOLEAN))
                    <a href="{{ $user->isAdmin() ? route('admin.settings.edit', 'maintenance') : '#' }}" class="badge hidden bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300 sm:inline-flex">
                        <x-icon name="wrench" class="h-3.5 w-3.5" /> {{ __('Maintenance mode') }}
                    </a>
                @endif

                <x-pwa-install />
                <x-locale-menu />
                <x-theme-menu />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="btn-icon" aria-label="{{ __('Account') }}"><x-icon name="user" /></button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="border-b border-gray-100 px-4 py-2 dark:border-gray-700">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        @if ($user->isAdmin())
                            <x-dropdown-link :href="route('admin.settings.edit')">{{ __('Settings') }}</x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>

        <main class="flex-1 px-3 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <x-flash />
                {{ $slot }}
            </div>
        </main>

        <footer class="px-6 py-4 text-center text-xs text-gray-400">
            © {{ date('Y') }} {{ setting('company_name', config('app.name')) }} · {{ __('Subscription management ERP') }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
