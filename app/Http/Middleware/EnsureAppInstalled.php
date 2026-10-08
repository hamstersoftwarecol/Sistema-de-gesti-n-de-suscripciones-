<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every request to the setup wizard until the application has been installed.
 */
class EnsureAppInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInstallRoute = $request->is('install', 'install/*');

        if (Installer::isInstalled()) {
            if ($isInstallRoute) {
                return redirect('/');
            }

            return $next($request);
        }

        // First run on a fresh copy: create .env and the encryption key automatically.
        if (empty(config('app.key'))) {
            if (! File::exists(base_path('.env')) && File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), base_path('.env'));
            }

            Artisan::call('key:generate', ['--force' => true]);

            return redirect($request->fullUrl());
        }

        if (! $isInstallRoute) {
            return redirect('/install');
        }

        return $next($request);
    }
}
