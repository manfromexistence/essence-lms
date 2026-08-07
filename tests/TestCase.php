<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application, forcing the testing environment.
     *
     * APP_ENV=local is set as a machine-level environment variable on this
     * developer machine, so it leaks into every php/artisan process and the
     * framework's LoadEnvironmentVariables picks it up instead of .env.testing.
     * That would leave CSRF verification active during tests (it only skips when
     * running unit tests) and 419 every POST. Forcing the env here — before the
     * app boots — makes .env.testing win and CSRF is skipped.
     */
    public function createApplication()
    {
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        return parent::createApplication();
    }
}
