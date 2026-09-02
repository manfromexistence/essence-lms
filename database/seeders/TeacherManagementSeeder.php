<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherCategory;
use App\Models\TeacherSalary;
use App\Models\Role;
use App\Models\Batch;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TeacherManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Teacher Categories
        $categories = [
            ['name' => 'Senior Lecturer', 'slug' => 'senior-lecturer'],
            ['name' => 'Junior Lecturer', 'slug' => 'junior-lecturer'],
            ['name' => 'Guest Teacher', 'slug' => 'guest-teacher'],
            ['name' => 'Lab Instructor', 'slug' => 'lab-instructor'],
        ];

        foreach ($categories as $cat) {
            TeacherCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $allCats = TeacherCategory::all();
        $teacherRole = Role::where('slug', 'teacher')->first();
        $batches = Batch::all();

        // 2. Create Teachers
        $teachersData = [
            [
                'name' => 'Demo Teacher',
                'email' => 'teacher@gmail.com',
                'department' => 'Computer Science',
                'salary' => 50000,
                'image' => '/images/team-kawsar.jpg',
            ],
            [
                'name' => 'Dr. Arifuzzaman',
                'email' => 'arif@example.com',
                'department' => 'Computer Science',
                'salary' => 55000,
                'image' => '/images/team-mikdad.jpg',
            ],
            [
                'name' => 'Tahmid Hasan',
                'email' => 'tahmid@example.com',
                'department' => 'Graphic Design',
                'salary' => 45000,
                'image' => '/images/team-galib.jpg',
            ],
            [
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat@example.com',
                'department' => 'Digital Marketing',
                'salary' => 42000,
                'image' => '/images/team-hasibur.jpg',
            ],
        ];

        foreach ($teachersData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                ]
            );

            if ($teacherRole && !$user->hasRole('teacher')) {
                $user->roles()->attach($teacherRole->id);
            }

            $teacher = Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => '017' . rand(10000000, 99999999),
                    'department' => $data['department'],
                    'salary' => $data['salary'],
                    'category_id' => $allCats->random()->id,
                    'profile_image' => $data['image'],
                    'subjects' => ['Programming', 'Design', 'Marketing'],
                ]
            );

            // 3. Assign to random batches
            if ($batches->count()) {
                $teacher->batches()->sync($batches->random(2)->pluck('id'));
            }

            // 4. Seed Salary History (Last 3 months)
            for ($i = 1; $i <= 3; $i++) {
                TeacherSalary::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'month' => Carbon::now()->subMonths($i)->format('F Y'),
                    ],
                    [
                        'amount' => $teacher->salary,
                        'status' => 'paid',
                        'payment_date' => Carbon::now()->subMonths($i)->startOfMonth()->addDays(5),
                    ]
                );
            }
        }
    }
}
