<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

/**
 * Verifies the student credential flow the support ticket is about:
 *  - a public applicant starts inactive;
 *  - approval activates the account AND lets them recover a password via a reset link;
 *  - rejection does NOT grant login.
 */
class StudentLoginCredentialFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function createPendingStudent(string $email = 'applicant@example.com'): Student
    {
        $user = User::factory()->create([
            'name' => 'Applicant',
            'email' => $email,
            'password' => Hash::make('old-password'),
            'is_active' => false,
            'must_change_password' => true,
        ]);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $user->roles()->attach($studentRole);

        return Student::factory()->create([
            'user_id' => $user->id,
            'name_bn' => 'Applicant',
            'phone' => '01711000001',
            'admission_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    public function test_pending_student_cannot_log_in(): void
    {
        $this->createPendingStudent();

        // is_active=false -> Auth::attempt fails -> redirected back with errors (302), no session.
        $this->post('/login', ['email' => 'applicant@example.com', 'password' => 'old-password'])
            ->assertStatus(302);
        $this->assertGuest();
    }

    public function test_approve_sends_reset_link_and_activates_account(): void
    {
        Notification::fake();

        $student = $this->createPendingStudent();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", [
                'admission_status' => 'approved',
            ])->assertRedirect();

        $student->refresh();
        $student->user->refresh();

        $this->assertTrue((bool) $student->user->is_active);
        $this->assertSame('approved', $student->admission_status);
        $this->assertSame('active', $student->status);

        // A real, signed password-reset link must be queued for the student.
        Notification::assertSentTo(
            $student->user,
            ResetPassword::class
        );
    }

    public function test_reject_does_not_send_reset_link_and_keeps_inactive(): void
    {
        Notification::fake();

        $student = $this->createPendingStudent();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", [
                'admission_status' => 'rejected',
            ])->assertRedirect();

        $student->refresh();
        $student->user->refresh();

        $this->assertFalse((bool) $student->user->is_active);
        $this->assertSame('rejected', $student->admission_status);
        $this->assertSame('rejected', $student->status); // status mirrors rejected

        Notification::assertNotSentTo($student->user, ResetPassword::class);
    }
}
