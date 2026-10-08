<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application-level maintenance mode toggled from Settings. Administrators keep full access.
 */
class CheckMaintenanceMode
{
    /** Routes that stay reachable so administrators can sign in. */
    protected array $except = ['login', 'logout', 'install', 'install/*', 'locale/*', 'auth/google/*', 'manifest.webmanifest', 'offline'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(setting('maintenance_mode', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        if ($request->user()?->isAdmin() || $request->is(...$this->except)) {
            return $next($request);
        }

        return response()->view('errors.maintenance', [
            'message' => setting('maintenance_message'),
        ], 503);
    }
}
