@php
    $code = trim($__env->yieldContent('code'));
    $messages = [
        '403' => __('You do not have permission to access this section.'),
        '404' => __('The page you are looking for does not exist.'),
        '419' => __('Your session has expired. Please reload the page.'),
        '500' => __('Something went wrong on our side.'),
        '503' => __('Service temporarily unavailable.'),
    ];
    $message = isset($exception) && $exception->getMessage() && $code === '403' ? $exception->getMessage() : ($messages[$code] ?? __('Error'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ config('app.name') }}</title>
    <script>if (localStorage.getItem('theme') === 'dark' || (localStorage.getItem('theme') !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');</script>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f3f4f6; color: #111827; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 24px; }
        .dark body { background: #030712; color: #f3f4f6; }
        .code { font-size: 72px; font-weight: 800; color: #6366f1; margin: 0; }
        p { color: #6b7280; max-width: 28rem; }
        a { display: inline-block; margin-top: 16px; padding: 10px 18px; border-radius: 8px; background: #4f46e5; color: #fff; text-decoration: none; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>
    <div style="text-align: center">
        <p class="code">{{ $code }}</p>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">{{ __('Back to home') }}</a>
    </div>
</body>
</html>
