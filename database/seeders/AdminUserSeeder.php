<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Fast hash for demo accounts — cost 4 keeps seeding quick
     * (production admin uses the env password at default cost).
     */
    private function quickHash(string $password): string
    {
        return Hash::make($password, ['rounds' => 4]);
    }

    public function run(): void
    {
        // The primary admin comes from env (same credentials work in any
        // environment). Falls back to local demo accounts when unset.
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');
        if ($email && $password && strlen($password) >= 16) {
            $superAdmin = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'System Owner',
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );
            if ($role = Role::where('slug', 'super-admin')->first()) {
                $superAdmin->roles()->syncWithoutDetaching([$role->id]);
            }
            if (app()->environment('production')) {
                return; // production only gets the env admin
            }
        } elseif (app()->environment('production')) {
            $this->command?->warn('Skipping initial admin: set INITIAL_ADMIN_EMAIL and a 16+ character INITIAL_ADMIN_PASSWORD.');
            return;
        }

        // Create Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'owner@dhakaitinstitute.test'],
            [
                'name' => 'Super Admin',
                'password' => $this->quickHash('password'),
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
                'password' => $this->quickHash('password'),
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
                'password' => $this->quickHash('password'),
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
                'password' => $this->quickHash('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Teacher role
        $teacherRole = Role::where('slug', 'teacher')->first();
        if ($teacherRole) {
            $teacher->roles()->syncWithoutDetaching([$teacherRole->id]);
        }

        // Every teacher login needs a Teacher profile, otherwise the teacher
        // portal redirects straight back to the generic dashboard.
        Teacher::firstOrCreate(
            ['user_id' => $teacher->id],
            [
                'phone' => '01700000000',
                'designation' => 'Instructor',
                'status' => 'active',
            ]
        );

        // Create a local-only teacher account
        $teacherAlpha = User::updateOrCreate(
            ['email' => 'teacher@dhakaitinstitute.test'],
            [
                'name' => 'Dhaka IT Demo Teacher',
                'password' => $this->quickHash('password'),
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
                'password' => $this->quickHash('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Student role
        $studentRole = Role::where('slug', 'student')->first();
        if ($studentRole) {
            $student->roles()->syncWithoutDetaching([$studentRole->id]);
        }

        // Every student login needs a Student profile; without it the student
        // portal bounces back to the generic dashboard in a redirect loop.
        // The batch is attached later by DemoAccountBatchSeeder, which runs
        // after BatchSeeder (batches do not exist yet at this point).
        Student::firstOrCreate(
            ['user_id' => $student->id],
            [
                'name_bn' => $student->name,
                'phone' => '01700000000',
                'admission_mode' => 'online',
                'admission_status' => 'approved',
                'status' => 'active',
                'registration_no' => 'REG-DEMO-0001',
                'applied_at' => now(),
            ]
        );

        // Create a local-only student account
        $studentAlpha = User::updateOrCreate(
            ['email' => 'student@dhakaitinstitute.test'],
            [
                'name' => 'Dhaka IT Demo Student',
                'password' => $this->quickHash('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($studentRole) {
            $studentAlpha->roles()->syncWithoutDetaching([$studentRole->id]);
        }

        // Give the local-only student account a profile too so the student
        // portal is reachable with it (batch attached later, see above).
        Student::firstOrCreate(
            ['user_id' => $studentAlpha->id],
            [
                'name_bn' => $studentAlpha->name,
                'phone' => '01700000000',
                'admission_mode' => 'online',
                'admission_status' => 'approved',
                'status' => 'active',
                'registration_no' => 'REG-DEMO-0002',
                'applied_at' => now(),
            ]
        );
    }
}
