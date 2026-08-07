<?php

/**
 * Test runner shim.
 *
 * APP_ENV=local is set as a machine-level environment variable on this
 * developer machine, so it leaks into every php/artisan process and overrides
 * phpunit.xml / .env.testing. That leaves CSRF verification active during tests
 * (it only skips when running unit tests) and 419s every POST.
 *
 * This script forces APP_ENV=testing in its own process, then execs php artisan
 * test so the child process inherits it.
 */

$_ENV['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

$args = array_slice($argv, 1);
$cmd = array_merge(['php', 'artisan', 'test'], $args);

$command = implode(' ', array_map('escapeshellarg', $cmd));
passthru($command, $exitCode);

exit($exitCode);
