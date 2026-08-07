<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds realistic course-LMS data: students with accounts, enrollments,
 * and payments, so the dashboard has meaningful data.
 *
 * Optimized for speed: no per-row N+1 queries, no external calls,
 * deterministic data spread across the last 6 months so charts show values.
 */
class CourseLmsDataSeeder extends Seeder
{
    public function run(): void
    {
        $studentRole = Role::where('slug', 'student')->first();
        $courses = Course::active()->get();
        if ($courses->isEmpty()) {
            $this->command?->warn('No active courses — run CourseSeeder first.');
            return;
        }

        // Preload once: all active batches grouped by course, and one admin id
        $batchesByCourse = Batch::where('status', 'active')->get()->groupBy('course_id');
        $adminId = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super-admin', 'admin']))->value('id');

        $names = [
            'Abdul Karim', 'Salma Khatun', 'Rakib Hasan', 'Nusrat Jahan', 'Imran Hossain',
            'Tania Akter', 'Sabbir Ahmed', 'Mim Sultana', 'Tanvir Islam', 'Rima Chowdhury',
            'Mehedi Hasan', 'Sadia Afrin', 'Arif Rahman', 'Fariha Tasnim', 'Nayeem Islam',
            'Sharmin Sultana', 'Rafiul Islam', 'Moumita Sen', 'Jahid Khan', 'Puja Das',
        ];

        $studentImages = [
            'https://images.unsplash.com/photo-1544717305-2782549b5136?w=600',
            'https://images.unsplash.com/photo-1491013516836-7dbc888c3867?w=600',
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=600',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=600',
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600',
        ];

        $paymentMethods = ['Cash', 'Bkash', 'Nagad', 'Rocket', 'Bank Transfer'];
        $genders = ['Male', 'Female'];
        $now = now();

        // Preload existing emails to avoid firstOrCreate SELECT churn
        $existingUsers = User::whereIn('email', array_map(fn ($i) => 'student' . ($i + 1) . '@example.com', array_keys($names)))
            ->pluck('id', 'email');

        $studentRoleId = $studentRole?->id;
        $existingRoleAssignments = $studentRoleId
            ? \Illuminate\Support\Facades\DB::table('role_user')->where('role_id', $studentRoleId)->pluck('user_id')->all()
            : [];
        $existingRoleSet = array_flip($existingRoleAssignments);

        $count = 0;

        foreach ($names as $i => $name) {
            $email = 'student' . ($i + 1) . '@example.com';
            $course = $courses[$i % $courses->count()];
            $batch = $batchesByCourse->get($course->id)?->first();

            // Create user only if missing
            if (!isset($existingUsers[$email])) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'must_change_password' => false,
                ]);
                $existingUsers[$email] = $user->id;
                if ($studentRoleId && !isset($existingRoleSet[$user->id])) {
                    \Illuminate\Support\Facades\DB::table('role_user')->insert([
                        'role_id' => $studentRoleId,
                        'user_id' => $user->id,
                    ]);
                    $existingRoleSet[$user->id] = true;
                }
            }

            $userId = $existingUsers[$email];

            // Spread created_at across the last 6 months so charts/trends show data
            $createdAt = $now->copy()->subDays(($i * 9) % 170);

            $student = Student::firstOrCreate(
                ['user_id' => $userId],
                [
                    'name_bn' => $name,
                    'phone' => '017' . str_pad((string) (10000000 + $i * 137), 8, '0', STR_PAD_LEFT),
                    'gender' => $genders[$i % 2],
                    'profile_image' => $studentImages[$i % count($studentImages)],
                    'batch_id' => $batch?->id,
                    'course_name' => $course->name,
                    'admission_mode' => $course->delivery_mode,
                    'admission_status' => 'approved',
                    'status' => 'active',
                    'applied_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            // Enrollment (skip if already exists)
            CourseEnrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id],
                ['batch_id' => $batch?->id, 'enrolled_at' => $createdAt->copy()->subDays(2)]
            );

            // Payment (mostly completed, some pending) — spread across 6 months
            $status = $i % 5 === 0 ? Payment::STATUS_PENDING : Payment::STATUS_COMPLETED;
            $paymentDate = $now->copy()->subDays(($i * 7) % 170)->toDateString();
            $paymentAmount = $course->price > 0 ? $course->price : (5000 + ($i % 5) * 2500);

            Payment::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id],
                [
                    'amount' => $paymentAmount,
                    'status' => $status,
                    'payment_method' => $paymentMethods[$i % count($paymentMethods)],
                    'payment_date' => $paymentDate,
                    'submitted_at' => $paymentDate . ' 12:00:00',
                    'reviewed_at' => $status === Payment::STATUS_COMPLETED ? $paymentDate . ' 13:00:00' : null,
                    'reviewed_by' => $status === Payment::STATUS_COMPLETED ? $adminId : null,
                    'transaction_id' => 'TRX-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT) . '-' . Str::upper(Str::random(6)),
                    'created_at' => $paymentDate . ' 12:00:00',
                    'updated_at' => $paymentDate . ' 12:00:00',
                ]
            );

            $count++;
        }

        $this->command?->info("Seeded {$count} students with enrollments and payments.");
    }
}
