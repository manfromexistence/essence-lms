<?php

// Runs before the application boots during tests (phpunit.xml "bootstrap").
// The checked-in .env sets APP_ENV=production and this dev machine exports the
// whole .env as process environment variables. PHP copies those into $_SERVER,
// and Laravel's dotenv (vlucas/phpdotenv) reads $_SERVER with HIGHER precedence
// than $_ENV/getenv — while PHPUnit's <env> directives only write putenv/$_ENV.
// So every phpunit.xml test variable must also be forced into $_SERVER here, or
// the tests boot against production MySQL and hit "Application In Production"
// confirmation prompts / connection refused errors.
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

// Mirror of the phpunit.xml <php> env block, forced into $_SERVER as well.
$forcedTestEnv = [
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'PULSE_ENABLED' => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED' => 'false',
];

foreach ($forcedTestEnv as $name => $value) {
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
    putenv("{$name}={$value}");
}

require __DIR__.'/../vendor/autoload.php';
