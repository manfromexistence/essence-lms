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

        $this->get('/services')->assertSuccessful()->assertSee('Website Development')->assertSee('Freelancing Mentorship');
        $this->get('/team')->assertSuccessful()->assertSee('Meet our training team')->assertSee('Student Success Team');
        $this->get("/courses/{$course->id}/demo")->assertSuccessful()->assertSee('Free Orientation Class')->assertSee('youtube-nocookie.com', false);
    }

    public function test_course_selling_sidebar_hides_school_academic_modules(): void
    {
        $user = $this->makeUserWithRole('super-admin');
        $menu = json_encode(app(SidebarService::class)->getMenuItems($user));

        $this->assertStringContainsString('Certificates', $menu);
        $this->assertStringContainsString('Videos & Demo Classes', $menu);
        $this->assertStringNotContainsString('All Classes', $menu);
        $this->assertStringNotContainsString('MCQ Exam', $menu);
        $this->assertStringNotContainsString('Attendance Tracking', $menu);
        $this->assertStringNotContainsString('Batches', $menu);
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

        $response = $this->actingAs($studentUser)->postJson("/student/courses/{$course->id}/watch/{$video->id}/complete", ['watched_seconds' => 60]);

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
