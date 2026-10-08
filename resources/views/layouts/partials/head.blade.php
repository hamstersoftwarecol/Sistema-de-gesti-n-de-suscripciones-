@php
    $user = auth()->user();
    $themeMode = $user?->theme ?? setting('default_theme', 'system');
    $themeAccent = $user?->accent ?? setting('default_accent', 'indigo');
    $themeSurface = $user?->surface ?? setting('default_surface', 'gray');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
@auth
    <meta name="preferences-url" content="{{ route('preferences') }}">
@endauth
<meta name="theme-color" content="#4f46e5">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ setting('company_name', config('app.name')) }}">
<link rel="manifest" href="{{ url('manifest.webmanifest') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

<title>{{ $title ? $title.' · ' : '' }}{{ setting('company_name', config('app.name')) }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

<script>
    (function () {
        var root = document.documentElement;
        var fromUser = {{ $user ? 'true' : 'false' }};
        var defaults = { theme: @js($themeMode), accent: @js($themeAccent), surface: @js($themeSurface) };
        var prefs = {};
        ['theme', 'accent', 'surface'].forEach(function (key) {
            var stored = null;
            try { stored = localStorage.getItem(key); } catch (e) {}
            prefs[key] = fromUser ? defaults[key] : (stored || defaults[key]);
            try { if (fromUser) localStorage.setItem(key, defaults[key]); } catch (e) {}
        });
        root.dataset.theme = prefs.theme;
        root.dataset.accent = prefs.accent;
        root.dataset.surface = prefs.surface;
        if (prefs.theme === 'dark' || (prefs.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        }
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
