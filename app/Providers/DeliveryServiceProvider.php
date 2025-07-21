<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/delivery.php', 'delivery'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/delivery.php' => config_path('delivery.php'),
            ], 'config');
        }
    }
}