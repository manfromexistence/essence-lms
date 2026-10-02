<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeCatboxHost;

abstract class TestCase extends BaseTestCase
{
    /**
     * The stand-in for the media host, when a test needed one.
     */
    protected ?FakeCatboxHost $catbox = null;

    /**
     * Create the application, forcing the testing environment.
     *
     * APP_ENV=local is set as a machine-level environment variable on this
     * developer machine, so it leaks into every php/artisan process and the
     * framework's LoadEnvironmentVariables picks it up instead of .env.testing.
     * That would leave CSRF verification active during tests (it only skips when
     * running unit tests) and 419 every POST. Forcing the env here - before the
     * app boots - makes .env.testing win and CSRF is skipped.
     */
    public function createApplication()
    {
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        return parent::createApplication();
    }

    /**
     * Route media-host traffic to a local fake.
     *
     * Media is hosted on Catbox, so any test that uploads an asset would
     * otherwise make a real outbound request. Installing the fake by default
     * means an upload in a test is both fast and hermetic; individual tests can
     * still assert on what reached the host.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->catbox = FakeCatboxHost::install();
    }

    protected function tearDown(): void
    {
        $this->catbox?->cleanUp();
        $this->catbox = null;

        parent::tearDown();
    }
}