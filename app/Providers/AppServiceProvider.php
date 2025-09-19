<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use App\Services\RouteOptimizerService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
         $this->app->singleton(RouteOptimizerService::class, function ($app) {
        return new RouteOptimizerService();
    });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
