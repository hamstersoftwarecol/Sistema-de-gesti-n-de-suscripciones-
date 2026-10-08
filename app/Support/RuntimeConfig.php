<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Applies configuration stored in the settings table (company, locale, SMTP...) on top of config files.
 */
class RuntimeConfig
{
    public static function apply(): void
    {
        if (! Installer::isInstalled()) {
            return;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            Setting::allCached();
        } catch (Throwable) {
            return;
        }

        if ($name = Setting::get('company_name')) {
            config(['app.name' => $name]);
        }

        $timezone = Setting::get('timezone');
        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        $locale = Setting::get('default_locale');
        if ($locale && array_key_exists($locale, config('app.available_locales', []))) {
            config(['app.locale' => $locale]);
            app()->setLocale($locale);
        }

        static::applyMail();
    }

    public static function applyMail(): void
    {
        $mailer = Setting::get('mail_mailer');

        if (! $mailer) {
            return;
        }

        $encryption = Setting::get('mail_encryption', 'tls');

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => (int) Setting::get('mail_port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => Setting::get('mail_username'),
            'mail.mailers.smtp.password' => Setting::get('mail_password'),
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
            'mail.from.name' => Setting::get('mail_from_name', config('app.name')),
        ]);

        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }
}
