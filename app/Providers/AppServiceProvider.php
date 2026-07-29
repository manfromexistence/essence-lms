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
