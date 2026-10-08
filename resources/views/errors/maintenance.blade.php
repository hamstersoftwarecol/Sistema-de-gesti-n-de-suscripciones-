<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    @php $title = __('Maintenance'); @endphp
    @include('layouts.partials.head')
</head>
<body class="flex h-full items-center justify-center p-6 font-sans antialiased">
    <div class="max-w-md text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
            <x-icon name="wrench" class="h-8 w-8" />
        </span>
        <h1 class="mt-6 text-2xl font-bold">{{ __('We are performing maintenance') }}</h1>
        <p class="mt-2 text-gray-500">{{ $message ?: __('The system is temporarily unavailable. Please try again in a few minutes.') }}</p>
        <div class="mt-6 flex justify-center gap-2">
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-secondary">{{ __('Log Out') }}</button></form>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">{{ __('Administrator access') }}</a>
            @endauth
        </div>
    </div>
</body>
</html>
