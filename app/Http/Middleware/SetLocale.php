<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('app.available_locales', []));

        $locale = collect([
            $request->user()?->locale,
            $request->session()->get('locale'),
            setting('default_locale'),
            config('app.locale'),
        ])->first(fn ($candidate) => $candidate && in_array($candidate, $available, true));

        if ($locale) {
            app()->setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
