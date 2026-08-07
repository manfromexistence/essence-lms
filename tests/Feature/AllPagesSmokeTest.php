<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: every visible dashboard and student page must return 200.
 * Catches missing views, broken relations and controller errors.
 */
class AllPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_dashboard_pages_render(): void
    {
        $this->seed(\Database\Seeders\CertificateTemplateSeeder::class);
        $this->seed(\Database\Seeders\TeamAndServicesSeeder::class);

        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($role);

        $course = Course::factory()->active()->create();
        $batch = \App\Models\Batch::factory()->create(['course_id' => $course->id]);
        $studentUser = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $studentUser->roles()->attach($studentRole);
        $student = Student::factory()->create(['user_id' => $studentUser->id, 'batch_id' => $batch->id]);
        CourseEnrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        Payment::create([
            'student_id' => $student->id, 'course_id' => $course->id, 'amount' => 1000,
            'status' => Payment::STATUS_PENDING, 'payment_method' => 'Bkash',
            'transaction_id' => 'SMOKE1', 'submitted_at' => now(),
        ]);
        Service::create(['title' => 'Test Service', 'slug' => 'test-service', 'price' => 100, 'is_active' => true]);

        $pages = [
            '/dashboard',
            '/dashboard/students',
            '/dashboard/students/create',
            '/dashboard/students/admission-form',
            '/dashboard/courses',
            '/dashboard/courses/create',
            "/dashboard/courses/{$course->id}",
            "/dashboard/courses/{$course->id}/edit",
            "/dashboard/courses/{$course->id}/materials",
            "/dashboard/courses/{$course->id}/videos",
            '/dashboard/batches',
            '/dashboard/batches/create',
            '/dashboard/payments',
            '/dashboard/payments/create',
            '/dashboard/payments/invoices',
            '/dashboard/payments/receipts',
            '/dashboard/payments/tracking',
            '/dashboard/certificates',
            '/dashboard/certificates/templates',
            '/dashboard/announcements',
            '/dashboard/announcements/create',
            '/dashboard/services',
            '/dashboard/services/create',
            '/dashboard/teachers',
            '/dashboard/teachers/create',
            '/dashboard/email',
            '/dashboard/cms',
            '/dashboard/users',
            '/dashboard/settings',
        ];

        $failures = [];
        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);
            if ($response->getStatusCode() !== 200) {
                $failures[] = $page . ' -> ' . $response->getStatusCode();
            }
        }

        $this->assertEmpty($failures, 'Dashboard pages failing: ' . implode(', ', $failures));
    }

    public function test_all_student_pages_render(): void
    {
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($studentRole);
        $student = Student::factory()->create(['user_id' => $user->id]);

        $pages = [
            '/student/dashboard',
            '/student/materials',
            '/student/schedule',
            '/student/exams',
            '/student/results',
            '/student/payments',
            '/student/courses',
            '/student/certificates',
        ];

        $failures = [];
        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);
            if ($response->getStatusCode() !== 200) {
                $failures[] = $page . ' -> ' . $response->getStatusCode();
            }
        }

        $this->assertEmpty($failures, 'Student pages failing: ' . implode(', ', $failures));
    }
}
