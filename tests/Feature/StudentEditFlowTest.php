<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The edit-student page must mirror the add-student (course LMS) fields,
 * and updating must keep the linked user + lifecycle status in sync.
 */
class StudentEditFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_edit_page_shows_course_fields_not_school_fields(): void
    {
        $admin = $this->createAdmin();
        $studentUser = User::factory()->create(['name' => 'Old Name', 'is_active' => true]);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'name_bn' => 'Old Bangla Name',
            'admission_mode' => 'offline',
            'admission_status' => 'pending',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get("/dashboard/students/{$student->id}/edit");
        $response->assertSuccessful()
            ->assertSee('Student Information')
            ->assertSee('Course & Admission', false)
            ->assertSee('name="admission_mode"', false)
            ->assertSee('Online Student')
            ->assertSee('Offline Student')
            ->assertSee('Payment Information');

        // Old school-era fields must be gone.
        $this->assertStringNotContainsString('Select Class', $response->getContent());
        $this->assertStringNotContainsString('Academic Qualification', $response->getContent());
        $this->assertStringNotContainsString('Family Information', $response->getContent());
        $this->assertStringNotContainsString('SSC / Equivalent', $response->getContent());
    }

    public function test_updating_student_syncs_user_name_email_and_status(): void
    {
        $admin = $this->createAdmin();
        $course = Course::factory()->active()->create(['delivery_mode' => 'offline']);
        $batch = Batch::factory()->create(['course_id' => $course->id, 'name' => 'Evening Batch']);
        $studentUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'is_active' => false,
        ]);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'name_bn' => 'Old Bangla Name',
            'admission_mode' => 'offline',
            'admission_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->put("/dashboard/students/{$student->id}", [
            'name' => 'New English Name',
            'email' => 'new@example.com',
            'phone' => '01711000001',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'total_amount' => 10000,
            'paid_amount' => 5000,
            'payment_method' => 'Bkash',
        ])->assertRedirect('/dashboard/students');

        $student->refresh();
        $studentUser->refresh();

        // User account synced.
        $this->assertSame('New English Name', $studentUser->name);
        $this->assertSame('new@example.com', $studentUser->email);
        $this->assertTrue((bool) $studentUser->is_active);

        // Lifecycle status synced (batch assigned => approved/active).
        $this->assertSame($batch->id, $student->batch_id);
        $this->assertSame('approved', $student->admission_status);
        $this->assertSame('active', $student->status);
        $this->assertSame('10000.00', (string) $student->total_amount);
    }

    public function test_removing_batch_sets_back_to_pending_and_blocks_login(): void
    {
        $admin = $this->createAdmin();
        $course = Course::factory()->active()->create(['delivery_mode' => 'offline']);
        $batch = Batch::factory()->create(['course_id' => $course->id]);
        $studentUser = User::factory()->create(['is_active' => true]);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'batch_id' => $batch->id,
            'admission_mode' => 'offline',
            'admission_status' => 'approved',
            'status' => 'active',
            'phone' => '01711000002',
        ]);

        $this->actingAs($admin)->put("/dashboard/students/{$student->id}", [
            'name' => $studentUser->name,
            'email' => $studentUser->email,
            'phone' => '01711000002',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
            'batch_id' => '', // unassign
            'total_amount' => $student->total_amount,
            'paid_amount' => $student->paid_amount,
            'payment_method' => $student->payment_method,
        ])->assertRedirect('/dashboard/students');

        $student->refresh();
        $studentUser->refresh();

        $this->assertNull($student->batch_id);
        $this->assertSame('pending', $student->admission_status);
        $this->assertSame('pending', $student->status);
        $this->assertFalse((bool) $studentUser->is_active);
    }
}
