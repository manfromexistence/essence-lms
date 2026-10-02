<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CqSubmission;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\ExamTakingService;
use App\Services\ExamTimeValidator;
use App\Services\PaymentService;
use App\Services\StudentPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for flaws found in a second, hostile audit.
 */
class HostileAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->roles()->attach(Role::where('slug', 'student')->first());
        $this->student = Student::factory()->create(['user_id' => $this->user->id]);
    }

    private function exam(array $overrides = []): Exam
    {
        return Exam::create(array_merge([
            'title' => 'Paper', 'type' => 'mcq', 'total_marks' => 100,
            'pass_marks' => 40, 'duration_minutes' => 30, 'status' => 'active',
            'start_time' => now()->subHour(),
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | An unpublished exam must not be sitable
    |--------------------------------------------------------------------------
    */

    public function test_a_draft_exam_cannot_be_started_even_inside_its_time_window(): void
    {
        // canStartExam() consulted only start_time/end_time. Exam ids are
        // sequential, so a student could iterate ids and sit a draft paper — the
        // one still being written — and the attempt/result rows were written for
        // real.
        foreach (['draft', 'scheduled', 'completed', 'cancelled'] as $status) {
            $exam = $this->exam(['status' => $status]);

            $this->assertFalse(
                app(ExamTimeValidator::class)->canStartExam($exam),
                "An exam with status '{$status}' must not be startable."
            );
        }
    }

    public function test_an_active_exam_inside_its_window_is_startable(): void
    {
        $this->assertTrue(app(ExamTimeValidator::class)->canStartExam($this->exam()));
    }

    public function test_every_publishable_status_is_a_valid_database_value(): void
    {
        // The admin form offered 'live', which is not in the exams.status enum, and
        // omitted 'active', the only value isActive() accepts. So no exam could be
        // published through the UI at all.
        $allowed = ['draft', 'scheduled', 'active', 'completed', 'cancelled'];

        foreach ($allowed as $status) {
            $exam = $this->exam(['status' => $status]);
            $exam->refresh();

            $this->assertSame($status, $exam->status, "'{$status}' must survive a write.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | A marker's CQ mark must reach the student's result
    |--------------------------------------------------------------------------
    */

    public function test_a_graded_cq_submission_updates_the_student_result(): void
    {
        // saveReview() wrote only cq_submissions.marks. The only code that synced a
        // CQ mark into exam_results was evaluateCq(), which had zero callers, so
        // every CQ student stayed 0 / grade Pending forever: the marker saw 88 on
        // the review page while results, the mark sheet and hasPassed() all said
        // 0, and no CQ student could ever pass.
        $exam = $this->exam(['type' => 'cq', 'total_marks' => 100]);

        $submission = CqSubmission::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'answer_text' => 'An answer.',
            'status' => 'submitted',
        ]);

        app(ExamTakingService::class)
            ->evaluateCq($submission, 88.0, 'Well done', $this->user->id);

        $result = ExamResult::where('student_id', $this->student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $this->assertNotNull($result, 'Grading a CQ must create the result row.');
        $this->assertSame(88.0, (float) $result->obtained_marks);
        $this->assertSame('A+', $result->grade);
        $this->assertTrue($result->hasPassed());
    }

    public function test_a_cq_mark_above_the_paper_is_clamped_in_both_marks_and_grade(): void
    {
        // evaluateCq clamped obtained_marks but computed the grade from the
        // UNCLAMPED figure, so an over-award stored an A+ on a clamped row.
        $exam = $this->exam(['type' => 'cq', 'total_marks' => 50]);

        $submission = CqSubmission::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'answer_text' => 'An answer.',
        ]);

        app(ExamTakingService::class)
            ->evaluateCq($submission, 500.0, '', $this->user->id);

        $result = ExamResult::where('exam_id', $exam->id)->firstOrFail();

        $this->assertSame(50.0, (float) $result->obtained_marks);
        $this->assertSame('A+', ExamResult::gradeForPercentage(100.0));
    }

    /*
    |--------------------------------------------------------------------------
    | MCQ must render its options and score them
    |--------------------------------------------------------------------------
    */

    public function test_a_list_stored_option_set_is_readable_as_a_letter_map(): void
    {
        // The admin form and seeder store a plain list; the paper indexed it by
        // letter, so no radio inputs rendered at all.
        $question = new Question([
            'options' => ['A) apple', 'B) ball'],
            'correct_answer' => 'A) apple',
        ]);

        $this->assertSame(['A' => 'A) apple', 'B' => 'B) ball'], $question->optionMap());
    }

    public function test_a_bare_letter_matches_a_free_text_correct_answer(): void
    {
        // Scored with a strict === against correct_answer, so a submitted 'A'
        // never matched the stored 'A) apple' and every MCQ scored zero.
        $question = new Question([
            'options' => ['A) apple', 'B) ball'],
            'correct_answer' => 'A) apple',
        ]);

        $this->assertTrue($question->isCorrectAnswer('A'));
        $this->assertFalse($question->isCorrectAnswer('B'));

        $bare = new Question(['options' => ['apple', 'ball'], 'correct_answer' => 'B']);
        $this->assertTrue($bare->isCorrectAnswer('B'));
        $this->assertFalse($bare->isCorrectAnswer('A'));
    }

    /*
    |--------------------------------------------------------------------------
    | A settled offline payment counts toward what the student owes
    |--------------------------------------------------------------------------
    */

    public function test_course_less_settled_payments_count_toward_the_fee(): void
    {
        // getPaymentSummary() added whereIn('course_id', $courseIds), which
        // excluded offline/cash payments (course_id IS NULL) for every
        // batch-assigned student. A fully paid student was told they still owed
        // the whole course price.
        $course = Course::create(['name' => 'Web', 'code' => 'WEB', 'price' => 10000, 'status' => 'active']);
        CourseEnrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        Payment::create([
            'student_id' => $this->student->id,
            'course_id' => null,
            'amount' => 10000,
            'payment_method' => 'cash',
            'transaction_id' => 'CASH-1',
            'receipt_number' => 'RCP1',
            'payment_date' => today(),
            'status' => Payment::STATUS_COMPLETED,
        ]);

        $summary = app(StudentPortalService::class)->getPaymentSummary($this->student);

        $this->assertSame(10000.0, (float) $summary['paid_amount']);
        $this->assertSame(0.0, (float) $summary['due_amount']);
        $this->assertTrue($summary['is_fully_paid']);
    }

    /*
    |--------------------------------------------------------------------------
    | Receipt numbers must not wedge for the rest of the month
    |--------------------------------------------------------------------------
    */

    public function test_a_null_receipt_row_does_not_reset_the_sequence(): void
    {
        // Student course payments are created by PaymentController::submit()
        // via Payment::create(), bypassing recordPayment(), so they carry
        // receipt_number NULL. The generator picked the newest row by id, saw
        // NULL, restarted at 1, and collided with the month's first receipt on
        // the unique index -> 500 for every subsequent staff payment.
        $service = app(PaymentService::class);

        Payment::create([
            'student_id' => $this->student->id, 'amount' => 500, 'payment_method' => 'cash',
            'transaction_id' => 'C1', 'receipt_number' => 'RCP2026090001',
            'payment_date' => today(), 'status' => Payment::STATUS_COMPLETED,
        ]);

        // A newer student-submitted row with no receipt number.
        Payment::create([
            'student_id' => $this->student->id, 'amount' => 500, 'payment_method' => 'bKash',
            'transaction_id' => 'B1', 'receipt_number' => null,
            'payment_date' => today(), 'status' => Payment::STATUS_PENDING,
        ]);

        $next = $service->generateReceiptNumber();

        $this->assertStringEndsWith('0002', $next, 'The sequence must continue from the last real receipt.');
    }

    /*
    |--------------------------------------------------------------------------
    | Deactivated accounts stop working immediately
    |--------------------------------------------------------------------------
    */

    public function test_a_deactivated_account_is_logged_out_of_its_existing_session(): void
    {
        // is_active was only checked at login, so rejecting an admission left the
        // student's current session working for its full lifetime.
        $this->actingAs($this->user);

        $this->get('/dashboard')->assertRedirect();

        $this->user->forceFill(['is_active' => false])->save();

        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_a_user_created_without_an_explicit_is_active_is_not_locked_out(): void
    {
        // Eloquent's create() returns an instance holding only the attributes
        // that were set, so is_active reads back as NULL even though the column
        // defaults to true. A falsy check here would lock out active users.
        $fresh = User::factory()->create(['must_change_password' => false]);
        $this->assertNull($fresh->is_active, 'Precondition: the attribute is absent on the instance.');

        $this->actingAs($fresh);
        $this->get('/dashboard')
            ->assertSuccessful()
            ->assertSessionMissing('errors');
    }
}
