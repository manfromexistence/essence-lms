<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_home_page_boots(): void
    {
        $this->get('/')->assertSuccessful();
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
}
