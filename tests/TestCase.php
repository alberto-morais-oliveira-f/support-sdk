<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk\Tests;

use Am2tec\SupportSdk\Providers\SupportServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [SupportServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('support.url', 'https://support.test');
        $app['config']->set('support.app_key', 'k');
        $app['config']->set('support.app_secret', 's');
        $app['config']->set('support.retry_times', 0);
    }
}
