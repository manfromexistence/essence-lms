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
 * This seeder is deliberately idempotent: it can run on every container
 * start without overwriting a password that an administrator has changed.
 */
class DefaultRoleAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            'super-admin' => [
                'email' => env('DEFAULT_SUPER_ADMIN_EMAIL', 'superadmin@dhakaitinstitute.com'),
                'password' => env('DEFAULT_SUPER_ADMIN_PASSWORD', 'Dii!SuperAdmin-2026#X9'),
                'name' => 'Dhaka IT Institute Super Admin',
            ],
            'admin' => [
                'email' => env('DEFAULT_ADMIN_EMAIL', 'admin@dhakaitinstitute.com'),
                'password' => env('DEFAULT_ADMIN_PASSWORD', 'Dii!Admin-2026#X9'),
                'name' => 'Dhaka IT Institute Administrator',
            ],
            'teacher' => [
                'email' => env('DEFAULT_TEACHER_EMAIL', 'teacher@dhakaitinstitute.com'),
                'password' => env('DEFAULT_TEACHER_PASSWORD', 'Dii!Teacher-2026#X9'),
                'name' => 'Dhaka IT Institute Instructor',
            ],
            'student' => [
                'email' => env('DEFAULT_STUDENT_EMAIL', 'student@dhakaitinstitute.com'),
                'password' => env('DEFAULT_STUDENT_PASSWORD', 'Dii!Student-2026#X9'),
                'name' => 'Dhaka IT Institute Demo Student',
            ],
            'parent' => [
                'email' => env('DEFAULT_PARENT_EMAIL', 'parent@dhakaitinstitute.com'),
                'password' => env('DEFAULT_PARENT_PASSWORD', 'Dii!Parent-2026#X9'),
                'name' => 'Dhaka IT Institute Demo Parent',
            ],
        ];

        foreach ($accounts as $slug => $account) {
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
                        'phone' => '01682715570',
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
                        'phone' => '01682715571',
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

                $student = Student::whereHas('user', fn ($query) => $query->where('email', $accounts['student']['email']))->first();
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
