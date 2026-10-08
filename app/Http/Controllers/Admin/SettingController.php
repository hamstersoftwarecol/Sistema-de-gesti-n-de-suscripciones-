<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GeminiException;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\GeminiService;
use App\Services\ReminderService;
use App\Support\RuntimeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    /** Settings that are checkboxes (missing from the request means "off"). */
    protected const BOOLEANS = [
        'allow_registration', 'mark_past_due', 'auto_email_invoices', 'reminders_enabled',
        'google_login_enabled', 'google_auto_register', 'maintenance_mode', 'auto_backup',
    ];

    public static function tabs(): array
    {
        return [
            'general' => [__('General'), 'building'],
            'billing' => [__('Billing'), 'document'],
            'reminders' => [__('Reminders & cron'), 'bell'],
            'mail' => [__('E-mail (SMTP)'), 'envelope'],
            'integrations' => [__('Integrations'), 'key'],
            'maintenance' => [__('Maintenance'), 'wrench'],
        ];
    }

    public function edit(string $tab = 'general'): View
    {
        abort_unless(array_key_exists($tab, static::tabs()), 404);

        return view('admin.settings.edit', [
            'tab' => $tab,
            'tabs' => static::tabs(),
            'settings' => Setting::allCached(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(Request $request, string $tab): RedirectResponse
    {
        abort_unless(array_key_exists($tab, static::tabs()), 404);

        $data = $request->validate($this->rules($tab));

        foreach (self::BOOLEANS as $key) {
            if (array_key_exists($key, $this->rules($tab))) {
                $data[$key] = $request->boolean($key);
            }
        }

        // Secrets are only replaced when a new value is typed.
        foreach (Setting::SECRET_KEYS as $secret) {
            if (array_key_exists($secret, $data) && blank($data[$secret])) {
                unset($data[$secret]);
            }
        }

        if ($request->boolean('clear_gemini_api_key')) {
            $data['gemini_api_key'] = null;
        }

        Setting::set($data);

        return redirect()->route('admin.settings.edit', $tab)->with('success', __('Settings saved.'));
    }

    public function testMail(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_email' => ['required', 'email']]);

        try {
            RuntimeConfig::applyMail();
            Mail::raw(__('This is a test e-mail from :app. Your SMTP settings work!', ['app' => setting('company_name', config('app.name'))]), function ($message) use ($data) {
                $message->to($data['test_email'])->subject(__('SMTP test'));
            });
        } catch (Throwable $e) {
            return back()->with('error', __('The e-mail could not be sent: :error', ['error' => $e->getMessage()]));
        }

        return back()->with('success', __('Test e-mail sent to :email.', ['email' => $data['test_email']]));
    }

    public function testGemini(GeminiService $gemini): RedirectResponse
    {
        try {
            $answer = $gemini->generate([['role' => 'user', 'text' => 'Reply only with the word OK.']], null, 0)['text'];
        } catch (GeminiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Gemini connected successfully (:model): :answer', ['model' => $gemini->model(), 'answer' => mb_substr($answer, 0, 50)]));
    }

    public function runCron(BillingService $billing, ReminderService $reminders): RedirectResponse
    {
        $b = $billing->run();
        $r = $reminders->run();
        Setting::set('last_cron_run', now()->toDateTimeString());

        return back()->with('success', __('Cron executed: :invoices invoices issued, :expired expired, :overdue overdue, :reminders reminders sent.', [
            'invoices' => $b['invoices'],
            'expired' => $b['expired'],
            'overdue' => $b['overdue'],
            'reminders' => $r['renewal'] + $r['overdue'],
        ]));
    }

    public function regenerateCronToken(): RedirectResponse
    {
        Setting::set('cron_token', bin2hex(random_bytes(16)));

        return back()->with('success', __('A new cron URL has been generated.'));
    }

    protected function rules(string $tab): array
    {
        return match ($tab) {
            'general' => [
                'company_name' => ['required', 'string', 'max:100'],
                'company_email' => ['nullable', 'email', 'max:255'],
                'company_phone' => ['nullable', 'string', 'max:50'],
                'company_address' => ['nullable', 'string', 'max:255'],
                'company_tax_id' => ['nullable', 'string', 'max:50'],
                'company_website' => ['nullable', 'url', 'max:255'],
                'default_locale' => ['required', Rule::in(array_keys(config('app.available_locales')))],
                'timezone' => ['required', 'timezone'],
                'date_format' => ['required', Rule::in(['d/m/Y', 'm/d/Y', 'Y-m-d', 'd.m.Y', 'd-m-Y'])],
                'default_theme' => ['required', 'in:light,dark,system'],
                'default_accent' => ['required', 'in:indigo,blue,emerald,violet,rose,amber,teal'],
                'default_surface' => ['required', 'in:gray,slate,zinc,stone'],
                'allow_registration' => ['nullable'],
            ],
            'billing' => [
                'invoice_prefix' => ['required', 'string', 'max:20'],
                'invoice_next_number' => ['required', 'integer', 'min:1'],
                'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
                'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
                'invoice_notes' => ['nullable', 'string', 'max:2000'],
                'invoice_terms' => ['nullable', 'string', 'max:5000'],
                'mark_past_due' => ['nullable'],
                'auto_email_invoices' => ['nullable'],
            ],
            'reminders' => [
                'reminders_enabled' => ['nullable'],
                'reminder_days' => ['required', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
                'overdue_reminder_interval' => ['required', 'integer', 'min:1', 'max:60'],
            ],
            'mail' => [
                'mail_mailer' => ['required', 'in:smtp,sendmail,log'],
                'mail_host' => ['nullable', 'required_if:mail_mailer,smtp', 'string', 'max:255'],
                'mail_port' => ['nullable', 'required_if:mail_mailer,smtp', 'integer', 'min:1', 'max:65535'],
                'mail_username' => ['nullable', 'string', 'max:255'],
                'mail_password' => ['nullable', 'string', 'max:255'],
                'mail_encryption' => ['required', 'in:tls,ssl,none'],
                'mail_from_address' => ['required', 'email', 'max:255'],
                'mail_from_name' => ['required', 'string', 'max:100'],
            ],
            'integrations' => [
                'gemini_api_key' => ['nullable', 'string', 'max:255'],
                'gemini_model' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9.\-_]+$/'],
                'google_login_enabled' => ['nullable'],
                'google_auto_register' => ['nullable'],
                'google_client_id' => ['nullable', 'string', 'max:255'],
                'google_client_secret' => ['nullable', 'string', 'max:255'],
            ],
            'maintenance' => [
                'maintenance_mode' => ['nullable'],
                'maintenance_message' => ['nullable', 'string', 'max:1000'],
                'auto_backup' => ['nullable'],
                'backup_keep' => ['required', 'integer', 'min:1', 'max:100'],
            ],
        };
    }
}
