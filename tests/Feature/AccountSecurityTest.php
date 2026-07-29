<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_account_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'pending@example.com',
            'password' => Hash::make('Temporary!Password123'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'pending@example.com',
            'password' => 'Temporary!Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_temporary_account_is_forced_to_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Temporary!Password123'),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Temporary!Password123',
        ])->assertRedirect(route('password.change'));

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
    }

    public function test_password_reset_request_does_not_disclose_account_existence(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'missing@example.com'])
            ->assertSessionHas('success');
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertSuccessful();
    }
}
