<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureAppInstalled;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(
            prepend: [EnsureAppInstalled::class],
            append: [SetLocale::class, EnsureUserIsActive::class, CheckMaintenanceMode::class],
        );

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectUsersTo(fn ($request) => $request->user()?->homeRoute() ?? '/');

        // The web cron endpoint is called by external schedulers without a CSRF token.
        $middleware->validateCsrfTokens(except: ['cron/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
