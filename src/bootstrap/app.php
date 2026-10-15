<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', ''));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('app:check-new-episodes')->dailyAt('06:00')->timezone('America/Denver')->withoutOverlapping();
        $schedule->command('app:check-movie-releases')->dailyAt('06:00')->timezone('America/Denver')->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
