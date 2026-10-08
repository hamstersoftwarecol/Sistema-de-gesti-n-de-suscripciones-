<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_KEY = 'app_settings';

    /** Settings stored encrypted at rest. */
    public const SECRET_KEYS = ['mail_password', 'gemini_api_key', 'google_client_secret'];

    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        if (! array_key_exists($key, $all) || $all[$key] === null || $all[$key] === '') {
            return $default;
        }

        $value = $all[$key];

        if (in_array($key, self::SECRET_KEYS, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                return $default;
            }
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(static::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Persist one or many settings.
     *
     * @param  string|array<string, mixed>  $key
     */
    public static function set(string|array $key, mixed $value = null): void
    {
        $values = is_array($key) ? $key : [$key => $value];

        foreach ($values as $name => $val) {
            if (is_bool($val)) {
                $val = $val ? '1' : '0';
            }

            if (in_array($name, self::SECRET_KEYS, true) && $val !== null && $val !== '') {
                $val = Crypt::encryptString((string) $val);
            }

            static::query()->updateOrCreate(['key' => $name], ['value' => $val]);
        }

        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
