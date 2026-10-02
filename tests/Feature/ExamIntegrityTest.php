<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\ExamTakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for exam-integrity flaws found in an audit.
 *
 * The serious one was that nothing enforced the exam deadline server-side.
 * `autoSubmitExpired()` existed but had no callers, so the only gate was the
 * countdown in the browser — which a student simply ignores.
 */
class ExamIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser = User::factory()->create(['is_active' => true]);
        $this->studentUser->roles()->attach($role);
        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);
    }

    private function makeExam(array $overrides = []): Exam
    {
        return Exam::create(array_merge([
            'title' => 'Assessment',
            'type' => 'mcq',
            'total_marks' => 100,
            'pass_marks' => 40,
            'duration_minutes' => 30,
            'status' => 'active',
            'start_time' => now()->subHour(),
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | The deadline must be enforced server-side
    |--------------------------------------------------------------------------
    */

    public function test_an_expired_attempt_is_marked_expired_rather_than_submitted(): void
    {
        $exam = $this->makeExam();

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subHours(2),
            'status' => 'in_progress',
        ]);

        $this->assertTrue($attempt->isExpired(), 'Two hours into a 30 minute exam is expired.');

        app(ExamTakingService::class)->submitExam($attempt);

        $attempt->refresh();

        $this->assertSame('expired', $attempt->status, 'The audit trail must distinguish a missed deadline.');
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_a_timely_attempt_is_marked_submitted(): void
    {
        $exam = $this->makeExam();

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subMinutes(5),
            'status' => 'in_progress',
        ]);

        $this->assertFalse($attempt->isExpired());

        app(ExamTakingService::class)->submitExam($attempt);

        $this->assertSame('submitted', $attempt->fresh()->status);
    }

    public function test_answers_are_refused_once_the_attempt_has_expired(): void
    {
        $exam = $this->makeExam();

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subHours(2),
            'status' => 'in_progress',
        ]);

        app(ExamTakingService::class)->saveCqTextAnswers($attempt, ['1' => 'my late answer']);

        $this->assertNull($attempt->fresh()->answers, 'An expired attempt must not keep accruing answers.');
    }

    public function test_the_auto_submit_backstop_is_registered_and_works(): void
    {
        $exam = $this->makeExam();

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subHours(3),
            'status' => 'in_progress',
        ]);

        $count = app(ExamTakingService::class)->autoSubmitExpired();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame('expired', $attempt->fresh()->status, 'autoSubmitExpired must not overwrite the status back to submitted.');
    }

    public function test_the_backstop_command_is_scheduled(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->command ?? '');

        $this->assertTrue(
            $events->contains(fn ($c) => str_contains($c, 'exams:auto-submit-expired')),
            'The backstop is only useful if something runs it.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | An untimed exam must not read as instantly expired
    |--------------------------------------------------------------------------
    */

    public function test_an_exam_with_no_duration_is_untimed_rather_than_expired(): void
    {
        // A NULL duration used to compute remaining_time as 0, which made every
        // answer rejected and scored the student zero while their timer counted
        // down from PHP_INT_MAX.
        $exam = $this->makeExam(['duration_minutes' => null]);

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subDays(5),
            'status' => 'in_progress',
        ]);

        $this->assertFalse($attempt->isExpired(), 'An exam with no duration must never be considered expired.');
    }

    public function test_remaining_time_counts_down_rather_than_being_infinite(): void
    {
        $exam = $this->makeExam(['duration_minutes' => 30]);

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        $this->assertGreaterThan(0, $attempt->remaining_time);
        $this->assertLessThanOrEqual(30 * 60, $attempt->remaining_time);
    }

    /*
    |--------------------------------------------------------------------------
    | One grading scale
    |--------------------------------------------------------------------------
    */

    public function test_every_grade_scale_agrees_with_the_canonical_one(): void
    {
        // Previously five implementations with two threshold sets meant a stored
        // "A" could be reported as a "B" on the same row.
        $expectations = [
            95 => 'A+',
            80 => 'A+',
            75 => 'A',
            70 => 'A',
            65 => 'A-',
            60 => 'A-',
            55 => 'B',
            50 => 'B',
            45 => 'C',
            40 => 'C',
            35 => 'D',
            33 => 'D',
            20 => 'F',
            0 => 'F',
        ];

        foreach ($expectations as $percentage => $grade) {
            $this->assertSame(
                $grade,
                ExamResult::gradeForPercentage((float) $percentage),
                "{$percentage}% should be {$grade}."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Marks cannot exceed the paper
    |--------------------------------------------------------------------------
    */

    public function test_an_autoscored_result_never_exceeds_total_marks(): void
    {
        // The score is the sum of question marks, which is not checked against
        // exams.total_marks; a mismatch used to write obtained_marks above the
        // total, producing a percentage over 100 that no admin could correct.
        $exam = $this->makeExam(['total_marks' => 10]);

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'started_at' => now(),
            'status' => 'in_progress',
            'answers' => ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E'],
        ]);

        app(ExamTakingService::class)->submitExam($attempt);

        $result = ExamResult::where('student_id', $this->student->id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        $this->assertLessThanOrEqual(10, (int) $result->obtained_marks);
        $this->assertLessThanOrEqual(100, (float) $result->percentage);
    }
}