<?php

use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\StudentExamAccessMiddleware;
use App\Http\Middleware\RequirePasswordChange;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register RBAC middleware aliases
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'student.exam.access' => StudentExamAccessMiddleware::class,
            'password.changed' => RequirePasswordChange::class,
        ]);

        // Trust the deployment proxy so HTTPS URLs are generated correctly.
        // Default to private/cloud ranges; override with the TRUSTED_PROXIES
        // env var (comma-separated IPs/CIDRs) for your host (e.g. Render).
        $middleware->trustProxies(
            at: array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,100.64.0.0/10')))),
        );
        $middleware->appendToGroup('web', RequirePasswordChange::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
