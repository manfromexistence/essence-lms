<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateEditorTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_editor_page_renders_with_elements_and_variables(): void
    {
        $this->seed(\Database\Seeders\CertificateTemplateSeeder::class);
        $admin = $this->makeSuperAdmin();
        $template = CertificateTemplate::where('type', 'course_completion')->first();

        $response = $this->actingAs($admin)->get("/dashboard/certificates/templates/{$template->id}/edit");
        $response->assertStatus(200)
            ->assertSee('Certificate Template Editor')
            ->assertSee('Add Text')
            ->assertSee('Add Image')
            ->assertSee('student_name', false)
            ->assertSee('Live Preview');
    }

    public function test_editor_saves_layout_config(): void
    {
        $this->seed(\Database\Seeders\CertificateTemplateSeeder::class);
        $admin = $this->makeSuperAdmin();
        $template = CertificateTemplate::where('type', 'course_completion')->first();

        $layout = [
            ['type' => 'text', 'content' => '{student_name}', 'x' => 100, 'y' => 200, 'width' => 900, 'fontSize' => 50, 'fontFamily' => 'Georgia, serif', 'color' => '#ff0000', 'bold' => true, 'align' => 'center'],
            ['type' => 'image', 'imageField' => 'logo', 'x' => 500, 'y' => 50, 'width' => 150, 'height' => 60],
        ];

        $this->actingAs($admin)->put("/dashboard/certificates/templates/{$template->id}", [
            'name' => $template->name,
            'type' => $template->type,
            'width' => 1200,
            'height' => 900,
            'is_active' => 1,
            'is_default' => 1,
            'layout_config' => json_encode($layout),
        ])->assertRedirect();

        $template->refresh();
        $this->assertCount(2, $template->layout_config);
        $this->assertEquals('#ff0000', $template->layout_config[0]['color']);
    }

    public function test_certificate_show_renders_from_layout_config(): void
    {
        $this->seed(\Database\Seeders\CertificateTemplateSeeder::class);
        $admin = $this->makeSuperAdmin();
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $studentUser = User::factory()->create(['name' => 'Layout Student', 'is_active' => true, 'must_change_password' => false]);
        $studentUser->roles()->attach($studentRole);
        $student = Student::factory()->create(['user_id' => $studentUser->id, 'name_bn' => 'Layout Student']);
        $course = Course::factory()->create(['name' => 'Layout Course']);

        $cert = app(\App\Services\CertificateService::class)->issue($student, $course, $admin);

        $response = $this->actingAs($studentUser)->get("/student/certificates/{$cert->id}");
        $response->assertStatus(200)
            ->assertSee('Layout Student')
            ->assertSee('Layout Course')
            ->assertSee($cert->certificate_number);
    }

    public function test_templates_index_shows_preview(): void
    {
        $this->seed(\Database\Seeders\CertificateTemplateSeeder::class);
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->get('/dashboard/certificates/templates');
        $response->assertStatus(200)
            ->assertSee('Design Editor')
            ->assertSee('Dhaka IT Course Completion');
    }
}
