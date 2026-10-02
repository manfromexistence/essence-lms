<?php

namespace App\Providers;

use App\Storage\CatboxAdapter;
use App\Storage\CatboxStorage;
use App\Storage\UrlAwarePathNormalizer;
use Illuminate\Contracts\Filesystem\Filesystem as LaravelFilesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;

/**
 * Registers the Catbox HTTP file host as a Laravel filesystem driver.
 *
 * Kept separate from AppServiceProvider so the storage concern — and the
 * adapter's Flysystem dependency — stay isolated from the app's other
 * bootstrapping.
 */
class StorageServiceProvider extends ServiceProvider
{
    private const DEFAULTS = [
        'endpoint' => 'https://catbox.moe/user/api.php',
        'base_url' => 'https://files.catbox.moe',
        'userhash' => null,
        'user_agent' => 'Laravel-LMS',
        'timeout' => 300,
        'connect_timeout' => 15,
        'read_timeout' => 300,
    ];

    public function register(): void
    {
        $this->app->singleton(
            CatboxStorage::class,
            fn () => new CatboxStorage((string) $this->app['config']->get('filesystems.default', 'catbox'))
        );
    }

    public function boot(): void
    {
        Storage::extend('catbox', function ($app, array $config): LaravelFilesystem {
            $adapter = new CatboxAdapter(array_merge(self::DEFAULTS, array_filter([
                'endpoint' => $config['endpoint'] ?? null,
                'base_url' => $config['base_url'] ?? null,
                'userhash' => $config['userhash'] ?? null,
                'user_agent' => $config['user_agent'] ?? null,
                'timeout' => $config['timeout'] ?? null,
                'connect_timeout' => $config['connect_timeout'] ?? null,
                'read_timeout' => $config['read_timeout'] ?? null,
            ], fn ($value) => $value !== null && $value !== '')));

            // Wrapped in Laravel's own adapter so that url(), temporaryUrl() and the
            // visibility helpers behave exactly as they do on the built-in drivers.
            return new FilesystemAdapter(
                new Flysystem($adapter, $config, new UrlAwarePathNormalizer),
                $adapter,
                $config,
            );
        });
    }
}
