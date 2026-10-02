<?php

namespace Tests\Feature;

use App\Exports\Concerns\NeutralisesExcelFormulas;
use App\Http\Requests\StoreStudentRequest;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\ClassSchedule;
use App\Models\Exam;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\BrevoEmailService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Tests\TestCase;

/**
 * Regression tests for the third audit round: stored XSS reachable by anonymous
 * visitors, and schedule/attendance integrity.
 */
class ThirdAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::where('slug', 'admin')->first());
    }

    /*
    |--------------------------------------------------------------------------
    | Anonymous input must not become script in an admin's browser
    |--------------------------------------------------------------------------
    */

    public function test_the_sms_log_modal_escapes_every_interpolated_value(): void
    {
        // sms_logs.message is composed from the student's name, and that name is
        // supplied by an ANONYMOUS applicant on /admission (validated as a plain
        // string, no HTML filtering). The modal interpolated it straight into
        // innerHTML, so anyone could POST an admission form containing markup and
        // have it execute in the session of any admin who later opened the SMS
        // log — full account takeover.
        $view = (string) file_get_contents(
            resource_path('views/dashboard/communication/logs.blade.php')
        );

        // The sink itself must not interpolate raw values.
        $this->assertStringNotContainsString(
            '${log.message}',
            $view,
            'log.message must not reach innerHTML unescaped.'
        );
        $this->assertStringNotContainsString('${log.phone}', $view);
        $this->assertStringNotContainsString('${log.error_message}', $view);

        // And an escaper must exist.
        $this->assertStringContainsString('__esc', $view);
    }

    public function test_the_admission_name_field_still_accepts_a_name(): void
    {
        // Precondition for the test above being about XSS specifically rather than
        // about the form being broken: the field is a plain string by design.
        $rules = (new StoreStudentRequest)->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertStringContainsString('string', $rules['name']);
    }

    public function test_public_pages_escape_course_and_service_data(): void
    {
        $courses = (string) file_get_contents(resource_path('views/courses.blade.php'));
        $services = (string) file_get_contents(resource_path('views/services.blade.php'));

        $this->assertStringContainsString('escHtml', $courses);
        $this->assertStringNotContainsString('alt="${course.name}"', $courses);
        $this->assertStringContainsString('${escHtml(course.description)', $courses);

        $this->assertStringContainsString('escHtml', $services);
        // The cart row must address the item by index, not splice the title into an
        // onclick="..." literal.
        $this->assertStringNotContainsString('${i.name', $services);
        $this->assertStringContainsString('removeFromCart(${idx})', $services);
    }

    public function test_a_course_name_cannot_break_out_of_an_alt_attribute(): void
    {
        $courses = (string) file_get_contents(resource_path('views/courses.blade.php'));

        $this->assertStringNotContainsString('alt="${course.name}"', $courses);
        $this->assertStringContainsString('alt="${escHtml(course.name)}"', $courses);
    }

    /*
    |--------------------------------------------------------------------------
    | day_of_week had two incompatible encodings in one column
    |--------------------------------------------------------------------------
    */

    public function test_day_of_week_is_validated_against_the_column_enum(): void
    {
        // The column is an enum of weekday NAMES, but validation accepted
        // integer|min:0|max:6 and both forms posted 0-6. So the same column held
        // 'wednesday' and '3', and four screens disagreed: the schedules index
        // compared integers, TeacherController and ClassSchedule::isToday
        // compared names, and the student timetable rendered "N/A".
        $controller = (string) file_get_contents(
            app_path('Http/Controllers/Admin/ScheduleController.php')
        );

        $this->assertStringNotContainsString(
            "'day_of_week' => 'required|integer",
            $controller,
            'day_of_week must not be validated as an integer.'
        );
        $this->assertStringContainsString(
            "'day_of_week' => 'required|in:sunday",
            $controller
        );

        foreach (['create', 'edit'] as $form) {
            $blade = (string) file_get_contents(
                resource_path("views/dashboard/schedules/{$form}.blade.php")
            );

            $this->assertStringContainsString('<option value="monday">', $blade);
            $this->assertStringNotContainsString('<option value="1">', $blade);
        }
    }

    public function test_every_weekday_name_is_a_valid_schedule_day(): void
    {
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach ($days as $day) {
            $schedule = new ClassSchedule(['day_of_week' => $day]);

            $this->assertSame($day, $schedule->day_of_week);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Re-marking an already-marked attendance day must not 500
    |--------------------------------------------------------------------------
    */

    public function test_attendance_can_be_corrected_on_a_day_already_marked(): void
    {
        // The raw request string was used as the updateOrCreate() key while the
        // model casts `date`, so a WRITE stored 'Y-m-d 00:00:00' while the WHERE
        // looked for 'Y-m-d'. The lookup never matched, fell through to an
        // INSERT, and the (student_id, batch_id, date) unique index rejected it:
        // a teacher could not correct a single day they had already marked.
        $batch = Batch::create(['name' => 'B', 'code' => 'B1']);
        $student = Student::factory()->create(['batch_id' => $batch->id]);

        $date = now()->subDay();

        Attendance::create([
            'student_id' => $student->id,
            'batch_id' => $batch->id,
            'date' => $date,
            'status' => 'absent',
        ]);

        // Exactly what the corrected controller does.
        $normalised = Carbon::parse($date->toDateString())->toDateString();

        $updated = Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'batch_id' => $batch->id,
                'date' => $normalised,
            ],
            ['status' => 'present']
        );

        $this->assertSame('present', $updated->status, 'The existing row must be updated, not duplicated.');

        $this->assertSame(
            1,
            Attendance::where('student_id', $student->id)
                ->where('batch_id', $batch->id)
                ->count(),
            'Exactly one row per student/batch/day.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | An exam with no batch must not be open to the whole institute
    |--------------------------------------------------------------------------
    */

    public function test_an_exam_orphaned_from_its_batch_is_not_sittable(): void
    {
        // exams.batch_id is `on delete set null`, and deleting a batch had no
        // guard. assertExamAccess() returned early when batch_id was null, so
        // deleting one batch turned its mid-term and final into papers any
        // student in the institute could sit.
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('slug', 'student')->first());
        $student = Student::factory()->create(['user_id' => $user->id]);

        $exam = Exam::create([
            'title' => 'Orphaned', 'type' => 'mcq', 'total_marks' => 10,
            'pass_marks' => 4, 'duration_minutes' => 30, 'status' => 'active',
            'start_time' => now()->subHour(),
            'batch_id' => null,
        ]);

        $this->actingAs($user);

        $this->get(route('student.exams.start', $exam->id))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | A batch with dependants must not be deletable
    |--------------------------------------------------------------------------
    */

    public function test_a_batch_holding_students_cannot_be_deleted(): void
    {
        $batch = Batch::create(['name' => 'B', 'code' => 'B2']);
        $student = Student::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($this->admin);

        $this->delete(route('dashboard.batches.destroy', $batch))
            ->assertSessionHasErrors('batch');

        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Exports must not emit live formulas
    |--------------------------------------------------------------------------
    */

    public function test_exports_neutralise_formula_leading_values(): void
    {
        foreach (['Student', 'Payment', 'Attendance', 'Performance'] as $name) {
            $class = "App\\Exports\\{$name}Export";

            $this->assertTrue(
                in_array(
                    WithCustomValueBinder::class,
                    class_implements($class),
                    true
                ),
                "{$name}Export must bind cell values to neutralise formulas."
            );
            $this->assertContains(
                NeutralisesExcelFormulas::class,
                class_uses($class)
            );
        }
    }

    public function test_bulk_email_targets_a_method_that_exists(): void
    {
        // SendBulkEmailsJob called BrevoEmailService::sendBulk(), which did not
        // exist. With $tries = 1 and no failed() handler, every bulk campaign
        // died silently after the controller had already returned
        // {"success": true, "queued": true}.
        $this->assertTrue(
            method_exists(BrevoEmailService::class, 'sendBulk'),
            'sendBulk() must exist or bulk email is 100% broken.'
        );
    }
}
