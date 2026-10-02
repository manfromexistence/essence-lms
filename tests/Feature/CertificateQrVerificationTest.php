<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateVerification;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\CqSubmission;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\VideoView;
use App\Services\StudentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the QR-code verification flow end to end.
 *
 * The point of the feature is that a holder of a printed certificate — or an
 * employer checking one — can prove it is genuine by scanning it. These assert
 * both halves of that: the code is on the certificate, and the page it opens
 * answers the question without disclosing anything it should not.
 */
class CertificateQrVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = $this->makeStudent();
        $this->certificate = $this->makeCertificate($this->student);
    }

    /*
    |--------------------------------------------------------------------------
    | Verification tokens
    |--------------------------------------------------------------------------
    */

    public function test_a_student_is_given_a_verification_token_on_creation(): void
    {
        $this->assertNotEmpty($this->student->verification_token);
    }

    public function test_the_token_is_long_enough_to_not_be_walked(): void
    {
        // Registration numbers are sequential; this must not be guessable.
        $this->assertSame(40, strlen($this->student->verification_token));
    }

    public function test_tokens_are_unique_across_students(): void
    {
        $tokens = collect(range(1, 25))
            ->map(fn () => Student::factory()->create()->verification_token);

        $this->assertCount(25, $tokens->unique());
    }

    public function test_an_existing_student_without_a_token_is_given_one_on_demand(): void
    {
        $legacy = Student::factory()->create(['verification_token' => null]);

        $token = $legacy->verificationUrl();

        $this->assertStringContainsString($legacy->fresh()->verification_token, $token);
    }

    /*
    |--------------------------------------------------------------------------
    | The public profile
    |--------------------------------------------------------------------------
    */

    public function test_the_public_profile_opens_for_a_valid_token(): void
    {
        $this->get('/verify/student/'.$this->student->verification_token)
            ->assertOk()
            ->assertSee('Verified student record')
            ->assertSee($this->student->registration_no);
    }

    public function test_an_unknown_token_is_a_404(): void
    {
        $this->get('/verify/student/'.Str::random(40))->assertNotFound();
    }

    public function test_the_profile_needs_no_login(): void
    {
        // It is reached by scanning a printed certificate in the street.
        $this->assertGuest();

        $this->get('/verify/student/'.$this->student->verification_token)->assertOk();
    }

    public function test_the_profile_shows_completed_courses(): void
    {
        $course = Course::factory()->active()->create(['name' => 'Advanced Web Development']);
        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'video_type' => 'youtube',
            'external_id' => 'dQw4w9WgXcQ',
            'order' => 1,
        ]);

        VideoView::create([
            'course_video_id' => $video->id,
            'student_id' => $this->student->id,
            'watched_seconds' => 600,
            'completed' => true,
        ]);

        CourseEnrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subMonth(),
        ]);

        $this->get('/verify/student/'.$this->student->verification_token)
            ->assertOk()
            ->assertSee('Advanced Web Development')
            ->assertSee('Coursework');
    }

    public function test_the_profile_shows_exam_performance(): void
    {
        $exam = Exam::create([
            'title' => 'Final Examination',
            'type' => 'mcq',
            'total_marks' => 100,
            'pass_marks' => 40,
            'status' => 'completed',
        ]);

        ExamResult::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'subject_name' => 'General',
            'obtained_marks' => 88,
            'total_marks' => 100,
            'marks' => 88,
            'grade' => 'A',
        ]);

        $this->get('/verify/student/'.$this->student->verification_token)
            ->assertOk()
            ->assertSee('Exam performance')
            ->assertSee('Final Examination')
            ->assertSee('88');
    }

    public function test_the_profile_lists_submitted_work(): void
    {
        $exam = Exam::create([
            'title' => 'Practical Assignment',
            'type' => 'cq',
            'total_marks' => 50,
            'pass_marks' => 20,
            'status' => 'completed',
        ]);

        CqSubmission::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'files' => [['path' => 'https://files.catbox.moe/abc.png', 'original_name' => 'answer.png']],
            'submitted_at' => now()->subDays(3),
        ]);

        $this->get('/verify/student/'.$this->student->verification_token)
            ->assertOk()
            ->assertSee('Submitted assignments')
            ->assertSee('Practical Assignment');
    }

    public function test_an_unevaluated_submission_is_not_reported_as_a_zero(): void
    {
        // A missing mark is not a zero, and showing it as one would
        // misrepresent the student on a page an employer may rely on.
        $exam = Exam::create([
            'title' => 'Ungraded Work',
            'type' => 'cq',
            'total_marks' => 50,
            'pass_marks' => 20,
            'status' => 'completed',
        ]);

        CqSubmission::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'files' => [['path' => 'https://files.catbox.moe/abc.png']],
            'submitted_at' => now(),
        ]);

        $this->get('/verify/student/'.$this->student->verification_token)
            ->assertOk()
            ->assertSee('Awaiting evaluation');
    }

    /*
    |--------------------------------------------------------------------------
    | Redaction
    |--------------------------------------------------------------------------
    */

    public function test_the_profile_never_discloses_personal_or_financial_details(): void
    {
        $this->student->forceFill([
            'phone' => '01712345678',
            'guardian_name' => 'Guardian Person',
            'guardian_phone' => '01812345678',
            'father_name' => 'Father Person',
            'mother_name' => 'Mother Person',
            'present_village' => 'Secret Village',
            'blood_group' => 'AB-',
            'religion' => 'Test Religion',
            'total_amount' => 50000,
            'due_amount' => 12345,
        ])->save();

        $response = $this->get('/verify/student/'.$this->student->verification_token)->assertOk();
        $body = $response->getContent();

        $mustNotAppear = [
            'phone number' => '01712345678',
            'guardian phone' => '01812345678',
            'guardian name' => 'Guardian Person',
            'father name' => 'Father Person',
            'mother name' => 'Mother Person',
            'address' => 'Secret Village',
            'blood group' => 'AB-',
            'religion' => 'Test Religion',
            'email' => $this->student->user->email,
        ];

        foreach ($mustNotAppear as $label => $value) {
            $this->assertStringNotContainsString($value, $body, "The profile leaked the {$label}.");
        }
    }

    public function test_fee_figures_are_not_in_the_public_payload(): void
    {
        // Checked as JSON keys rather than values: a zero balance serialises as
        // "0", which would collide with any count in the payload.
        $payload = app(StudentVerificationService::class)->profile($this->student);
        $encoded = json_encode($payload);

        foreach (['balance', 'due_amount', 'total_amount', 'paid_amount', 'guardian', 'father', 'mother', 'religion', 'blood_group', 'dob', 'phone'] as $key) {
            $this->assertStringNotContainsString('"'.$key.'"', $encoded, "The payload exposed {$key}.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | The QR code on the certificate
    |--------------------------------------------------------------------------
    */

    public function test_the_certificate_embeds_a_qr_code(): void
    {
        $html = $this->renderCertificate();

        $this->assertStringContainsString('data:image/png;base64,', $html);
    }

    public function test_the_qr_points_at_the_students_verification_profile(): void
    {
        $html = $this->renderCertificate();

        $this->assertStringContainsString('/verify/student/'.$this->student->verification_token, $html);
    }

    public function test_a_template_without_a_qr_element_still_gets_one(): void
    {
        // Existing certificates must not become unverifiable just because their
        // template predates the feature.
        $template = $this->certificate->template;
        $template->update(['layout_config' => [
            'elements' => [
                ['type' => 'text', 'content' => '{student_name}', 'x' => 50, 'y' => 50, 'width' => 1100],
            ],
            'background_opacity' => 0.6,
        ]]);

        $this->assertStringContainsString('data:image/png;base64,', $this->renderCertificate());
    }

    public function test_a_template_with_its_own_qr_element_does_not_get_a_second_one(): void
    {
        $template = $this->certificate->template;
        $template->update(['layout_config' => [
            'elements' => [
                [
                    'type' => 'image',
                    'imageField' => 'qr',
                    'x' => 10, 'y' => 10, 'width' => 120, 'height' => 120,
                ],
            ],
            'background_opacity' => 0.6,
        ]]);

        $html = $this->renderCertificate();

        $this->assertSame(
            1,
            substr_count($html, 'data:image/png;base64,'),
            'A template that positions its own QR must not receive an injected second one.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The verification audit log
    |--------------------------------------------------------------------------
    */

    public function test_scanning_a_qr_is_recorded(): void
    {
        $this->get('/verify/student/'.$this->student->verification_token)->assertOk();

        $this->assertDatabaseHas('certificate_verifications', [
            'certificate_id' => $this->certificate->id,
        ]);
    }

    public function test_the_audit_row_records_when_and_from_where(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('/verify/student/'.$this->student->verification_token)
            ->assertOk();

        $row = CertificateVerification::where('certificate_id', $this->certificate->id)->firstOrFail();

        $this->assertSame('203.0.113.9', $row->ip_address);
        $this->assertNotNull($row->verified_at);
    }

    public function test_verifying_a_certificate_by_code_is_also_recorded(): void
    {
        $this->get('/certificates/verify/'.$this->certificate->verification_code)->assertOk();

        $this->assertDatabaseHas('certificate_verifications', [
            'certificate_id' => $this->certificate->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Code entry as an alternative to scanning
    |--------------------------------------------------------------------------
    */

    public function test_a_certificate_code_can_be_entered_in_any_case(): void
    {
        // Codes are printed on paper; retyping them in the wrong case must work.
        $this->get('/certificates/verify/'.strtolower($this->certificate->verification_code))
            ->assertOk()
            ->assertSee($this->certificate->certificate_number);
    }

    public function test_an_unknown_certificate_code_reports_no_match(): void
    {
        $this->get('/certificates/verify/NOSUCHCODE999')
            ->assertOk()
            ->assertSee('No certificate was found');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function renderCertificate(): string
    {
        return view('certificates.show', [
            'certificate' => $this->certificate->fresh(['student.user', 'course', 'issuer', 'template']),
        ])->render();
    }

    private function makeStudent(): Student
    {
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $user = User::factory()->create([
            'name' => 'Publicly Visible Name',
            'email' => 'qr.student@example.test',
            'is_active' => true,
        ]);

        return Student::factory()->create([
            'user_id' => $user->id,
            'name_bn' => 'Publicly Visible Name',
            'admission_status' => 'approved',
            'status' => 'active',
        ]);
    }

    private function makeCertificate(Student $student): Certificate
    {
        $template = CertificateTemplate::firstOrCreate(
            ['type' => 'course_completion', 'is_default' => true],
            ['name' => 'Default', 'is_active' => true, 'width' => 1200, 'height' => 900],
        );

        $course = Course::factory()->active()->create(['name' => 'Web Development']);

        $issuer = User::factory()->create();

        return Certificate::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'template_id' => $template->id,
            'certificate_number' => 'DII-202609-00001-001',
            'verification_code' => strtoupper(Str::random(12)),
            'issued_at' => now()->subMonth(),
            'issued_by' => $issuer->id,
            'grade' => 'A',
            'status' => 'active',
        ]);
    }
}
