<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Sign in with Google" using the credentials stored in Settings → Integrations.
 */
class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return Setting::bool('google_login_enabled')
            && filled(Setting::get('google_client_id'))
            && filled(Setting::get('google_client_secret'));
    }

    public function redirect(): SymfonyRedirect
    {
        abort_unless(static::enabled(), 404);

        $this->configure();

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(static::enabled(), 404);

        $this->configure();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => __('Google authentication failed. Please try again.')]);
        }

        // Only trust e-mail addresses Google has verified before matching existing accounts.
        if (($googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? true) === false || blank($googleUser->getEmail())) {
            return redirect()->route('login')->withErrors(['email' => __('Google authentication failed. Please try again.')]);
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $googleUser->getEmail())->first();

        if (! $user) {
            if (! Setting::bool('google_auto_register')) {
                return redirect()->route('login')->withErrors(['email' => __('There is no account for :email. Ask an administrator to invite you.', ['email' => $googleUser->getEmail()])]);
            }

            $user = DB::transaction(function () use ($googleUser) {
                $customer = Customer::query()->create([
                    'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                    'email' => $googleUser->getEmail(),
                    'status' => 'lead',
                    'currency_id' => base_currency()?->id,
                ]);

                return User::query()->create([
                    'name' => $customer->name,
                    'email' => $googleUser->getEmail(),
                    'role' => User::ROLE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'email_verified_at' => now(),
                ]);
            });
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => __('Your account has been disabled.')]);
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar() ?: $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        ActivityLog::record('login', $user, "Google login {$user->email}");

        return redirect()->intended($user->homeRoute());
    }

    protected function configure(): void
    {
        config(['services.google' => [
            'client_id' => Setting::get('google_client_id'),
            'client_secret' => Setting::get('google_client_secret'),
            'redirect' => route('auth.google.callback'),
        ]]);
    }
}
