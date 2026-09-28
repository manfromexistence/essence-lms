<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CqSubmission;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\Service;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Integrity guards for the two failure classes that shipped to production and
 * were only caught by manually probing the live site:
 *
 *  1. A controller calling view('x') where resources/views/x.blade.php does not
 *     exist — every request to that route died with
 *     InvalidArgumentException: View [x] not found. 13 such references existed.
 *
 *  2. A resource route (Route::resource) whose controller method was never
 *     written — e.g. SalaryController::show(), MaterialController::show(),
 *     ScheduleController::show() — so GET .../{id} returned
 *     "Call to undefined method".
 *
 * Both are cheap to detect statically, so they are asserted here instead of
 * relying on someone clicking every page.
 */
class ViewAndRouteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every view('name') reference in app/ and routes/ must resolve to a file.
     */
    public function test_every_view_reference_resolves_to_a_blade_file(): void
    {
        $viewsDir = resource_path('views');

        // Build the set of available view names in dot notation.
        $available = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsDir));
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $relative = str_replace(
                [$viewsDir.DIRECTORY_SEPARATOR, '.blade.php'],
                '',
                $file->getPathname()
            );
            $available[str_replace(DIRECTORY_SEPARATOR, '.', $relative)] = true;
        }

        $this->assertNotEmpty($available, 'No Blade views were discovered — check the path.');

        $missing = [];

        // Cover every way a view name can be resolved. Matching only view('x')
        // previously missed PDF::loadView('exports.pdf.financial-report'), which
        // then 500'd the account export.
        $patterns = [
            '/\bview\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/',        // view('x')
            '/\bloadView\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/',    // PDF::loadView('x')
            '/\bView::make\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/',  // View::make('x')
            '/\bmarkdown\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/',    // Mail::markdown('x')
        ];

        foreach ([base_path('app'), base_path('routes')] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                $source = file_get_contents($file->getPathname());
                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $source, $matches)) {
                        foreach ($matches[1] as $name) {
                            if (! isset($available[$name])) {
                                $missing[] = $name.'  <- '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                            }
                        }
                    }
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($missing)),
            "These view() references point at Blade files that do not exist:\n  ".implode("\n  ", array_unique($missing))
        );
    }

    /**
     * Every GET route must have a callable controller action (no missing methods)
     * and must not blow up. Parameterised routes are probed with a real ID where
     * one exists.
     */
    public function test_every_get_route_resolves_to_a_callable_action(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $action = $route->getActionName();

            // Closures and controller actions are both fine; we only care that a
            // "Controller@method" pair actually has that method.
            if (is_string($action) && str_contains($action, '@')) {
                [$class, $method] = explode('@', $action, 2);
                if (! class_exists($class)) {
                    $missing[] = "{$action} (class does not exist)";

                    continue;
                }
                if (! method_exists($class, $method)) {
                    $missing[] = $route->uri()."  ->  {$action}";
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($missing)),
            "These routes point at controller methods that do not exist:\n  ".implode("\n  ", array_unique($missing))
        );
    }

    /**
     * Smoke test: every parameterless GET route returns a non-5xx response for a
     * signed-in super admin. This is what caught the 13 missing views, the
     * pagination bug on the exam review page and the broken invoice page.
     */
    public function test_parameterless_get_routes_do_not_error_for_an_admin(): void
    {
        $admin = $this->makeSuperAdmin();

        $failures = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = $route->uri();
            if (str_contains($uri, '{') || str_starts_with($uri, '_')) {
                continue;
            }

            $path = '/'.ltrim($uri, '/');
            $checked++;

            try {
                $response = $this->actingAs($admin)->get($path);
                if ($response->getStatusCode() >= 500) {
                    $failures[] = $path.' -> '.$response->getStatusCode();
                }
            } catch (\Throwable $e) {
                $failures[] = $path.' -> '.get_class($e).': '.substr($e->getMessage(), 0, 90);
            }
        }

        $this->assertGreaterThan(50, $checked, 'Expected to probe a meaningful number of routes.');

        $this->assertSame(
            [],
            $failures,
            "These GET routes returned a server error:\n  ".implode("\n  ", $failures)
        );
    }

    /**
     * The invoice line items must round-trip as an array. They used to be written
     * with json_encode() into an array-cast column, which double-encoded them and
     * made $invoice->items a string — breaking count() on the invoice page.
     */
    public function test_invoice_items_round_trip_as_an_array(): void
    {
        $invoice = Invoice::create([
            'student_id' => Student::factory()->create()->id,
            'invoice_number' => 'INV-TEST-0001',
            'amount' => 5000,
            'due_date' => now()->addWeek(),
            'status' => 'pending',
            'items' => [['description' => 'Tuition Fee', 'amount' => 5000]],
        ]);

        $fresh = $invoice->fresh();

        $this->assertIsArray($fresh->items, 'Invoice::items must hydrate as an array.');
        $this->assertSame('Tuition Fee', $fresh->items[0]['description']);

        // The stored JSON must not itself be a quoted JSON string.
        $raw = $fresh->getRawOriginal('items');
        $this->assertStringStartsWith('[', $raw, 'items should be stored as a JSON array, not a JSON-encoded string.');
    }

    /**
     * Exam exposes `title`; several views/exports read `name`. The accessor keeps
     * those headings populated instead of silently rendering blank.
     */
    public function test_exam_exposes_name_as_an_alias_for_title(): void
    {
        $exam = Exam::create([
            'title' => 'Midterm Physics',
            'type' => 'mcq',
            'total_marks' => 100,
            'pass_marks' => 40,
            'status' => 'draft',
        ]);

        $this->assertSame('Midterm Physics', $exam->name);
        $this->assertSame('Midterm Physics', $exam->title);
    }

    /**
     * Write routes (POST/PUT/PATCH/DELETE) must not be reachable by a user who
     * should not have access, and must not blow up.
     *
     * An EMPTY payload is sent, so no write route should ever answer 200 — every
     * one of them either requires input (302/422), is forbidden (403), or does
     * not exist for this actor (404). A 200 here means an unguarded action, and a
     * 5xx means an unhandled error.
     */
    public function test_write_routes_are_not_reachable_without_authorisation(): void
    {
        $actors = [
            'guest' => null,
            'student' => $this->makeUserWithRole('student', 'student-write-probe@example.com'),
            'teacher' => $this->makeUserWithRole('teacher', 'teacher-write-probe@example.com'),
        ];

        $targets = [];
        foreach (Route::getRoutes() as $route) {
            $methods = array_values(array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']));
            if (! $methods) {
                continue;
            }
            $uri = $route->uri();
            if (str_starts_with($uri, '_')) {
                continue;
            }
            $path = '/'.ltrim($uri, '/');
            foreach ($route->parameterNames() as $param) {
                $path = str_replace('{'.$param.'}', $this->sampleIdFor($param), $path);
            }
            $targets[] = [$methods[0], $path];
        }

        $this->assertGreaterThan(50, count($targets), 'Expected a meaningful number of write routes.');

        $failures = [];

        foreach ($actors as $label => $actor) {
            foreach ($targets as [$method, $path]) {
                try {
                    $request = $actor ? $this->actingAs($actor) : $this;
                    $response = $request->json($method, $path, []);

                    if ($response->getStatusCode() === 200) {
                        $failures[] = "[{$label}] {$method} {$path} -> 200 (action ran with an empty payload)";
                    } elseif ($response->getStatusCode() >= 500) {
                        // 503 is an intentional "feature not configured" refusal.
                        if ($response->getStatusCode() !== 503) {
                            $failures[] = "[{$label}] {$method} {$path} -> {$response->getStatusCode()}";
                        }
                    }
                } catch (\Throwable $e) {
                    $failures[] = "[{$label}] {$method} {$path} -> ".get_class($e).': '.substr($e->getMessage(), 0, 70);
                }
            }
        }

        $this->assertSame(
            [],
            $failures,
            "These write routes were reachable or errored:\n  ".implode("\n  ", $failures)
        );
    }

    /**
     * Themed checkboxes are applied globally from resources/css/app.css rather
     * than per-view, so two invariants must hold:
     *
     *  1. the rules opt out of the native control (appearance: none), and
     *  2. they EXCLUDE .sr-only — components/ui/switch.blade.php renders a
     *     visually-hidden checkbox and paints the switch itself, so styling it
     *     would make the hidden input visible and silently break every toggle.
     *
     * Both are easy to lose in a refactor and impossible to notice in a diff.
     */
    public function test_themed_checkbox_styles_are_global_and_exclude_sr_only(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(
            'input[type=\'checkbox\']:not(.sr-only)',
            $css,
            'Themed checkbox rules must target input[type=checkbox]:not(.sr-only).'
        );

        $this->assertStringContainsString(
            'appearance: none',
            $css,
            'Themed checkboxes must set appearance: none, otherwise the OS control renders instead.'
        );

        // The switch component depends on this exclusion.
        $switch = file_get_contents(resource_path('views/components/ui/switch.blade.php'));
        if (str_contains($switch, 'sr-only')) {
            $this->assertStringContainsString(
                'not(.sr-only)',
                $css,
                'ui/switch.blade.php relies on a .sr-only checkbox; the global checkbox rules MUST exclude it.'
            );
        }
    }

    private function sampleIdFor(string $param): string
    {
        $model = match ($param) {
            'user' => User::class,
            'role' => Role::class,
            'student' => Student::class,
            'teacher' => Teacher::class,
            'course' => Course::class,
            'batch' => Batch::class,
            'payment' => Payment::class,
            'exam' => Exam::class,
            'salary' => TeacherSalary::class,
            'inventory' => InventoryItem::class,
            'material' => CourseMaterial::class,
            'announcement' => Announcement::class,
            'submission' => CqSubmission::class,
            'attempt' => ExamAttempt::class,
            'result' => ExamResult::class,
            'invoice' => Invoice::class,
            'export' => ReportExport::class,
            'service' => Service::class,
            default => null,
        };

        if ($model && class_exists($model)) {
            $id = $model::query()->value('id');
            if ($id) {
                return (string) $id;
            }
        }

        return '1';
    }

    private function makeUserWithRole(string $slug, string $email): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);

        $user = User::factory()->create([
            'email' => $email,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function makeSuperAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );

        $user = User::factory()->create([
            'email' => 'integrity-admin@example.com',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }
}
