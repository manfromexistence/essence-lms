<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The header toggle switches the admin dashboard between online and offline
 * course delivery views; the choice must persist across page loads.
 */
class CourseModeToggleTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_toggle_between_online_and_offline_mode(): void
    {
        $admin = $this->makeUserWithRole('super-admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->assertVisible('form[aria-label="Course delivery mode"]');

            // Online is the default; the offline button becomes active on click.
            $browser->click('form[aria-label="Course delivery mode"] button[value="offline"]')
                ->waitForLocation('/dashboard')
                ->assertPresent('form[aria-label="Course delivery mode"] button[value="offline"].bg-black')
                ->assertSee('Offline');

            // And back to online.
            $browser->click('form[aria-label="Course delivery mode"] button[value="online"]')
                ->waitForLocation('/dashboard')
                ->assertPresent('form[aria-label="Course delivery mode"] button[value="online"].bg-green-700');
        });
    }

    private function makeUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]);
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $user->roles()->attach($role);

        return $user;
    }
}
