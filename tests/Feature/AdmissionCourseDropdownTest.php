<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the admission-form course dropdown.
 *
 * Bug: a newly uploaded course was created with status "draft" (or the admin
 * picked "draft"/"inactive"), the admission form only queried active() courses,
 * so the new course never appeared in the required "Course" dropdown and the
 * form could not be submitted — blocking every new admission.
 */
class AdmissionCourseDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
    }

    public function test_draft_course_appears_on_public_admission_form(): void
    {
        $draft = Course::factory()->create([
            'name' => 'Brand New Draft Course',
            'status' => 'draft',
            'delivery_mode' => 'offline',
        ]);

        $response = $this->get('/admission?mode=offline');

        $response->assertOk();
        $response->assertSee($draft->name, false);
    }

    public function test_active_and_draft_courses_appear_but_inactive_are_hidden(): void
    {
        $active = Course::factory()->create([
            'name' => 'Active Course Alpha',
            'status' => 'active',
            'delivery_mode' => 'online',
        ]);
        $draft = Course::factory()->create([
            'name' => 'Draft Course Beta',
            'status' => 'draft',
            'delivery_mode' => 'online',
        ]);
        $inactive = Course::factory()->create([
            'name' => 'Retired Course Gamma',
            'status' => 'inactive',
            'delivery_mode' => 'online',
        ]);

        $response = $this->get('/admission?mode=online');

        $response->assertOk();
        $response->assertSee($active->name, false);
        $response->assertSee($draft->name, false);
        $response->assertDontSee($inactive->name, false);
    }

    public function test_offline_admission_form_lists_newly_created_offline_course(): void
    {
        $course = Course::factory()->create([
            'name' => 'Fresh Offline Course',
            'status' => 'draft',
            'delivery_mode' => 'offline',
        ]);

        $response = $this->get('/admission/offline');

        $response->assertOk();
        $response->assertSee($course->name, false);
    }

    public function test_admission_can_be_submitted_for_a_draft_course(): void
    {
        // The applicant must not set a password anymore — approval issues one.
        $course = Course::factory()->create([
            'status' => 'draft',
            'delivery_mode' => 'online',
            'name' => 'Submittable Draft Course',
        ]);

        $this->post('/admission', [
            'name_bn' => 'Draft Applicant',
            'email' => 'draft.applicant@example.com',
            'phone' => '01911007700',
            'admission_mode' => 'online',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'draft.applicant@example.com']);
    }

    public function test_admission_still_succeeds_when_the_notification_email_throws(): void
    {
        // The confirmation email is a best-effort side effect. On Render the
        // queue runs "sync" (inline), so a mail/driver failure used to bubble
        // out of the controller and return HTTP 500 even though the student
        // row had already been written. The admission must survive that.
        $course = Course::factory()->create([
            'status' => 'active',
            'delivery_mode' => 'offline',
            'name' => 'Email Failure Course',
        ]);

        // Make the email transport blow up inside the (sync) job.
        \Illuminate\Support\Facades\Mail::shouldReceive('send')->never();

        $this->mock(\App\Services\BrevoEmailService::class, function ($mock) {
            $mock->shouldReceive('send')->andThrow(new \RuntimeException('SMTP is down'));
        });

        $this->post('/admission', [
            'name_bn' => 'Resilient Applicant',
            'email' => 'resilient.applicant@example.com',
            'phone' => '01911008811',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
        ])->assertRedirect('/login');

        // The application itself must be persisted regardless of mail failure.
        $this->assertDatabaseHas('users', ['email' => 'resilient.applicant@example.com']);
        $this->assertDatabaseHas('students', ['name_bn' => 'Resilient Applicant']);
    }

    public function test_enrollable_scope_includes_active_and_draft_only(): void
    {
        // A brand-content data migration seeds 4 base courses, so assert on the
        // delta rather than an absolute count.
        $baseline        = Course::count();
        $activeBaseline  = Course::where('status', 'active')->count();
        $enrollBaseline  = Course::enrollable()->count();

        Course::factory()->create(['status' => 'active']);
        Course::factory()->create(['status' => 'draft']);
        Course::factory()->create(['status' => 'inactive']);

        // active() grows by exactly 1 (the new active row).
        $this->assertSame($activeBaseline + 1, Course::where('status', 'active')->count());
        // enrollable() grows by 2 (the new active + draft rows), not 3.
        $this->assertSame($enrollBaseline + 2, Course::enrollable()->count());
        // Total grows by 3.
        $this->assertSame($baseline + 3, Course::count());
    }
}
