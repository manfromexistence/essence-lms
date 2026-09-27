<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DefaultRoleAccountsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_home_page_boots(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('images/brand/dhaka-it-institute-favicon.png?v=20260802', false)
            ->assertSee('images/brand/dhaka-it-institute-logo.png?v=20260802', false)
            ->assertSee('data-hero-slider', false)
            ->assertSee('/images/', false);
    }

    public function test_courses_page_uses_professional_image_fallbacks(): void
    {
        $this->get('/courses')
            ->assertSuccessful()
            ->assertSee('/images/', false)
            ->assertDontSee('via.placeholder.com', false);
    }

    public function test_password_forms_expose_show_hide_control_and_symbol_requirement(): void
    {
        // The PUBLIC admission form no longer collects a password — applicants
        // apply first and receive generated credentials on approval.
        $this->get('/admission')
            ->assertSuccessful()
            ->assertDontSee('name="password"', false)
            ->assertDontSee('data-password-toggle', false);

        // The admin "Add Student" form (creator sets the password) still exposes
        // the show/hide control and the strength/symbol requirements.
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin)
            ->get('/dashboard/students/create')
            ->assertSuccessful()
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-password-eye="show"', false)
            ->assertSee('togglePasswordVisibility', false);

        // The post-approval change-password screen keeps the same controls.
        $user = \App\Models\User::factory()->create(['must_change_password' => true]);
        $this->actingAs($user)
            ->get('/change-password')
            ->assertSuccessful()
            ->assertSee('data-password-toggle', false);
    }

    /**
     * Create an authenticated super-admin for admin-area assertions.
     */
    private function createSuperAdmin(): \App\Models\User
    {
        $this->seed(RoleSeeder::class);
        $role = \App\Models\Role::where('slug', 'super-admin')->first();
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        if ($role) {
            $user->roles()->attach($role->id);
        }

        return $user;
    }

    public function test_dhaka_it_favicon_assets_are_present(): void
    {
        $this->assertFileExists(public_path('images/brand/dhaka-it-institute-favicon.png'));
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('favicon-32x32.png'));
        $this->assertFileExists(public_path('favicon-16x16.png'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
    }

    public function test_s3_filesystem_adapter_is_available_for_render_storage(): void
    {
        $this->assertTrue(class_exists(\League\Flysystem\AwsS3V3\PortableVisibilityConverter::class));
    }

    public function test_default_role_accounts_require_explicit_credentials(): void
    {
        $this->seed(RoleSeeder::class);

        foreach (['super-admin', 'admin', 'teacher', 'student', 'parent'] as $role) {
            $this->assertDatabaseHas('roles', ['slug' => $role]);
        }

        // No credentials configured in testing env → no accounts created.
        $this->seed(DefaultRoleAccountsSeeder::class);
        $this->assertDatabaseCount('users', 0);

        // Explicit 16+ char credentials create idempotent accounts with profiles.
        foreach (['super-admin', 'admin', 'teacher', 'student', 'parent'] as $role) {
            $key = 'DEFAULT_' . strtoupper(str_replace('-', '_', $role));
            putenv("{$key}_EMAIL={$role}@dhakaitinstitute.test");
            $_ENV["{$key}_EMAIL"] = "{$role}@dhakaitinstitute.test";
            putenv("{$key}_PASSWORD=Testing-Only-Password-123!");
            $_ENV["{$key}_PASSWORD"] = 'Testing-Only-Password-123!';
        }

        try {
            $this->seed(DefaultRoleAccountsSeeder::class);

            $student = User::where('email', 'student@dhakaitinstitute.test')->firstOrFail();
            $this->assertTrue($student->hasRole('student'));
            $this->assertNotNull($student->student);
            $this->assertSame('approved', $student->student->admission_status);

            $parent = User::where('email', 'parent@dhakaitinstitute.test')->firstOrFail();
            $this->assertTrue($parent->hasRole('parent'));
            $this->assertDatabaseHas('parents', ['email' => 'parent@dhakaitinstitute.test']);

            $originalHash = $student->password;
            $this->seed(DefaultRoleAccountsSeeder::class);
            $this->assertSame($originalHash, User::find($student->id)->password);
        } finally {
            foreach (['super-admin', 'admin', 'teacher', 'student', 'parent'] as $role) {
                $key = 'DEFAULT_' . strtoupper(str_replace('-', '_', $role));
                putenv($key . '_EMAIL');
                putenv($key . '_PASSWORD');
                unset($_ENV[$key . '_EMAIL'], $_ENV[$key . '_PASSWORD']);
            }
        }
    }

    public function test_debug_upload_routes_are_not_exposed(): void
    {
        $this->get('/test-upload')->assertNotFound();
        $this->post('/test-upload/process')->assertNotFound();
    }

    public function test_logout_is_not_available_over_get(): void
    {
        $this->get('/logout')->assertMethodNotAllowed();
    }

    public function test_guest_cannot_access_admin_or_student_payment_proofs(): void
    {
        $this->get('/admin/payment-review/1/proof')->assertRedirect('/login');
        $this->get('/student/payment/1/proof')->assertRedirect('/login');
    }

    public function test_super_admin_dashboard_layout_renders(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/dashboard')->assertSuccessful();
    }
}
