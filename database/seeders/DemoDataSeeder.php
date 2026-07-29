<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Course;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create default settings
        $this->createSettings();
        
        // Create message templates
        $this->createMessageTemplates();
        
        // Create demo courses
        $this->createCourses();
        
        // Create demo batches
        $this->createBatches();
        
        // Create demo teachers
        $this->createTeachers();
        
        // Create demo students
        $this->createStudents();
    }

    private function createSettings(): void
    {
        $settings = [
            ['key' => 'institution_name', 'value' => 'Dhaka IT Institute', 'group' => 'institution', 'type' => 'string'],
            ['key' => 'institution_address', 'value' => 'House #5, Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216', 'group' => 'institution', 'type' => 'string'],
            ['key' => 'institution_phone', 'value' => '+8801682715576', 'group' => 'institution', 'type' => 'string'],
            ['key' => 'institution_email', 'value' => 'dhakaitinstitute@gmail.com', 'group' => 'institution', 'type' => 'string'],
            ['key' => 'student_id_format', 'value' => 'STU-{YEAR}-{SEQ:4}', 'group' => 'student', 'type' => 'string'],
            ['key' => 'attendance_threshold', 'value' => '75', 'group' => 'attendance', 'type' => 'integer'],
            ['key' => 'sms_gateway', 'value' => 'bulk_sms_bd', 'group' => 'sms', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }

    private function createMessageTemplates(): void
    {
        $templates = [
            [
                'name' => 'Payment Reminder',
                'slug' => 'payment-reminder',
                'type' => 'sms',
                'content' => 'Dear Parent, {student_name} has a due payment of Tk.{amount}. Please pay by {due_date}.',
                'placeholders' => ['student_name', 'amount', 'due_date'],
            ],
            [
                'name' => 'Result Notification',
                'slug' => 'result-notification',
                'type' => 'sms',
                'content' => 'Dear Parent, {student_name} scored {marks}/{total} in {exam_name}. Grade: {grade}',
                'placeholders' => ['student_name', 'marks', 'total', 'exam_name', 'grade'],
            ],
            [
                'name' => 'Attendance Alert',
                'slug' => 'attendance-alert',
                'type' => 'sms',
                'content' => 'Dear Parent, {student_name} attendance is {percentage}%. Please ensure regular attendance.',
                'placeholders' => ['student_name', 'percentage'],
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(['slug' => $template['slug']], $template);
        }
    }

    private function createCourses(): void
    {
        $courses = [
            ['name' => 'HSC Science', 'code' => 'HSC-SCI', 'class' => 'HSC', 'price' => 5000, 'status' => 'active', 'level' => 'advanced'],
            ['name' => 'HSC Commerce', 'code' => 'HSC-COM', 'class' => 'HSC', 'price' => 4500, 'status' => 'active', 'level' => 'advanced'],
            ['name' => 'SSC Science', 'code' => 'SSC-SCI', 'class' => 'SSC', 'price' => 4000, 'status' => 'active', 'level' => 'intermediate'],
            ['name' => 'Class 8 All Subjects', 'code' => 'C8-ALL', 'class' => '8', 'price' => 3000, 'status' => 'active', 'level' => 'beginner'],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(['code' => $course['code']], $course);
        }
    }

    private function createBatches(): void
    {
        $courses = Course::all();
        
        foreach ($courses as $course) {
            Batch::updateOrCreate(
                ['code' => $course->code . '-A'],
                [
                    'name' => $course->name . ' - Batch A',
                    'course_id' => $course->id,
                    'schedule' => 'Sun, Tue, Thu - 4:00 PM',
                    'max_students' => 30,
                    'status' => 'active',
                ]
            );
            
            Batch::updateOrCreate(
                ['code' => $course->code . '-B'],
                [
                    'name' => $course->name . ' - Batch B',
                    'course_id' => $course->id,
                    'schedule' => 'Mon, Wed, Fri - 5:00 PM',
                    'max_students' => 30,
                    'status' => 'active',
                ]
            );
        }
    }

    private function createTeachers(): void
    {
        $teacherRole = Role::where('slug', 'teacher')->first();
        
        $teachers = [
            ['name' => 'Dr. Karim Ahmed', 'email' => 'karim@demo.com', 'department' => 'Physics', 'salary' => 25000],
            ['name' => 'Prof. Fatima Begum', 'email' => 'fatima@demo.com', 'department' => 'Chemistry', 'salary' => 28000],
            ['name' => 'Md. Rahim Khan', 'email' => 'rahim@demo.com', 'department' => 'Mathematics', 'salary' => 22000],
        ];

        foreach ($teachers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            
            if ($teacherRole) {
                $user->roles()->syncWithoutDetaching([$teacherRole->id]);
            }
            
            Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => '01700' . rand(100000, 999999),
                    'department' => $data['department'],
                    'salary' => $data['salary'],
                    'status' => 'active',
                ]
            );
        }
    }

    private function createStudents(): void
    {
        $studentRole = Role::where('slug', 'student')->first();
        $batches = Batch::all();
        
        $students = [
            ['name' => 'Rafiq Islam', 'email' => 'rafiq@demo.com'],
            ['name' => 'Nasreen Akter', 'email' => 'nasreen@demo.com'],
            ['name' => 'Jahid Hasan', 'email' => 'jahid@demo.com'],
            ['name' => 'Mita Rahman', 'email' => 'mita@demo.com'],
            ['name' => 'Sohel Rana', 'email' => 'sohel@demo.com'],
        ];

        foreach ($students as $index => $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            
            if ($studentRole) {
                $user->roles()->syncWithoutDetaching([$studentRole->id]);
            }
            
            $batch = $batches[$index % $batches->count()];
            
            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'registration_no' => 'STU-' . date('Y') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'batch_id' => $batch->id,
                    'phone' => '01800' . rand(100000, 999999),
                    'guardian_phone' => '01900' . rand(100000, 999999),
                ]
            );
        }
    }
}
