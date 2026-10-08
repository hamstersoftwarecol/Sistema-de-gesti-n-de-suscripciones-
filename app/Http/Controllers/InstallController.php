<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use App\Support\DefaultContent;
use App\Support\Installer;
use Database\Seeders\BaseDataSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * Three-step setup wizard: requirements → database → company & administrator.
 */
class InstallController extends Controller
{
    public function welcome(Request $request): View
    {
        if ($request->filled('lang') && array_key_exists($request->query('lang'), config('app.available_locales'))) {
            $request->session()->put('locale', $request->query('lang'));
            app()->setLocale($request->query('lang'));
        }

        return view('install.welcome', [
            'requirements' => Installer::requirements(),
            'permissions' => Installer::permissions(),
            'passes' => Installer::passes(),
        ]);
    }

    public function database(): View|RedirectResponse
    {
        if (! Installer::passes()) {
            return redirect()->route('install.welcome');
        }

        return view('install.database', [
            'path' => config('database.connections.sqlite.database'),
            'migrated' => $this->isMigrated(),
        ]);
    }

    public function migrate(Request $request): RedirectResponse
    {
        try {
            $path = config('database.connections.sqlite.database');

            if (config('database.default') === 'sqlite' && $path !== ':memory:' && ! File::exists($path)) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, '');
            }

            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            return back()->with('error', __('The database could not be prepared: :error', ['error' => $e->getMessage()]));
        }

        $request->session()->put('install.demo', $request->boolean('demo'));

        return redirect()->route('install.setup');
    }

    public function setup(): View|RedirectResponse
    {
        if (! $this->isMigrated()) {
            return redirect()->route('install.database');
        }

        return view('install.setup', [
            'currencies' => collect(DefaultContent::currencies())->mapWithKeys(fn ($c) => [$c['code'] => $c['code'].' — '.$c['name']]),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function finish(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'locale' => ['required', 'in:'.implode(',', array_keys(config('app.available_locales')))],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'size:3'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app()->setLocale($data['locale']);

            (new BaseDataSeeder)->run($data['locale'], $data['currency']);

            Setting::set([
                'company_name' => $data['company_name'],
                'company_email' => $data['company_email'],
                'default_locale' => $data['locale'],
                'timezone' => $data['timezone'],
                'mail_from_address' => $data['company_email'],
                'mail_from_name' => $data['company_name'],
            ]);

            if (! empty($data['gemini_api_key'])) {
                Setting::set('gemini_api_key', $data['gemini_api_key']);
            }

            Currency::query()->where('code', $data['currency'])->first()?->makeDefault();

            $admin = User::query()->updateOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'password' => $data['password'],
                'role' => User::ROLE_ADMIN,
                'locale' => $data['locale'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            if ($request->session()->pull('install.demo')) {
                app()->call([new DemoDataSeeder, 'run']);
            }

            Installer::markInstalled();
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', __('Installation failed: :error', ['error' => $e->getMessage()]));
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', __('Installation completed! Welcome to :app.', ['app' => $data['company_name']]));
    }

    protected function isMigrated(): bool
    {
        try {
            return Schema::hasTable('settings') && Schema::hasTable('subscriptions');
        } catch (Throwable) {
            return false;
        }
    }
}
