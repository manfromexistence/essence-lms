<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

/**
 * Verifies the student credential flow:
 *  - a public applicant starts inactive and submits NO password;
 *  - approval generates a password, activates the account, forces a password
 *    change on first login, and emails the credentials;
 *  - rejection does NOT grant login.
 */
class StudentLoginCredentialFlowTest extends TestCase
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

    private function createPendingStudent(string $email = 'applicant@example.com'): Student
    {
        $user = User::factory()->create([
            'name' => 'Applicant',
            'email' => $email,
            'password' => Hash::make('old-password'),
            'is_active' => false,
            'must_change_password' => false,
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

    public function test_approve_activates_account_without_reset_link(): void
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

        // No reset link: the student already has a known password.
        Notification::assertNotSentTo($student->user, ResetPassword::class);
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

    public function test_public_admission_no_longer_accepts_an_applicant_password(): void
    {
        $course = Course::factory()->create(['status' => 'active', 'delivery_mode' => 'online']);

        // Submitting a password is ignored — the field is not part of the form
        // and the applicant must not control their credentials.
        $this->post('/admission', [
            'name_bn' => 'No Password Applicant',
            'email' => 'no.password@example.com',
            'phone' => '01911006600',
            'admission_mode' => 'online',
            'course_id' => $course->id,
            'password' => 'AttackerChosen-123',
            'password_confirmation' => 'AttackerChosen-123',
        ])->assertRedirect('/login');

        $student = Student::whereHas('user', fn ($q) => $q->where('email', 'no.password@example.com'))->first();
        $this->assertNotNull($student);

        // The applicant cannot log in yet, and definitely not with that password.
        $this->assertFalse((bool) $student->user->is_active);
        $this->assertFalse(Hash::check('AttackerChosen-123', $student->user->password));
    }

    public function test_approval_generates_and_emails_credentials_then_forces_change(): void
    {
        Notification::fake();
        Bus::fake();

        $course = Course::factory()->create(['status' => 'active', 'delivery_mode' => 'online', 'name' => 'Credential Course']);
        $this->post('/admission', [
            'name_bn' => 'Credential Applicant',
            'email' => 'credential.applicant@example.com',
            'phone' => '01911008800',
            'admission_mode' => 'online',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        $student = Student::whereHas('user', fn ($q) => $q->where('email', 'credential.applicant@example.com'))->first();
        $this->assertNotNull($student);
        $this->assertFalse((bool) $student->user->is_active);

        $admin = $this->createAdmin();
        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", [
                'admission_status' => 'approved',
            ])->assertRedirect();

        $student->refresh();
        $student->user->refresh();

        $this->assertTrue((bool) $student->user->is_active);
        $this->assertTrue((bool) $student->user->must_change_password, 'first login must force a password change');

        // A credential email was queued (SendEmailJob implements ShouldQueue).
        Bus::assertDispatched(\App\Jobs\SendEmailJob::class);
    }

    public function test_generated_password_can_log_in_and_is_changed_after_first_login(): void
    {
        Notification::fake();

        $student = $this->createPendingStudent('forced.change@example.com');
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", ['admission_status' => 'approved'])
            ->assertRedirect();

        // Grab the generated plaintext password the way the email would carry it.
        $service = app(\App\Services\StudentCredentialService::class);
        $password = $service->issueFor($student->fresh());
        $this->assertNotNull($password);

        $student->user->refresh();

        // The generated password authenticates.
        $this->post('/login', [
            'email' => $student->user->email,
            'password' => $password,
        ])->assertRedirect();
        $this->assertAuthenticatedAs($student->user);

        // Because must_change_password is set, AuthController redirects to the
        // change-password page and the user can set a new private password.
        $this->get('/change-password')->assertOk();

        $newPassword = 'MyPrivatePassword-9876';
        $this->put('/change-password', [
            'current_password' => $password,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect();

        $student->user->refresh();
        $this->assertFalse((bool) $student->user->must_change_password);
        $this->assertTrue(Hash::check($newPassword, $student->user->password));
    }
}
