<?php

namespace RMoore\WebMonitor;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->app->singleton(Monitor::class, function ($app) {
            return new Monitor;
        });

        $this->publishes([
            __DIR__.'/../config/web-monitor.php' => config_path('web-monitor.php')
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/web-monitor.php',
            'web-monitor'
        );
    }
}
