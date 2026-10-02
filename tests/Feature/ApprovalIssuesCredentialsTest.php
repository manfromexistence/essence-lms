<?php

namespace Tests\Feature;

use App\Jobs\SendEmailJob;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Guards the rule that approving a student through *any* route must hand them
 * working credentials.
 *
 * This existed as a live bug: bulk batch assignment and payment approval both
 * set admission_status = approved and is_active = true, which activates the
 * account, but neither issued a password. The applicant was left approved and
 * unable to log in.
 */
class ApprovalIssuesCredentialsTest extends TestCase
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

    /**
     * A student in the state a public applicant is left in: pending, inactive,
     * and holding a password nobody knows.
     */
    private function pendingApplicant(array $overrides = []): Student
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->roles()->attach(Role::where('slug', 'student')->value('id'));

        return Student::factory()->create(array_merge([
            'user_id' => $user->id,
            'admission_status' => 'pending',
            'status' => 'pending',
        ], $overrides));
    }

    private function originalPassword(Student $student): string
    {
        return 'Original!Pass123';
    }

    /*
    |--------------------------------------------------------------------------
    | The Approve button
    |--------------------------------------------------------------------------
    */

    public function test_the_approve_button_issues_credentials(): void
    {
        Bus::fake();

        $student = $this->pendingApplicant();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", ['admission_status' => 'approved'])
            ->assertRedirect();

        $student->refresh();

        $this->assertSame('approved', $student->admission_status);
        $this->assertTrue($student->user->is_active, 'Approval must activate the account.');

        Bus::assertDispatched(SendEmailJob::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk batch assignment — the gap this guards
    |--------------------------------------------------------------------------
    */

    public function test_bulk_batch_assignment_issues_credentials(): void
    {
        Bus::fake();

        $batch = Batch::factory()->create();
        $students = collect(range(1, 3))->map(fn () => $this->pendingApplicant());

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/dashboard/students/batch-assignment/bulk', [
                'student_ids' => $students->pluck('id')->all(),
                'batch_id' => $batch->id,
            ])
            ->assertRedirect();

        foreach ($students as $student) {
            $student->refresh();

            $this->assertSame('approved', $student->admission_status, 'Bulk assignment must approve the student.');
            $this->assertTrue($student->user->is_active, 'Bulk assignment must activate the account.');
            $this->assertTrue(
                $student->user->must_change_password,
                'Bulk-approved students must be told to set their own password.'
            );
        }

        Bus::assertDispatched(SendEmailJob::class, 3);
    }

    public function test_bulk_assignment_does_not_reset_an_already_approved_student(): void
    {
        Bus::fake();

        $student = $this->pendingApplicant(['admission_status' => 'approved', 'status' => 'active']);
        $student->user->update(['is_active' => true, 'password' => Hash::make($this->originalPassword($student))]);

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/dashboard/students/batch-assignment/bulk', [
                'student_ids' => [$student->id],
                'batch_id' => Batch::factory()->create()->id,
            ])
            ->assertRedirect();

        $this->assertTrue(
            Hash::check($this->originalPassword($student), $student->user->fresh()->password),
            'Re-assigning a batch must not silently reset a password the student may have changed.'
        );

        Bus::assertNothingDispatched();
    }

    /*
    |--------------------------------------------------------------------------
    | Single batch assignment
    |--------------------------------------------------------------------------
    */

    public function test_single_batch_assignment_issues_credentials_once(): void
    {
        Bus::fake();

        $batch = Batch::factory()->create();
        $student = $this->pendingApplicant();

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/dashboard/students/batch-assignment/update', [
                'student_id' => $student->id,
                'batch_id' => $batch->id,
            ])
            ->assertRedirect();

        Bus::assertDispatched(SendEmailJob::class);

        Bus::fake();

        // Re-assigning the same student must not reissue.
        $this->actingAs($admin)
            ->post('/dashboard/students/batch-assignment/update', [
                'student_id' => $student->id,
                'batch_id' => $batch->id,
            ])
            ->assertRedirect();

        Bus::assertNothingDispatched();
    }

    /*
    |--------------------------------------------------------------------------
    | Payment approval — the other gap
    |--------------------------------------------------------------------------
    */

    public function test_approving_a_payment_issues_credentials(): void
    {
        Bus::fake();

        $student = $this->pendingApplicant();
        $course = Course::factory()->active()->create(['price' => 5000]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'bkash',
            'transaction_id' => 'TXN-CREDS-001',
            'amount' => 5000,
            'status' => Payment::STATUS_PENDING,
        ]);

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/admin/payment-review/{$payment->id}/approve", ['admin_notes' => 'verified'])
            ->assertRedirect();

        $student->refresh();

        $this->assertSame('approved', $student->admission_status);
        $this->assertTrue($student->user->is_active);

        Bus::assertDispatched(SendEmailJob::class);
    }

    public function test_a_second_payment_does_not_reset_the_password(): void
    {
        Bus::fake();

        $student = $this->pendingApplicant();
        $student->user->update(['password' => Hash::make($this->originalPassword($student))]);

        $admin = $this->createAdmin();

        // First approval transitions pending -> approved and issues credentials.
        $first = Payment::create([
            'student_id' => $student->id,
            'course_id' => Course::factory()->active()->create(['price' => 5000])->id,
            'payment_method' => 'bkash',
            'transaction_id' => 'TXN-CREDS-A',
            'amount' => 5000,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->post("/admin/payment-review/{$first->id}/approve")->assertRedirect();
        Bus::assertDispatched(SendEmailJob::class);

        // The student signs in and sets their own password.
        $student->refresh();
        $student->user->update([
            'password' => Hash::make($this->originalPassword($student)),
            'must_change_password' => false,
        ]);

        Bus::fake();

        // A second, unrelated payment must not hand them a new password.
        $second = Payment::create([
            'student_id' => $student->id,
            'course_id' => Course::factory()->active()->create(['price' => 5000])->id,
            'payment_method' => 'bkash',
            'transaction_id' => 'TXN-CREDS-B',
            'amount' => 5000,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->post("/admin/payment-review/{$second->id}/approve")->assertRedirect();

        $this->assertTrue(
            Hash::check($this->originalPassword($student), $student->user->fresh()->password),
            'Approving a second payment must not reset a password the student has since changed.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rejection
    |--------------------------------------------------------------------------
    */

    public function test_rejecting_keeps_the_account_locked_and_sends_nothing(): void
    {
        Bus::fake();

        $student = $this->pendingApplicant();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/students/{$student->id}/admission-status", ['admission_status' => 'rejected'])
            ->assertRedirect();

        $student->refresh();

        $this->assertSame('rejected', $student->admission_status);
        $this->assertFalse($student->user->is_active);

        Bus::assertNothingDispatched();
    }

    /*
    |--------------------------------------------------------------------------
    | Re-sending
    |--------------------------------------------------------------------------
    */

    public function test_an_admin_can_reissue_credentials_from_the_admissions_page(): void
    {
        Bus::fake();

        $student = $this->pendingApplicant(['admission_status' => 'approved', 'status' => 'active']);
        $student->user->update(['is_active' => true]);

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post("/dashboard/email/students/{$student->id}/resend-credentials")
            ->assertRedirect();

        Bus::assertDispatched(SendEmailJob::class);
    }

    public function test_the_admissions_page_offers_a_resend_control_for_approved_students(): void
    {
        $student = $this->pendingApplicant(['admission_status' => 'approved', 'status' => 'active']);
        $student->user->update(['is_active' => true]);

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/dashboard/students/admission-form')
            ->assertOk()
            ->assertSee('Resend login');
    }
}
