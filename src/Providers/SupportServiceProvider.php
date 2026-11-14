<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk\Providers;

use Am2tec\SupportSdk\SupportSdk;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/support.php', 'support');

        $this->app->singleton(SupportSdk::class, function ($app) {
            $c = $app['config']['support'];

            return new SupportSdk(
                baseUrl: rtrim((string) ($c['url'] ?? ''), '/'),
                appKey: (string) ($c['app_key'] ?? ''),
                appSecret: (string) ($c['app_secret'] ?? ''),
                timeout: (int) ($c['timeout'] ?? 10),
                retryTimes: (int) ($c['retry_times'] ?? 2),
                retryDelayMs: (int) ($c['retry_delay_ms'] ?? 200),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config/support.php' => config_path('support.php')], 'support-config');
        }
    }
}
