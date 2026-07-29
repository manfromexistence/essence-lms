<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_home_page_boots(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('images/brand/dhaka-it-institute-favicon.png?v=20260729', false)
            ->assertSee('data-hero-slider', false)
            ->assertSee('images.unsplash.com/photo-', false);
    }

    public function test_courses_page_uses_professional_image_fallbacks(): void
    {
        $this->get('/courses')
            ->assertSuccessful()
            ->assertSee('images.unsplash.com/photo-', false)
            ->assertDontSee('via.placeholder.com', false);
    }

    public function test_dhaka_it_favicon_assets_are_present(): void
    {
        $this->assertFileExists(public_path('images/brand/dhaka-it-institute-favicon.png'));
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('favicon-32x32.png'));
        $this->assertFileExists(public_path('favicon-16x16.png'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
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
