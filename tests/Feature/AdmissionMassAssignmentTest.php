<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for flaws found in an audit of the admission flow.
 *
 * The public application form is unauthenticated, so any field it accepts is
 * attacker-controlled. These assert that the fields which would grant course
 * entitlement, authorise a fee ledger, or edit the public record are refused.
 */
class AdmissionMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
    }

    private function apply(array $overrides = [])
    {
        $course = \App\Models\Course::factory()->active()->create([
            'delivery_mode' => 'offline',
            'price' => 5000,
        ]);

        return $this->post('/admission', array_merge([
            'name_bn' => 'Legit Applicant',
            'email' => 'applicant@example.test',
            'phone' => '01700000000',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
        ], $overrides));
    }

    public function test_a_normal_application_is_accepted(): void
    {
        $this->apply()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('students', ['name_bn' => 'Legit Applicant']);
    }

    /**
     * The core flaw: batch_id decides course entitlement, and approval grants
     * it. Accepting it from an anonymous request let an applicant pick their
     * own batch and walk out with paid course access.
     */
    public function test_an_applicant_cannot_self_assign_a_batch(): void
    {
        $batch = \App\Models\Batch::factory()->create();

        $this->apply(['batch_id' => $batch->id])->assertSessionHasErrors('batch_id');

        $this->assertNull(Student::where('name_bn', 'Legit Applicant')->first()?->batch_id);
    }

    public function test_an_applicant_cannot_authorise_their_own_fee_ledger(): void
    {
        $this->apply([
            'total_amount' => 0,
            'paid_amount' => 5000,
        ])->assertSessionHasErrors(['total_amount', 'paid_amount']);

        // The request is rejected outright, so no applicant record exists at all
        // — which is a stronger guarantee than "the values were discarded".
        $this->assertNull(Student::where('name_bn', 'Legit Applicant')->first());
    }

    public function test_an_applicant_cannot_feature_themselves_on_the_homepage(): void
    {
        $this->apply(['featured' => 1])->assertSessionHasErrors('featured');

        $this->assertFalse((bool) Student::where('name_bn', 'Legit Applicant')->first()?->featured);
    }

    /**
     * verification_token is the public handle a certificate QR code resolves to.
     * Letting an applicant choose it would let them mint a URL that resolves to
     * their own record.
     */
    public function test_an_applicant_cannot_choose_their_own_verification_token(): void
    {
        $this->apply(['verification_token' => 'attacker-chosen-token'])
            ->assertSessionHasErrors('verification_token');

        $token = Student::where('name_bn', 'Legit Applicant')->first()?->verification_token;

        $this->assertNotSame('attacker-chosen-token', $token);
    }

    public function test_an_applicant_cannot_self_approve(): void
    {
        $this->apply(['admission_status' => 'approved', 'status' => 'active'])
            ->assertSessionHasErrors(['admission_status', 'status']);

        $this->assertNull(Student::where('name_bn', 'Legit Applicant')->first());
    }

    public function test_an_applicant_cannot_bind_the_record_to_an_existing_user(): void
    {
        $victim = User::factory()->create();

        $this->apply(['user_id' => $victim->id])->assertSessionHasErrors('user_id');

        $student = Student::where('name_bn', 'Legit Applicant')->first();

        $this->assertNotSame($victim->id, $student?->user_id);
    }

    /**
     * The whitelist and the `prohibited` rules are independent guards. This
     * pins the whitelist's behaviour specifically, in case the rules are ever
     * loosened again.
     */
    public function test_the_applicant_record_is_always_created_pending(): void
    {
        $this->apply();

        $student = Student::where('name_bn', 'Legit Applicant')->firstOrFail();

        $this->assertSame('pending', $student->admission_status);
        $this->assertSame('pending', $student->status);
        $this->assertFalse($student->user->is_active);
        $this->assertNotEmpty($student->verification_token, 'The system must mint the token, not accept one.');
    }
}