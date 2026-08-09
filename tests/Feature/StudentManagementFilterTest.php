<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentManagementFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_admission_form_only_lists_offline_courses(): void
    {
        $offlineCourse = Course::factory()->active()->create([
            'name' => 'Offline Office Skills',
            'delivery_mode' => 'offline',
        ]);
        Course::factory()->active()->create([
            'name' => 'Online Marketing Skills',
            'delivery_mode' => 'online',
        ]);

        $this->get('/admission/offline')
            ->assertSuccessful()
            ->assertSee('Offline Student Admission Form')
            ->assertSee($offlineCourse->name)
            ->assertDontSee('Online Marketing Skills')
            ->assertSee('name="admission_mode" id="admission_mode" value="offline"', false);
    }

    public function test_offline_admission_form_can_be_submitted_with_office_search_details(): void
    {
        \App\Models\Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $course = Course::factory()->active()->create([
            'name' => 'Offline Computer Office Application',
            'delivery_mode' => 'offline',
        ]);

        $response = $this->post('/admission', [
            'name_bn' => 'Nusrat Jahan',
            'email' => 'nusrat.offline@example.com',
            'phone' => '01911000003',
            'blood_group' => 'A+',
            'present_village' => 'Mirpur Section 10',
            'present_ps' => 'Pallabi',
            'present_dist' => 'Dhaka',
            'admission_mode' => 'offline',
            'course_id' => $course->id,
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('students', [
            'name_bn' => 'Nusrat Jahan',
            'phone' => '01911000003',
            'blood_group' => 'A+',
            'present_village' => 'Mirpur Section 10',
            'admission_mode' => 'offline',
            'admission_status' => 'pending',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'nusrat.offline@example.com',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_search_students_by_required_fields_and_filter_by_mode(): void
    {
        $admin = $this->createSuperAdmin();
        $offlineCourse = Course::factory()->active()->create(['delivery_mode' => 'offline']);
        $onlineCourse = Course::factory()->active()->create(['delivery_mode' => 'online']);
        $offlineBatch = Batch::factory()->create([
            'name' => 'Mirpur Evening Batch',
            'code' => 'MIR-EVE-01',
            'course_id' => $offlineCourse->id,
        ]);
        $onlineBatch = Batch::factory()->create([
            'name' => 'Remote Morning Batch',
            'code' => 'REM-MOR-01',
            'course_id' => $onlineCourse->id,
        ]);

        $offlineUser = User::factory()->create(['name' => 'Rahim Offline Student']);
        $onlineUser = User::factory()->create(['name' => 'Karim Online Student']);

        Student::factory()->create([
            'user_id' => $offlineUser->id,
            'batch_id' => $offlineBatch->id,
            'name_bn' => 'Rahim Uddin',
            'phone' => '01711000001',
            'present_village' => 'Pallabi Mirpur',
            'present_ps' => 'Pallabi',
            'present_dist' => 'Dhaka',
            'blood_group' => 'B+',
            'admission_mode' => 'offline',
        ]);
        Student::factory()->create([
            'user_id' => $onlineUser->id,
            'batch_id' => $onlineBatch->id,
            'name_bn' => 'Karim Hasan',
            'phone' => '01822000002',
            'present_village' => 'Uttara',
            'present_ps' => 'Uttara West',
            'present_dist' => 'Dhaka',
            'blood_group' => 'O+',
            'admission_mode' => 'online',
        ]);

        foreach ([
            ['name', 'Rahim'],
            ['number', '01711000001'],
            ['batch', 'Mirpur Evening'],
            ['area', 'Pallabi'],
            ['blood_group', 'B+'],
        ] as [$field, $term]) {
            $this->actingAs($admin)
                ->get('/dashboard/students?' . http_build_query(['search_field' => $field, 'search' => $term]))
                ->assertSuccessful()
                ->assertSee('Rahim Offline Student')
                ->assertDontSee('Karim Online Student');
        }

        $this->actingAs($admin)
            ->get('/dashboard/students?mode=online')
            ->assertSuccessful()
            ->assertSee('Karim Online Student')
            ->assertDontSee('Rahim Offline Student')
            ->assertSee('All Students')
            ->assertSee('Add New Student')
            ->assertSee('Search students')
            ->assertSee('Phone / ID number')
            ->assertSee('Area')
            ->assertSee('Blood group')
            ->assertSee('Online students')
            ->assertSee('Offline students');
    }

    public function test_admin_role_sees_all_students_and_add_new_student_navigation(): void
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $this->actingAs($admin)
            ->get('/dashboard/students')
            ->assertSuccessful()
            ->assertSee('All Students')
            ->assertSee('Add New Student');
    }

    public function test_add_student_page_defaults_to_offline_and_lists_it_courses_without_a_school_class(): void
    {
        $admin = $this->createSuperAdmin();
        $course = Course::factory()->active()->create([
            'name' => 'Professional Offline Web Design',
            'delivery_mode' => 'offline',
            'class' => null,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard/students/create?mode=offline')
            ->assertSuccessful()
            ->assertSee('Add New Student')
            ->assertSee($course->name)
            ->assertSee('value="offline" selected', false)
            ->assertSee('Batch (Optional)');
    }

    public function test_admin_can_create_student_with_profile_image_upload(): void
    {
        Storage::fake('public');
        $admin = $this->createSuperAdmin();
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $response = $this->actingAs($admin)->post('/dashboard/students', [
            'name_bn' => 'Uploaded Student',
            'email' => 'uploaded.student@example.com',
            'phone' => '01700000000',
            'admission_mode' => 'offline',
            'password' => 'Strong!Pass123',
            'password_confirmation' => 'Strong!Pass123',
            'profile_image_file' => UploadedFile::fake()->image('student.jpg', 400, 400),
        ]);

        $response->assertRedirect('/dashboard/students');
        $student = Student::where('phone', '01700000000')->firstOrFail();
        $this->assertStringStartsWith('students/profiles/', $student->profile_image);
        Storage::disk('public')->assertExists($student->profile_image);
    }

    private function createSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
