<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $email = env('INITIAL_ADMIN_EMAIL');
            $password = env('INITIAL_ADMIN_PASSWORD');
            if (!$email || !$password || strlen($password) < 16) {
                $this->command?->warn('Skipping initial admin: set INITIAL_ADMIN_EMAIL and a 16+ character INITIAL_ADMIN_PASSWORD.');
                return;
            }

            $superAdmin = User::firstOrCreate(
                ['email' => $email],
                ['name' => 'System Owner', 'password' => Hash::make($password), 'email_verified_at' => now()]
            );
            if ($role = Role::where('slug', 'super-admin')->first()) {
                $superAdmin->roles()->syncWithoutDetaching([$role->id]);
            }
            return;
        }

        // Create Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'owner@dhakaitinstitute.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Super Admin role
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Create Admin user with admin@gmail.com as Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Super Admin role to admin@gmail.com
        if ($superAdminRole) {
            $admin->roles()->sync([$superAdminRole->id]);
        }

        // Create a local-only secondary administrator account
        $adminAlpha = User::updateOrCreate(
            ['email' => 'admin@dhakaitinstitute.test'],
            [
                'name' => 'Dhaka IT Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Super Admin role to the local administrator
        if ($superAdminRole) {
            $adminAlpha->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Create sample Teacher user with teacher@gmail.com
        $teacher = User::updateOrCreate(
            ['email' => 'teacher@gmail.com'],
            [
                'name' => 'Demo Teacher',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Teacher role
        $teacherRole = Role::where('slug', 'teacher')->first();
        if ($teacherRole) {
            $teacher->roles()->syncWithoutDetaching([$teacherRole->id]);
        }

        // Create a local-only teacher account
        $teacherAlpha = User::updateOrCreate(
            ['email' => 'teacher@dhakaitinstitute.test'],
            [
                'name' => 'Dhaka IT Demo Teacher',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($teacherRole) {
            $teacherAlpha->roles()->syncWithoutDetaching([$teacherRole->id]);
        }

        // Create sample Student user with student@gmail.com
        $student = User::updateOrCreate(
            ['email' => 'student@gmail.com'],
            [
                'name' => 'Demo Student',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Student role
        $studentRole = Role::where('slug', 'student')->first();
        if ($studentRole) {
            $student->roles()->syncWithoutDetaching([$studentRole->id]);
        }

        // Create a local-only student account
        $studentAlpha = User::updateOrCreate(
            ['email' => 'student@dhakaitinstitute.test'],
            [
                'name' => 'Dhaka IT Demo Student',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($studentRole) {
            $studentAlpha->roles()->syncWithoutDetaching([$studentRole->id]);
        }
    }
}
