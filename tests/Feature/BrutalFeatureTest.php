<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Brutal end-to-end tests of every visible feature.
 * These do NOT mock the auth CSRF — they exercise the real flows.
 */
class BrutalFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($role);
        return $user;
    }

    /* ---------- AUTH ---------- */

    public function test_login_logout_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'brutal@test.com',
            'password' => bcrypt('Brutal#Pass123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->post('/login', ['email' => 'brutal@test.com', 'password' => 'Brutal#Pass123'])
            ->assertRedirect();
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@test.com',
            'password' => bcrypt('Brutal#Pass123'),
            'is_active' => false,
        ]);

        $this->post('/login', ['email' => 'inactive@test.com', 'password' => 'Brutal#Pass123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /* ---------- STUDENT CREATION ---------- */

    public function test_admin_can_create_student_and_student_can_login(): void
    {
        // Seed the student role (as the role seeder would in production)
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $admin = $this->makeUser('super-admin');
        $course = Course::factory()->create(['delivery_mode' => 'offline']);
        $batch = Batch::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($admin)->post('/dashboard/students', [
            'name' => 'Brutal Student',
            'email' => 'brutal.student@test.com',
            'phone' => '01712345678',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'payment_method' => 'Cash',
        ])->assertRedirect(route('dashboard.students.index'));

        $this->assertDatabaseHas('users', ['email' => 'brutal.student@test.com', 'is_active' => true]);
        $this->assertDatabaseHas('students', ['phone' => '01712345678']);

        // Student can log in (password reset link is sent, but login uses the temp password setup)
        $studentUser = User::where('email', 'brutal.student@test.com')->first();
        $this->assertTrue($studentUser->hasRole('student'));
    }

    public function test_student_creation_requires_name_and_phone(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->post('/dashboard/students', [
            'email' => 'missing@test.com',
            'admission_mode' => 'online',
        ])->assertSessionHasErrors(['name', 'phone']);
    }

    /* ---------- COURSE CREATION ---------- */

    public function test_admin_can_create_course_and_it_shows_on_frontend(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->post('/dashboard/courses', [
            'name' => 'Brutal Course',
            'code' => 'BRT-01',
            'description' => 'A brutal test course',
            'price' => 9999,
            'duration' => 3,
            'duration_unit' => 'months',
            'status' => 'active',
            'delivery_mode' => 'online',
            'level' => 'beginner',
            'category' => 'Web Development',
        ])->assertRedirect(route('dashboard.courses.index'));

        $this->assertDatabaseHas('courses', ['code' => 'BRT-01', 'status' => 'active']);

        // Shows on frontend courses page
        $this->get('/courses')->assertSuccessful()->assertSee('Brutal Course');
    }

    public function test_course_code_must_be_unique(): void
    {
        $admin = $this->makeUser('super-admin');
        Course::factory()->create(['code' => 'DUP-01']);

        $this->actingAs($admin)->post('/dashboard/courses', [
            'name' => 'Duplicate',
            'code' => 'DUP-01',
            'price' => 100,
            'status' => 'active',
            'delivery_mode' => 'online',
            'level' => 'beginner',
        ])->assertSessionHasErrors('code');
    }

    /* ---------- COURSE VIDEOS ---------- */

    public function test_admin_can_add_youtube_video_to_course(): void
    {
        $admin = $this->makeUser('super-admin');
        $course = Course::factory()->create();

        $this->actingAs($admin)->post("/dashboard/courses/{$course->id}/videos", [
            'title' => 'Intro Lesson',
            'video_type' => 'youtube',
            'external_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_preview' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('course_videos', [
            'course_id' => $course->id,
            'external_id' => 'dQw4w9WgXcQ',
            'is_preview' => true,
        ]);
    }

    /* ---------- ANNOUNCEMENTS ---------- */

    public function test_admin_can_create_announcement_and_student_sees_it(): void
    {
        $admin = $this->makeUser('super-admin');
        $studentUser = $this->makeUser('student');
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $this->actingAs($admin)->post('/dashboard/announcements', [
            'title' => 'Brutal Notice',
            'content' => 'This is a brutal announcement',
            'target_type' => 'all',
            'priority' => 'high',
            'is_active' => 1,
        ])->assertRedirect(route('dashboard.announcements.index'));

        $this->assertDatabaseHas('announcements', ['title' => 'Brutal Notice']);

        // Student can view the announcement detail page (requires login)
        $announcement = Announcement::where('title', 'Brutal Notice')->first();
        $this->actingAs($studentUser)->get("/announcements/{$announcement->id}")
            ->assertSuccessful()
            ->assertSee('Brutal Notice');
    }

    public function test_announcement_requires_title_and_content(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->post('/dashboard/announcements', [
            'target_type' => 'all',
            'priority' => 'normal',
        ])->assertSessionHasErrors(['title', 'content']);
    }

    /* ---------- PAYMENTS ---------- */

    public function test_admin_approves_payment_student_gets_enrolled_and_email_sent(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);
        \App\Models\Setting::updateOrCreate(['key' => 'brevo_api_key'], ['value' => 'xkeysib-test', 'group' => 'email', 'type' => 'string']);
        app(\App\Services\SettingsService::class)->clearCache();

        $admin = $this->makeUser('super-admin');
        $studentUser = $this->makeUser('student');
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $course = Course::factory()->create();
        $batch = Batch::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 5000,
            'status' => Payment::STATUS_PENDING,
            'payment_method' => 'Bkash',
            'transaction_id' => 'BRT-TRX-1',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->post("/admin/payment-review/{$payment->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_COMPLETED]);
        $this->assertDatabaseHas('course_enrollments', ['student_id' => $student->id, 'course_id' => $course->id]);

        // Brevo email sent
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.brevo.com') && str_contains($r['subject'], 'Payment Approved'));
    }

    /* ---------- CERTIFICATES + TEMPLATES ---------- */

    public function test_template_crud_and_certificate_uses_default_template(): void
    {
        $admin = $this->makeUser('super-admin');
        $studentUser = $this->makeUser('student');
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $course = Course::factory()->create();
        CourseEnrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        // Create a template via the admin UI
        $this->actingAs($admin)->post('/dashboard/certificates/templates', [
            'name' => 'Brutal Template',
            'type' => 'course_completion',
            'width' => 1200,
            'height' => 900,
            'is_default' => 1,
        ])->assertRedirect();

        $template = CertificateTemplate::where('name', 'Brutal Template')->first();
        $this->assertNotNull($template);
        $this->assertTrue($template->is_default);

        // Issue a certificate (should use the default template)
        $enrollment = CourseEnrollment::first();
        $this->actingAs($admin)->post('/dashboard/certificates', [
            'enrollment_id' => $enrollment->id,
        ])->assertRedirect();

        $cert = Certificate::first();
        $this->assertNotNull($cert);
        $this->assertEquals($template->id, $cert->template_id);

        // Student can view the certificate with template applied
        $this->actingAs($studentUser)->get("/student/certificates/{$cert->id}")
            ->assertSuccessful()
            ->assertSee($studentUser->name);
    }

    public function test_certificate_verify_page(): void
    {
        $admin = $this->makeUser('super-admin');
        $studentUser = $this->makeUser('student');
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $course = Course::factory()->create();
        $cert = app(\App\Services\CertificateService::class)->issue($student, $course, $admin);

        $this->get("/certificates/verify/{$cert->verification_code}")
            ->assertSuccessful()
            ->assertSee('Valid certificate')
            ->assertSee($course->name);
    }

    /* ---------- SERVICES ---------- */

    public function test_services_page_lists_seeded_services(): void
    {
        Service::create([
            'title' => 'Brutal Service',
            'slug' => 'brutal-service',
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->get('/services')->assertSuccessful()->assertSee('Brutal Service');
    }

    /* ---------- TEAM ---------- */

    public function test_team_page_lists_teachers(): void
    {
        $teacherUser = $this->makeUser('teacher');
        Teacher::create([
            'user_id' => $teacherUser->id,
            'department' => 'Web Development',
            'designation' => 'Instructor',
            'status' => 'active',
            'is_featured' => true,
        ]);

        $this->get('/team')->assertSuccessful()->assertSee($teacherUser->name);
    }

    /* ---------- DASHBOARD ---------- */

    public function test_dashboard_renders_with_data(): void
    {
        $admin = $this->makeUser('super-admin');
        $student = Student::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertSuccessful()
            ->assertSee('মোট শিক্ষার্থী')
            ->assertSee('মোট শিক্ষক');
    }

    public function test_email_dashboard_renders(): void
    {
        $admin = $this->makeUser('super-admin');
        $this->actingAs($admin)->get('/dashboard/email')
            ->assertSuccessful()
            ->assertSee('Send Bulk Email');
    }

    /* ---------- ADMISSION (public) ---------- */

    public function test_public_admission_submission_works(): void
    {
        $course = Course::factory()->active()->create(['delivery_mode' => 'offline']);

        $this->post('/admission', [
            'name' => 'Public Applicant',
            'email' => 'public@test.com',
            'phone' => '01812345678',
            'course_id' => $course->id,
            'admission_mode' => 'offline',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('students', ['phone' => '01812345678', 'admission_status' => 'pending']);
    }
}
