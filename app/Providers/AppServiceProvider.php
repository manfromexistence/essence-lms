<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SidebarService;
use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SidebarService as a singleton
        $this->app->singleton(SidebarService::class, function ($app) {
            return new SidebarService();
        });

        // WORKAROUND: Set upload_tmp_dir at runtime if not set
        // This fixes the "Missing temporary folder" error on some systems
        if (!ini_get('upload_tmp_dir') || empty(ini_get('upload_tmp_dir'))) {
            $tempDir = sys_get_temp_dir();
            if (is_dir($tempDir) && is_writable($tempDir)) {
                ini_set('upload_tmp_dir', $tempDir);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(30)->by($request->ip()),
        ]);

        // Scoped route model binding for questions within exams
        Route::bind('question', function ($value, $route) {
            $exam = $route->parameter('exam');
            if ($exam instanceof \App\Models\Exam) {
                return $exam->questions()->findOrFail($value);
            }
            return \App\Models\Question::findOrFail($value);
        });

        // Payment method numbers come from Settings first, env second — nothing
        // user-facing ever falls back to a placeholder number.
        $this->app->resolving('config', function ($config) {
            /** @var \Illuminate\Config\Repository $config */
            try {
                $methods = $config->get('payment-methods.methods', []);
                $settings = function (string $key) {
                    try {
                        return \App\Models\Setting::getValue($key);
                    } catch (\Throwable) {
                        return null;
                    }
                };
                if (isset($methods['bkash'])) {
                    $methods['bkash']['number'] = $settings('bkash_number') ?: $methods['bkash']['number'] ?? null;
                    $methods['bkash']['account_name'] = $settings('bkash_account_name') ?: $methods['bkash']['account_name'] ?? null;
                }
                if (isset($methods['nagad'])) {
                    $methods['nagad']['number'] = $settings('nagad_number') ?: $methods['nagad']['number'] ?? null;
                }
                if (isset($methods['rocket'])) {
                    $methods['rocket']['number'] = $settings('rocket_number') ?: $methods['rocket']['number'] ?? null;
                }
                if (isset($methods['bank_transfer']['details'])) {
                    $methods['bank_transfer']['details']['bank_name'] = $settings('bank_name') ?: null;
                    $methods['bank_transfer']['details']['account_name'] = $settings('bank_account_name') ?: null;
                    $methods['bank_transfer']['details']['account_number'] = $settings('bank_account_number') ?: null;
                    $methods['bank_transfer']['details']['branch'] = $settings('bank_branch') ?: null;
                }
                $config->set('payment-methods.methods', $methods);
            } catch (\Throwable) {
            }
        });

        // Register the sidebar composer for the admin layout
        View::composer('layouts.admin', SidebarComposer::class);

        // Define gates for permission checks
        Gate::before(function (User $user, string $ability) {
            // Super admin can do everything
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Define permission-based gates
        $permissions = [
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            'batches.view', 'batches.create', 'batches.edit', 'batches.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
            'exams.view', 'exams.create', 'exams.edit', 'exams.delete',
            'attendance.view', 'attendance.record',
            'reports.view', 'reports.export',
            'communication.view', 'communication.send',
            'settings.view', 'settings.manage',
            'roles.view', 'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }
}
