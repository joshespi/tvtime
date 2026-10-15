<?php

namespace App\Providers;

use App\Services\Shows\TmdbClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One instance per request/command run, not one per injection — lets
        // TmdbClient::$imdbIdCache actually dedupe cast lookups across every
        // service that resolves it in the same run (e.g. both ShowSyncService
        // and MovieSyncService during BackfillCastAndTrailer). Safe as a
        // singleton because this app runs on php-fpm, not Octane — nothing
        // persists across requests.
        $this->app->singleton(TmdbClient::class);
    }
}
