<?php

// Runs before the application boots during tests (phpunit.xml "bootstrap").
// The checked-in .env sets APP_ENV=local; forcing "testing" here guarantees the
// framework's CSRF middleware (which only skips verification when running unit
// tests) is disabled in tests, and that .env.testing is loaded instead of .env.
$_ENV['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

require __DIR__.'/../vendor/autoload.php';
