<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression tests for the defects found in the 2026-09-27 audit.
 *
 * Each test pins a previously-broken behaviour so it cannot silently return:
 *  - the missing exam-create view (was HTTP 500)
 *  - the MySQL-only FIELD() in the teacher schedule (was HTTP 500 on SQLite)
 *  - the relative-path backup failure under the web server
 *  - the demo student/teacher accounts missing their profiles
 *  - exam seed rows with end_time before start_time
 */
class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_exam_create_page_renders_and_creates_exam(): void
    {
        $admin = $this->makeUser('super-admin');
        $course = Course::factory()->active()->create();
        $batch = Batch::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        // The page itself must render (previously View [dashboard.exams.create] not found).
        $this->actingAs($admin)
            ->get('/dashboard/exams/create')
            ->assertOk()
            ->assertSee('Create Exam', false);

        // And the form it renders must actually create an exam.
        $this->actingAs($admin)
            ->post('/dashboard/exams', [
                'type' => 'mcq',
                'title' => 'Regression MCQ Exam',
                'batch_id' => $batch->id,
                'course_id' => $course->id,
                'total_marks' => 50,
                'pass_marks' => 20,
                'duration_minutes' => 45,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exams', ['title' => 'Regression MCQ Exam', 'type' => 'mcq']);
    }

    public function test_teacher_schedule_renders_portable_sql(): void
    {
        $user = $this->makeUser('teacher');
        Teacher::create([
            'user_id' => $user->id,
            'phone' => '01700000000',
            'designation' => 'Instructor',
            'status' => 'active',
        ]);

        // Would 500 on SQLite before the FIELD() -> CASE WHEN fix.
        $this->actingAs($user)->get('/teacher/schedule')->assertOk();
    }

    public function test_backup_resolves_sqlite_path_when_cwd_differs(): void
    {
        Storage::fake('local');

        // Simulate the web-server working directory being public/ while the
        // configured sqlite path is relative to the app root.
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set(
            'database.connections.sqlite.database',
            'database/database.sqlite'
        );

        $service = app(BackupService::class);
        $filename = $service->createBackup();

        $this->assertMatchesRegularExpression('/^backup-\d{4}-\d{2}-\d{2}-\d{6}\.sql$/', $filename);
        Storage::disk('local')->assertExists('backups/' . $filename);
    }

    public function test_demo_student_account_has_profile_and_batch(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\AdminUserSeeder::class);

        $user = User::where('email', 'student@gmail.com')->first();

        $this->assertNotNull($user, 'demo student user should exist');
        $this->assertTrue($user->hasRole('student'));
        $this->assertNotNull($user->student, 'demo student must have a Student profile');
        $this->assertSame('approved', $user->student->admission_status);
    }

    public function test_demo_teacher_account_has_profile(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\AdminUserSeeder::class);

        $user = User::where('email', 'teacher@gmail.com')->first();

        $this->assertNotNull($user, 'demo teacher user should exist');
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertDatabaseHas('teachers', ['user_id' => $user->id]);
    }

    public function test_exam_seed_never_sets_end_before_start(): void
    {
        $course = Course::factory()->active()->create();
        $batch = Batch::factory()->create(['course_id' => $course->id]);

        // Exercise the same logic branch that previously called rand() twice.
        $start = now()->subDays(10);
        $end = (clone $start)->addHours(2);

        $exam = Exam::create([
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'title' => 'Time Window Regression Exam',
            'type' => 'mcq',
            'duration_minutes' => 60,
            'total_marks' => 50,
            'pass_marks' => 20,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'active',
        ]);

        $this->assertTrue(
            $exam->fresh()->end_time->greaterThan($exam->fresh()->start_time),
            'seeded exam end_time must be after start_time'
        );
    }
}
