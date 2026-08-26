<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    protected const APP_HOST = '127.0.0.1';

    protected const APP_PORT = 8200;

    /** @var resource|null */
    protected static $appServerProcess = null;

    /**
     * Prepare for Dusk test execution: start ChromeDriver and a real HTTP
     * server for the application so the suite is fully self-contained.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }

        if (empty($_ENV['DUSK_APP_URL']) && empty(env('DUSK_APP_URL'))) {
            static::startAppServer();
        }
    }

    /**
     * Boot `php artisan serve` with the phpunit.dusk.xml environment so the
     * browser talks to the same database/storage the tests prepare.
     */
    protected static function startAppServer(): void
    {
        $env = array_merge(
            $_ENV,
            $_SERVER,
            array_filter(getenv(), fn ($value) => $value !== false),
            [
                'APP_ENV' => $_ENV['APP_ENV'] ?? 'dusk',
                'APP_URL' => 'http://' . self::APP_HOST . ':' . self::APP_PORT,
                'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? 'sqlite',
                'DB_DATABASE' => $_ENV['DB_DATABASE'] ?? 'database/dusk.sqlite',
            ]
        );

        static::$appServerProcess = proc_open(
            [PHP_BINARY, 'artisan', 'serve', '--host=' . self::APP_HOST, '--port=' . self::APP_PORT],
            [['pipe', 'r'], ['file', 'storage/logs/dusk-server.log', 'a'], ['file', 'storage/logs/dusk-server.log', 'a']],
            $pipes,
            dirname(__DIR__),
            $env
        );

        if (!is_resource(static::$appServerProcess)) {
            static::fail('Unable to start the Dusk application server.');
        }

        // Wait until the server answers before any browser touches it.
        $deadline = microtime(true) + 30;
        $url = 'http://' . self::APP_HOST . ':' . self::APP_PORT . '/up';
        while (microtime(true) < $deadline) {
            $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
            $body = @file_get_contents($url, false, $context);
            if ($body !== false) {
                return;
            }
            usleep(300_000);
        }

        static::fail('The Dusk application server did not become ready in time.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(static::$appServerProcess)) {
            proc_terminate(static::$appServerProcess);
            proc_close(static::$appServerProcess);
            static::$appServerProcess = null;
        }

        parent::tearDownAfterClass();
    }

    /**
     * The base URL browsers should use.
     */
    protected function baseUrl(): string
    {
        return $_ENV['DUSK_APP_URL']
            ?? env('DUSK_APP_URL')
            ?? 'http://' . self::APP_HOST . ':' . self::APP_PORT;
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
