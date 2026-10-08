<?php

use App\Models\Currency;
use App\Models\Setting;
use App\Support\Installer;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    /** Read an application setting stored in the database. */
    function setting(string $key, mixed $default = null): mixed
    {
        if (! Installer::isInstalled()) {
            return $default;
        }

        try {
            return Setting::get($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('base_currency')) {
    function base_currency(): ?Currency
    {
        try {
            return Currency::default();
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('money')) {
    /** Format an amount using the given currency (defaults to the base currency). */
    function money(float|string|null $amount, ?Currency $currency = null): string
    {
        $currency ??= base_currency();

        return $currency ? $currency->format($amount) : number_format((float) $amount, 2);
    }
}

if (! function_exists('fdate')) {
    /** Format a date with the configured date format. */
    function fdate(mixed $date, bool $withTime = false): string
    {
        if (blank($date)) {
            return '—';
        }

        $format = setting('date_format', 'd/m/Y').($withTime ? ' H:i' : '');

        return Carbon::parse($date)->format($format);
    }
}

if (! function_exists('available_locales')) {
    /** @return array<string, string> */
    function available_locales(): array
    {
        return config('app.available_locales', ['es' => 'Español', 'en' => 'English']);
    }
}
