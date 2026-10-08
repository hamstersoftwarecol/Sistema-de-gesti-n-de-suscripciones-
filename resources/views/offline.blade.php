<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Offline') }}</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #111827; color: #f3f4f6; display: flex; min-height: 100vh; align-items: center; justify-content: center; text-align: center; padding: 24px; }
        p { color: #9ca3af; }
        button { margin-top: 12px; padding: 10px 18px; border: 0; border-radius: 8px; background: #4f46e5; color: #fff; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div>
        <h1>{{ __('You are offline') }}</h1>
        <p>{{ __('Check your internet connection. The page will be available again when you reconnect.') }}</p>
        <button onclick="location.reload()">{{ __('Retry') }}</button>
    </div>
</body>
</html>
