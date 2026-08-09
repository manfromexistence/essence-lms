<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\SidebarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CourseBusinessExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_form_is_compact_and_course_focused(): void
    {
        Course::factory()->active()->create(['delivery_mode' => 'offline']);

        $this->get('/admission/offline')
            ->assertSuccessful()
            ->assertSee('Applicant information')
            ->assertSee('Learning Mode')
            ->assertSee('Course selection')
            ->assertDontSee('Academic qualification')
            ->assertDontSee('Marital Status')
            ->assertDontSee('Permanent Address');
    }

    public function test_services_team_and_public_demo_pages_render(): void
    {
        $course = Course::factory()->active()->create(['name' => 'Professional Web Development']);
        CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Free Orientation Class',
            'video_type' => 'youtube',
            'external_id' => 'dQw4w9WgXcQ',
            'order' => 1,
            'is_preview' => true,
        ]);

        $this->get('/services')->assertSuccessful()->assertSee('Our services');
        $this->get('/team')->assertSuccessful()->assertSee('Meet our team');
        $this->get("/courses/{$course->id}/demo")->assertSuccessful()->assertSee('Free Orientation Class')->assertSee('youtube-nocookie.com', false);
    }

    public function test_course_selling_sidebar_exposes_admin_pages(): void
    {
        $user = $this->makeUserWithRole('super-admin');
        $menu = json_encode(app(SidebarService::class)->getMenuItems($user));

        // Core course-business pages are present
        $this->assertStringContainsString('Certificates', $menu);
        $this->assertStringContainsString('Videos & Demo Classes', $menu);
        $this->assertStringContainsString('Services & Store', $menu);
        $this->assertStringContainsString('Team Members', $menu);
        $this->assertStringContainsString('Batches', $menu);

        // School/class modules are commented out (not needed for course LMS)
        $this->assertStringNotContainsString('MCQ Exams', $menu);
        $this->assertStringNotContainsString('Attendance', $menu);
        $this->assertStringNotContainsString('Accounts', $menu);
        $this->assertStringNotContainsString('Communication', $menu);
        $this->assertStringNotContainsString('Inventory', $menu);
        $this->assertStringNotContainsString('Reports', $menu);

        // Legacy school-only modules that have no routes stay hidden
        $this->assertStringNotContainsString('All Classes', $menu);
        $this->assertStringNotContainsString('Class 12', $menu);
    }

    public function test_student_sidebar_exposes_complete_learning_portal(): void
    {
        $student = $this->makeUserWithRole('student');
        $menu = json_encode(app(SidebarService::class)->getMenuItems($student));

        foreach ([
            'My Learning', 'Student Dashboard', 'My Courses', 'Learning Materials', 'Class Schedule',
            'Progress & Certificates', 'Exams', 'Results', 'Performance & Results', 'My Certificates',
            'Payments', 'Payment Dashboard', 'Payment History', 'Account & Support',
            'Change Password', 'Contact Support',
        ] as $label) {
            $this->assertStringContainsString($label, $menu);
        }

        foreach ([
            'student.dashboard', 'student.courses', 'student.materials', 'student.schedule',
            'student.exams', 'student.results',
            'student.certificates.index', 'student.payment.dashboard', 'student.payments',
            'password.change', 'contact',
        ] as $route) {
            $this->assertTrue(Route::has($route), "Sidebar route [{$route}] must exist.");
        }
    }

    public function test_student_courses_page_renders_local_course_images_without_cloud_storage(): void
    {
        $studentUser = $this->makeUserWithRole('student');
        Student::factory()->create(['user_id' => $studentUser->id, 'batch_id' => null]);
        $course = Course::factory()->active()->create([
            'name' => 'Local Image Course',
            'delivery_mode' => 'online',
            'image' => 'courses/local-image.jpg',
        ]);

        config(['filesystems.default' => 's3']);

        $this->actingAs($studentUser)
            ->get('/student/courses')
            ->assertSuccessful()
            ->assertSee($course->name)
            ->assertSee('storage/courses/local-image.jpg', false);
    }

    public function test_dashboard_select_hover_keeps_full_opacity(): void
    {
        $admin = $this->makeUserWithRole('super-admin');

        $this->actingAs($admin)->get('/dashboard')
            ->assertSuccessful()
            ->assertSee('background-color: color-mix', false)
            ->assertSee('opacity: 1', false);
    }

    public function test_completing_final_course_video_issues_certificate(): void
    {
        $studentUser = $this->makeUserWithRole('student');
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $course = Course::factory()->active()->create();
        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Final Lesson',
            'video_type' => 'youtube',
            'external_id' => 'dQw4w9WgXcQ',
            'duration' => 60,
            'order' => 1,
        ]);
        CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($studentUser)
            ->postJson("/student/courses/{$course->id}/watch/{$video->id}/complete", ['watched_seconds' => 60]);

        $response->assertSuccessful()->assertJsonPath('completed', true)->assertJsonPath('next_url', null);
        $this->assertNotNull($response->json('certificate_url'));
        $certificate = Certificate::first();
        $this->assertNotNull($certificate);
        $this->get("/certificates/verify/{$certificate->verification_code}")
            ->assertSuccessful()
            ->assertSee('Valid certificate')
            ->assertSee($course->name);
    }

    private function makeUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
