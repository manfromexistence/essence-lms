<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Media — images, course video, exam screenshots, payment proofs — is hosted
    | on Catbox rather than on this server's disk. Course video in particular
    | exceeds what a shared host's disk, PHP's upload_max_filesize, and the
    | client's own limits will comfortably carry.
    |
    | See config/media.php for the host settings.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'catbox'),

    // Retained because eight call sites resolve `config('filesystems.private')`
    // to decide how to stream or download an existing object.
    'private' => env('PRIVATE_FILESYSTEM_DISK', 'catbox'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "local", "catbox", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Catbox-hosted media. The `catbox` driver is registered in
        // App\Providers\StorageServiceProvider.
        'catbox' => [
            'driver' => 'catbox',
            'endpoint' => env('CATBOX_API_URL', 'https://catbox.moe/user/api.php'),
            'base_url' => env('CATBOX_BASE_URL', 'https://files.catbox.moe'),
            'userhash' => env('CATBOX_USERHASH'),
            'user_agent' => env('CATBOX_USER_AGENT', 'Laravel-LMS'),
            'timeout' => (int) env('CATBOX_TIMEOUT', 300),
            'connect_timeout' => (int) env('CATBOX_CONNECT_TIMEOUT', 15),
            'read_timeout' => (int) env('CATBOX_READ_TIMEOUT', 300),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
