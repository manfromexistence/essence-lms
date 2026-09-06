<?php

namespace Database\Seeders;

use App\Models\ParentModel;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the support accounts needed to verify each portal.
 *
 * Production safety: no default passwords exist in this file. Every account
 * requires an explicit DEFAULT_*_EMAIL + DEFAULT_*_PASSWORD pair (16+
 * characters). Missing or weak credentials are skipped with a warning, so a
 * fresh production deploy can never be logged into with a public password.
 *
 * This seeder is deliberately idempotent: it can run on every container
 * start without overwriting a password that an administrator has changed.
 */
class DefaultRoleAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            'super-admin' => [
                'email' => env('DEFAULT_SUPER_ADMIN_EMAIL'),
                'password' => env('DEFAULT_SUPER_ADMIN_PASSWORD'),
                'name' => 'Dhaka IT Institute Super Admin',
            ],
            'admin' => [
                'email' => env('DEFAULT_ADMIN_EMAIL'),
                'password' => env('DEFAULT_ADMIN_PASSWORD'),
                'name' => 'Dhaka IT Institute Administrator',
            ],
            'teacher' => [
                'email' => env('DEFAULT_TEACHER_EMAIL'),
                'password' => env('DEFAULT_TEACHER_PASSWORD'),
                'name' => 'Dhaka IT Institute Instructor',
            ],
            'student' => [
                'email' => env('DEFAULT_STUDENT_EMAIL'),
                'password' => env('DEFAULT_STUDENT_PASSWORD'),
                'name' => 'Dhaka IT Institute Demo Student',
            ],
            'parent' => [
                'email' => env('DEFAULT_PARENT_EMAIL'),
                'password' => env('DEFAULT_PARENT_PASSWORD'),
                'name' => 'Dhaka IT Institute Demo Parent',
            ],
        ];

        foreach ($accounts as $slug => $account) {
            if (empty($account['email']) || empty($account['password']) || strlen((string) $account['password']) < 16) {
                $this->command?->warn("Skipping {$slug} support account: set DEFAULT_" . strtoupper(str_replace('-', '_', $slug)) . '_EMAIL and a 16+ character DEFAULT_' . strtoupper(str_replace('-', '_', $slug)) . '_PASSWORD.');
                continue;
            }
            $role = Role::where('slug', $slug)->first();
            if (!$role) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'must_change_password' => false,
                ],
            );
            $user->roles()->syncWithoutDetaching([$role->id]);

            if ($slug === 'student') {
                Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name_bn' => $account['name'],
                        'phone' => env('DEFAULT_STUDENT_PHONE', '01700000000'),
                        'admission_mode' => 'online',
                        'admission_status' => 'approved',
                        'status' => 'active',
                        'applied_at' => now(),
                    ],
                );
            }

            if ($slug === 'parent') {
                $parent = ParentModel::firstOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'phone' => env('DEFAULT_PARENT_PHONE', '01700000000'),
                        'password' => Hash::make($account['password']),
                        'email_verified_at' => now(),
                        'phone_verified_at' => now(),
                        'notification_preferences' => [
                            'email_notifications' => true,
                            'sms_notifications' => false,
                            'exam_alerts' => true,
                            'attendance_alerts' => true,
                            'payment_reminders' => true,
                        ],
                    ],
                );

                $student = !empty($accounts['student']['email'])
                    ? Student::whereHas('user', fn ($query) => $query->where('email', $accounts['student']['email']))->first()
                    : null;
                if ($student && !$parent->students()->whereKey($student->id)->exists()) {
                    $parent->students()->attach($student->id, [
                        'relationship_type' => 'guardian',
                        'status' => 'approved',
                        'approved_by' => User::where('email', $accounts['super-admin']['email'])->value('id'),
                        'approved_at' => now(),
                    ]);
                }
            }
        }
    }
}
