<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_submission_is_stored(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);

        $this->post('/contact', [
            'name' => 'Test Sender',
            'email' => 'sender@test.com',
            'subject' => 'Test Subject',
            'message' => 'Hello, this is a test message body.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Test Sender',
            'email' => 'sender@test.com',
            'subject' => 'Test Subject',
            'message' => 'Hello, this is a test message body.',
            'status' => 'new',
        ]);
    }

    public function test_contact_submission_fails_validation_without_fields(): void
    {
        $this->post('/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_admin_can_view_and_update_contact_messages(): void
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $admin->roles()->attach($role);

        $message = ContactMessage::create([
            'name' => 'Dashboard Viewer',
            'email' => 'viewer@test.com',
            'subject' => 'Help needed',
            'message' => 'Please help with my course.',
            'status' => 'new',
        ]);

        // Index page shows the message with a "New" badge
        $this->actingAs($admin)->get('/dashboard/contact-messages')
            ->assertSuccessful()
            ->assertSee('Dashboard Viewer')
            ->assertSee('Help needed');

        // Show page marks it as read
        $this->actingAs($admin)->get('/dashboard/contact-messages/' . $message->id)
            ->assertSuccessful()
            ->assertSee('Please help with my course.');
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'status' => 'read']);

        // Update status to replied
        $this->actingAs($admin)->post('/dashboard/contact-messages/' . $message->id . '/status', ['status' => 'replied'])
            ->assertRedirect();
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'status' => 'replied']);

        // Delete
        $this->actingAs($admin)->delete('/dashboard/contact-messages/' . $message->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }
}
