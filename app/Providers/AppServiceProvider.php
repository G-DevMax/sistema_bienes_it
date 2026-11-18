<?php

namespace App\Providers;

use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Session::extend('cache', function ($app): CacheBasedSessionHandler {
            return new CacheBasedSessionHandler(
                $app->make('cache')->store(config('session.store')),
                (int) config('session.lifetime')
            );
        });
    }
}
