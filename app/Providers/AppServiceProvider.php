<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use App\Services\RouteOptimizerService;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        // Share Google Maps API key with all Inertia responses
        Inertia::share([
            'googleMapsApiKey' => config('app.google_maps_api_key'),
        ]);
        Vite::prefetch(concurrency: 3);
    }
}
