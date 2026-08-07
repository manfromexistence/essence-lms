<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_trends_chart_has_seeded_data(): void
    {
        $studentUser = User::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $course = Course::factory()->create();

        // Completed payment with payment_date in the last month
        Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 5000,
            'status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'Bkash',
            'transaction_id' => 'CHART-TEST-1',
            'payment_date' => now()->subDays(2)->toDateString(),
            'submitted_at' => now()->subDays(2),
        ]);

        $data = app(\App\Services\ReportService::class)->getDashboardChartData('payment_trends', []);
        $total = $data['summary']['total_amount'] ?? 0;
        $this->assertGreaterThan(0, $total, 'Payment trends chart should include the completed payment');
    }

    public function test_dashboard_recent_activities_show_student_names(): void
    {
        $studentUser = User::factory()->create(['name' => 'Chart Student', 'is_active' => true]);
        $student = Student::factory()->create(['user_id' => $studentUser->id, 'name_bn' => 'চার্ট স্টুডেন্ট']);

        $activities = app(\App\Services\DashboardService::class)->getRecentActivities(10);
        $messages = $activities->pluck('message')->implode(' ');

        $this->assertStringContainsString('চার্ট স্টুডেন্ট', $messages);
        $this->assertStringNotContainsString('Unknown Student', $messages);
    }

    public function test_dashboard_stat_cards_are_links(): void
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertSuccessful()
            ->assertSee('dashboard/students', false)
            ->assertSee('dashboard/teachers', false)
            ->assertSee('dashboard/payments', false)
            ->assertSee('dashboard/batches', false);
    }
}
