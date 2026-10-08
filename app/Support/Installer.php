<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Tracks whether the setup wizard has been completed and checks server requirements.
 */
class Installer
{
    public static function flagPath(): string
    {
        return storage_path('app/installed');
    }

    public static function isInstalled(): bool
    {
        $forced = config('app.installed');

        if ($forced !== null && $forced !== '') {
            return filter_var($forced, FILTER_VALIDATE_BOOLEAN);
        }

        return File::exists(static::flagPath());
    }

    public static function markInstalled(): void
    {
        File::ensureDirectoryExists(dirname(static::flagPath()));
        File::put(static::flagPath(), now()->toIso8601String());
    }

    /** @return array<int, array{label: string, ok: bool, value?: string}> */
    public static function requirements(): array
    {
        $checks = [[
            'label' => 'PHP >= 8.2',
            'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'value' => PHP_VERSION,
        ]];

        foreach (['pdo_sqlite', 'sqlite3', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'curl', 'fileinfo', 'dom', 'gd'] as $extension) {
            $checks[] = ['label' => "ext-{$extension}", 'ok' => extension_loaded($extension)];
        }

        return $checks;
    }

    /** @return array<int, array{label: string, ok: bool}> */
    public static function permissions(): array
    {
        $paths = [
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'database' => database_path(),
        ];

        return collect($paths)
            ->map(fn ($path, $label) => ['label' => $label, 'ok' => is_writable($path)])
            ->values()
            ->all();
    }

    public static function passes(): bool
    {
        return collect(static::requirements())->every('ok') && collect(static::permissions())->every('ok');
    }
}
