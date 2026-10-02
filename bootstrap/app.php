<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\StudentExamAccessMiddleware;
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
        // env var (comma-separated IPs/CIDRs) for your host. The special value
        // "*" must be passed as a STRING — Laravel's TrustProxies middleware
        // only handles "trust the calling IP" when it receives the scalar "*";
        // an array like ['*'] is treated as an IP/CIDR list and never matches,
        // which left Render serving http:// asset URLs on the https site.
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,100.64.0.0/10'));
        $middleware->trustProxies(
            at: $trustedProxies === '*'
                ? '*'
                : array_filter(array_map('trim', explode(',', $trustedProxies))),
        );
        $middleware->appendToGroup('web', RequirePasswordChange::class);
        // Deactivated accounts must stop working on their *existing* session,
        // not merely be refused at the next login attempt.
        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
