<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', ''));

        // navigator.sendBeacon() can't attach the CSRF header, and the crash
        // this exists to diagnose may itself be tied to a stale/expired
        // session — the beacon needs to land either way.
        $middleware->validateCsrfTokens(except: ['client-errors']);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('app:check-new-episodes')->daily();
        $schedule->command('app:check-movie-releases')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
