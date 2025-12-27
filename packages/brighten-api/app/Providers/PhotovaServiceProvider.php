<?php

namespace App\Providers;

use App\Services\ProviderManager;
use Illuminate\Support\ServiceProvider;

class PhotovaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/photova.php',
            'photova'
        );

        $this->app->singleton(ProviderManager::class, function () {
            return new ProviderManager();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/photova.php' => config_path('photova.php'),
        ], 'photova-config');
    }
}
