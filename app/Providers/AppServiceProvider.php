<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use InvadersXX\FilamentJsoneditor\FilamentJsoneditorServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->terminating(function () {
            // possible fix of mysql error https://github.com/laravel/framework/issues/18471
            // user already has more than 'max_user_connections' active connections
            DB::disconnect();
        });

        app()->register(FilamentJsoneditorServiceProvider::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
