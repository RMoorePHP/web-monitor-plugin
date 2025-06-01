<?php

namespace RMoore\WebMonitor;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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

        RateLimiter::for('web-monitor:send-to-server', function(SendToServer $job) {
            return Limit::perMinute(100)->by(1);
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/web-monitor.php',
            'web-monitor'
        );
    }
}
