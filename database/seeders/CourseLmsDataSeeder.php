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

        foreach ($names as $i => $name) {
            $email = 'student' . ($i + 1) . '@example.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'must_change_password' => false,
                ]
            );
            if ($studentRole && !$user->hasRole('student')) {
                $user->roles()->attach($studentRole->id);
            }

            $course = $courses[$i % $courses->count()];
            $batch = $course->batches()->first();

            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => '017' . rand(10000000, 99999999),
                    'gender' => $genders[$i % 2],
                    'profile_image' => $studentImages[$i % count($studentImages)],
                    'batch_id' => $batch?->id,
                    'course_name' => $course->name,
                    'admission_mode' => $course->delivery_mode,
                    'admission_status' => 'approved',
                    'status' => 'active',
                    'applied_at' => now()->subDays(rand(5, 90)),
                ]
            );

            // Enrollment
            CourseEnrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id],
                ['batch_id' => $batch?->id, 'enrolled_at' => now()->subDays(rand(3, 60))]
            );

            // Payment (mostly completed, some pending)
            $status = $i % 5 === 0 ? Payment::STATUS_PENDING : Payment::STATUS_COMPLETED;
            Payment::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id, 'transaction_id' => 'TRX' . Str::upper(Str::random(10))],
                [
                    'amount' => $course->price,
                    'status' => $status,
                    'payment_method' => $paymentMethods[$i % count($paymentMethods)],
                    'submitted_at' => now()->subDays(rand(3, 60)),
                    'reviewed_at' => $status === Payment::STATUS_COMPLETED ? now()->subDays(rand(1, 55)) : null,
                    'reviewed_by' => $status === Payment::STATUS_COMPLETED ? User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super-admin', 'admin']))->first()?->id : null,
                ]
            );
        }

        $this->command?->info('Seeded ' . count($names) . ' students with enrollments and payments.');
    }
}
