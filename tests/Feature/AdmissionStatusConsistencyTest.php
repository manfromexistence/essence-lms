<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards against the "status shows different things on different pages" bug:
 * admission_status must be the single source of truth and must render
 * identically in All Students, Admission Applications, and Batch Assignment.
 */
class AdmissionStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
    }

    private function createAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_online_admission_stays_pending_on_all_pages(): void
    {
        $course = Course::factory()->active()->create(['delivery_mode' => 'online']);

        $this->post('/admission', [
            'name_bn' => 'Pending Applicant',
            'email' => 'pending.applicant@example.com',
            'phone' => '01911009999',
            'admission_mode' => 'online',
            'course_id' => $course->id,
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
        ])->assertRedirect('/login');

        // Single source of truth: admission_status AND status both pending,
        // and the user is NOT active (cannot log in yet).
        $student = Student::where('email', 'pending.applicant@example.com')
            ->orWhereHas('user', fn ($q) => $q->where('email', 'pending.applicant@example.com'))
            ->first();
        $this->assertNotNull($student);
        $this->assertSame('pending', $student->admission_status);
        $this->assertSame('pending', $student->status);
        $this->assertFalse((bool) $student->user->is_active);

        $admin = $this->createAdmin();

        // All Students page: badge says Pending (not green Active).
        $this->actingAs($admin)->get('/dashboard/students')
            ->assertSuccessful()
            ->assertSee('Pending Applicant')
            ->assertSee('Pending');

        // Admission Applications page: same badge.
        $this->actingAs($admin)->get('/dashboard/students/admission-form')
            ->assertSuccessful()
            ->assertSee('Pending Applicant')
            ->assertSee('Pending');

        // Batch Assignment page: same badge (no hardcoded Active).
        $this->actingAs($admin)->get('/dashboard/students/batch-assignment')
            ->assertSuccessful()
            ->assertSee('Pending Applicant')
            ->assertSee('Pending');
    }

    public function test_approving_admission_activates_account_and_shows_admitted_everywhere(): void
    {
        $course = Course::factory()->active()->create(['delivery_mode' => 'offline']);
        $batch = Batch::factory()->create([
            'name' => 'Offline Batch A',
            'course_id' => $course->id,
        ]);

        $this->post('/admission', [
            'name_bn' => 'Approve Me',
            'email' => 'approve.me@example.com',
            'phone' => '01911008888',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
        ]);

        $student = Student::whereHas('user', fn ($q) => $q->where('email', 'approve.me@example.com'))->first();
        $admin = $this->createAdmin();

        // Approve via the admission-form action.
        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", ['admission_status' => 'approved'])
            ->assertRedirect();

        $student->refresh();
        $this->assertSame('approved', $student->admission_status);
        $this->assertSame('active', $student->status);
        $this->assertTrue((bool) $student->user->is_active);

        // All Students shows Admitted.
        $this->actingAs($admin)->get('/dashboard/students')
            ->assertSuccessful()
            ->assertSee('Approve Me')
            ->assertSee('Admitted');

        // Admission Applications shows Admitted.
        $this->actingAs($admin)->get('/dashboard/students/admission-form')
            ->assertSuccessful()
            ->assertSee('Approve Me')
            ->assertSee('Admitted');
    }

    public function test_rejecting_admission_blocks_login_and_shows_rejected(): void
    {
        $course = Course::factory()->active()->create(['delivery_mode' => 'offline']);

        $this->post('/admission', [
            'name_bn' => 'Reject Me',
            'email' => 'reject.me@example.com',
            'phone' => '01911007777',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
        ]);

        $student = Student::whereHas('user', fn ($q) => $q->where('email', 'reject.me@example.com'))->first();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", ['admission_status' => 'rejected'])
            ->assertRedirect();

        $student->refresh();
        $this->assertSame('rejected', $student->admission_status);
        $this->assertSame('rejected', $student->status);
        $this->assertFalse((bool) $student->user->is_active);

        // Rejected student cannot log in.
        $this->post('/login', [
            'email' => 'reject.me@example.com',
            'password' => 'some-password',
        ])->assertSessionHasErrors('email');

        // All Students shows Rejected.
        $this->actingAs($admin)->get('/dashboard/students')
            ->assertSuccessful()
            ->assertSee('Reject Me')
            ->assertSee('Rejected');
    }

    public function test_admin_assigned_batch_sets_both_fields_and_activates_user(): void
    {
        $course = Course::factory()->active()->create(['delivery_mode' => 'offline']);
        $batch = Batch::factory()->create(['course_id' => $course->id]);
        $user = User::factory()->create(['is_active' => false]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'admission_status' => 'pending',
            'status' => 'pending',
            'admission_mode' => 'offline',
        ]);

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/dashboard/students/batch-assignment/update', [
                'student_id' => $student->id,
                'batch_id' => $batch->id,
            ])
            ->assertRedirect();

        $student->refresh();
        $this->assertSame($batch->id, $student->batch_id);
        $this->assertSame('approved', $student->admission_status);
        $this->assertSame('active', $student->status);
        $this->assertTrue((bool) $student->user->is_active);
    }
}
