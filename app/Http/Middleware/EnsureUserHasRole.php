<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin,staff')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasRole(...$roles)) {
            if ($user->isCustomer() && ! $request->expectsJson()) {
                return redirect()->route('portal.dashboard');
            }

            abort(403, __('You do not have permission to access this section.'));
        }

        return $next($request);
    }
}
