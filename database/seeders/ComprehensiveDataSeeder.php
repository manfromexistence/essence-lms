<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Course, CourseVideo, CourseMaterial, Batch, Student, Teacher, ClassSchedule,
    Attendance, Exam, Question, ExamResult, ExamAttempt,
    Invoice, Payment, ParentModel, Announcement, User, Setting,
    Income, Expense, InventoryItem, InventoryTransaction,
    MessageTemplate, SmsLog, ActivityLog
};
use Illuminate\Support\Facades\Hash;

class ComprehensiveDataSeeder extends Seeder
{
    /**
     * Run comprehensive database seeding for all admin panel pages.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive data seeding...');
        
        // Seed in order of dependencies
        $this->seedFooterSettings();
        $this->seedCourseVideos();
        $this->seedCourseMaterials();
        $this->seedClassSchedules();
        $this->seedAttendanceRecords();
        $this->seedExamsAndResults();
        $this->seedInvoicesAndPayments();
        $this->seedParentAccounts();
        $this->seedAnnouncements();
        
        // New seeders for admin panel pages
        $this->seedAccountsData();
        $this->seedInventoryData();
        $this->seedCommunicationData();
        $this->seedActivityLogs();
        
        $this->command->info('✅ Comprehensive seeding completed successfully!');
    }

    /**
     * Seed footer settings
     */
    private function seedFooterSettings(): void
    {
        $this->command->info('⚙️ Seeding footer settings...');
        
        Setting::updateOrCreate(
            ['key' => 'footer_text'],
            [
                'value' => 'Dhaka IT Institute — Let’s Build Your Dream',
                'group' => 'general',
                'type' => 'string',
            ]
        );
        
        Setting::updateOrCreate(
            ['key' => 'footer_copyright'],
            [
                'value' => '© ' . date('Y') . ' Dhaka IT Institute. All rights reserved.',
                'group' => 'general',
                'type' => 'string',
            ]
        );
        
        $this->command->info('✓ Footer settings created');
    }

    /**
     * Seed course videos with real educational content from YouTube
     */
    private function seedCourseVideos(): void
    {
        $this->command->info('📹 Seeding course videos...');
        
        // Real free educational YouTube videos
        $educationalVideos = [
            // Programming & Computer Science
            ['id' => 'RF_eOpCLayA', 'title' => 'Python Programming - Complete Course', 'duration' => 14400],
            ['id' => 'PkZNo7MFNFg', 'title' => 'JavaScript Full Course for Beginners', 'duration' => 11700],
            ['id' => 'pQN-pnXPaVg', 'title' => 'HTML Tutorial - Build a Website', 'duration' => 7200],
            ['id' => 'yfoY53QXEnI', 'title' => 'CSS Complete Course - Zero to Hero', 'duration' => 10800],
            ['id' => 'RGOj5yH7evk', 'title' => 'Git and GitHub for Beginners', 'duration' => 3900],
            
            // Mathematics
            ['id' => 'WUvTyaaNkzM', 'title' => 'Calculus 1 - Full College Course', 'duration' => 21600],
            ['id' => 'LwCRRUa8yTU', 'title' => 'Algebra - Basic Introduction', 'duration' => 9000],
            ['id' => 'fNk_zzaMoSs', 'title' => 'Linear Algebra Full Course', 'duration' => 18000],
            
            // Science
            ['id' => 'cbJOAiz0WN8', 'title' => 'Physics - Introduction to Mechanics', 'duration' => 12600],
            ['id' => 'FSyAehMdpyI', 'title' => 'Chemistry - Complete Course', 'duration' => 16200],
            ['id' => 'QnQe0xW_JY4', 'title' => 'Biology - Cell Structure and Function', 'duration' => 5400],
            
            // English & Language
            ['id' => 'Rjd4_Wn0kxI', 'title' => 'English Grammar Course for Beginners', 'duration' => 7200],
            ['id' => 'ub3HcgmFmJY', 'title' => 'English Vocabulary Building', 'duration' => 3600],
            
            // Additional Educational Content
            ['id' => 'nu_pCVPKzTk', 'title' => 'React Tutorial for Beginners', 'duration' => 8100],
            ['id' => 'SLpUKAGnm-g', 'title' => 'SQL Database Course', 'duration' => 14400],
            ['id' => 'Wm6CUkswsNw', 'title' => 'Data Structures and Algorithms', 'duration' => 36000],
        ];
        
        $courses = Course::all();
        
        if ($courses->isEmpty()) {
            $this->command->warn('No courses found. Skipping video seeding.');
            return;
        }
        
        $videoCount = 0;
        
        foreach ($courses as $course) {
            // Add 5-8 videos per course
            $numVideos = rand(5, 8);
            
            for ($i = 1; $i <= $numVideos; $i++) {
                $video = $educationalVideos[array_rand($educationalVideos)];
                
                CourseVideo::create([
                    'course_id' => $course->id,
                    'title' => "Lesson {$i}: " . $video['title'],
                    'description' => "This comprehensive lesson covers important concepts in {$course->name}. Watch carefully and take notes for better understanding.",
                    'video_type' => 'youtube',
                    'external_id' => $video['id'],
                    'duration' => $video['duration'],
                    'order' => $i,
                    'is_preview' => $i === 1, // First video is preview
                ]);
                
                $videoCount++;
            }
        }
        
        $this->command->info("✓ Created {$videoCount} course videos");
    }

    /**
     * Seed course materials (PDFs, documents, links)
     */
    private function seedCourseMaterials(): void
    {
        $this->command->info('📚 Seeding course materials...');
        
        $courses = Course::all();
        
        if ($courses->isEmpty()) {
            $this->command->warn('No courses found. Skipping materials seeding.');
            return;
        }
        
        $materialTypes = ['pdf', 'document', 'link', 'video'];
        $materialCount = 0;
        
        foreach ($courses as $course) {
            // Add 5-10 materials per course
            $numMaterials = rand(5, 10);
            
            for ($i = 1; $i <= $numMaterials; $i++) {
                $type = $materialTypes[array_rand($materialTypes)];
                
                $materialData = [
                    'course_id' => $course->id,
                    'title' => $this->generateMaterialTitle($type, $i),
                    'type' => $type,
                    'description' => $this->generateMaterialDescription($type),
                    'order' => $i,
                ];
                
                // Add appropriate URL based on type
                if ($type === 'link') {
                    $materialData['external_url'] = 'https://example.com/resource-' . $i;
                } elseif ($type === 'video') {
                    $materialData['external_url'] = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                } else {
                    // For PDF and documents, use a placeholder path
                    $materialData['file_path'] = "materials/{$course->code}/material-{$i}.pdf";
                }
                
                CourseMaterial::create($materialData);
                $materialCount++;
            }
        }
        
        $this->command->info("✓ Created {$materialCount} course materials");
    }

    /**
     * Seed announcements for different target types
     */
    private function seedAnnouncements(): void
    {
        $this->command->info('📢 Seeding announcements...');
        
        $adminUser = User::whereHas('roles', function($q) {
            $q->where('slug', 'admin');
        })->first();
        
        if (!$adminUser) {
            $adminUser = User::first();
        }
        
        if (!$adminUser) {
            $this->command->warn('No admin user found. Skipping announcement seeding.');
            return;
        }
        
        // Create exactly 5 active announcements for homepage display
        $announcements = [
            [
                'title' => 'Welcome to the New Academic Year 2026!',
                'content' => 'We are excited to welcome all students to the new academic year. Please check your schedules and ensure you have all required materials. Our dedicated faculty is ready to support your learning journey.',
                'priority' => 'high',
                'starts_at' => now()->subDays(30),
                'expires_at' => now()->addDays(60),
            ],
            [
                'title' => 'Important: Exam Schedule Released',
                'content' => 'The examination schedule for this term has been released. Please check your student portal for detailed information about exam dates, timings, and venues. Prepare well and good luck!',
                'priority' => 'urgent',
                'starts_at' => now()->subDays(10),
                'expires_at' => now()->addDays(30),
            ],
            [
                'title' => 'Library Hours Extended for Students',
                'content' => 'Good news! Library hours have been extended until 9 PM on weekdays to support your studies. Take advantage of the quiet study spaces and extensive resources available.',
                'priority' => 'normal',
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addDays(90),
            ],
            [
                'title' => 'Payment Reminder - Fee Deadline Approaching',
                'content' => 'This is a friendly reminder to clear any pending fees by the end of this month to avoid late charges. You can pay online through your student portal or visit the accounts office.',
                'priority' => 'high',
                'starts_at' => now()->subDays(3),
                'expires_at' => now()->addDays(20),
            ],
            [
                'title' => 'New Course Materials and Videos Available',
                'content' => 'New study materials, video lectures, and practice resources have been uploaded to all courses. Check your course materials section regularly for updates and additional learning resources.',
                'priority' => 'normal',
                'starts_at' => now()->subDays(1),
                'expires_at' => now()->addDays(45),
            ],
        ];
        
        $announcementCount = 0;
        
        foreach ($announcements as $data) {
            Announcement::updateOrCreate(
                ['title' => $data['title']],
                [
                    'content' => $data['content'],
                    'target_type' => 'all',
                    'target_id' => null,
                    'priority' => $data['priority'],
                    'starts_at' => $data['starts_at'],
                    'expires_at' => $data['expires_at'],
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );
            $announcementCount++;
        }
        
        $this->command->info("✓ Created {$announcementCount} announcements for homepage display");
    }

    /**
     * Seed class schedules for all batches
     */
    private function seedClassSchedules(): void
    {
        $this->command->info('📅 Seeding class schedules...');
        
        $batches = Batch::all();
        $teachers = Teacher::all();
        
        if ($batches->isEmpty()) {
            $this->command->warn('No batches found. Skipping schedule seeding.');
            return;
        }
        
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];
        $subjects = [
            'Mathematics', 'English', 'Physics', 'Chemistry', 'Biology',
            'Computer Science', 'Bangla', 'Islamic Studies', 'History',
            'Geography', 'Economics', 'Accounting'
        ];
        
        $timeSlots = [
            ['start' => '08:00:00', 'end' => '09:30:00'],
            ['start' => '09:45:00', 'end' => '11:15:00'],
            ['start' => '11:30:00', 'end' => '13:00:00'],
            ['start' => '14:00:00', 'end' => '15:30:00'],
            ['start' => '15:45:00', 'end' => '17:15:00'],
            ['start' => '17:30:00', 'end' => '19:00:00'],
        ];
        
        $rooms = ['Room 101', 'Room 102', 'Room 103', 'Room 201', 'Room 202', 'Lab 1', 'Lab 2', 'Computer Lab'];
        
        $scheduleCount = 0;
        
        foreach ($batches as $batch) {
            // Create 4-6 classes per week
            $classesPerWeek = rand(4, 6);
            $usedDays = [];
            
            for ($i = 0; $i < $classesPerWeek; $i++) {
                $availableDays = array_diff($days, $usedDays);
                if (empty($availableDays)) break;
                
                $day = $availableDays[array_rand($availableDays)];
                $usedDays[] = $day;
                
                $timeSlot = $timeSlots[array_rand($timeSlots)];
                $subject = $subjects[array_rand($subjects)];
                $teacherId = $teachers->isNotEmpty() ? $teachers->random()->id : null;
                $room = $rooms[array_rand($rooms)];
                
                ClassSchedule::create([
                    'batch_id' => $batch->id,
                    'teacher_id' => $teacherId,
                    'subject' => $subject,
                    'day_of_week' => $day,
                    'start_time' => $timeSlot['start'],
                    'end_time' => $timeSlot['end'],
                    'room' => $room,
                ]);
                
                $scheduleCount++;
            }
        }
        
        $this->command->info("✓ Created {$scheduleCount} class schedules");
    }

    /**
     * Seed attendance records for students
     */
    private function seedAttendanceRecords(): void
    {
        $this->command->info('📊 Seeding attendance records...');
        
        $students = Student::with('batch')->get();
        
        if ($students->isEmpty()) {
            $this->command->warn('No students found. Skipping attendance seeding.');
            return;
        }
        
        $attendanceCount = 0;
        $statuses = ['present', 'absent', 'late', 'excused'];
        $weights = [70, 10, 15, 5]; // Weighted probability
        
        foreach ($students as $student) {
            // Create attendance for last 60 days
            for ($i = 60; $i >= 0; $i--) {
                $date = now()->subDays($i);
                
                // Skip weekends (Friday)
                if ($date->dayOfWeek === 5) continue;
                
                // Random status with weighted probability
                $status = $this->weightedRandom($statuses, $weights);
                
                Attendance::create([
                    'student_id' => $student->id,
                    'batch_id' => $student->batch_id,
                    'date' => $date,
                    'status' => $status,
                ]);
                
                $attendanceCount++;
            }
        }
        
        $this->command->info("✓ Created {$attendanceCount} attendance records");
    }

    /**
     * Seed exams, questions, and results
     */
    private function seedExamsAndResults(): void
    {
        $this->command->info('📝 Seeding exams and results...');
        
        $batches = Batch::with('course')->get();
        
        if ($batches->isEmpty()) {
            $this->command->warn('No batches found. Skipping exam seeding.');
            return;
        }
        
        $examCount = 0;
        $resultCount = 0;
        
        foreach ($batches as $batch) {
            // Create 3-5 exams per batch
            $numExams = rand(3, 5);
            
            for ($i = 1; $i <= $numExams; $i++) {
                $type = rand(0, 1) ? 'mcq' : 'cq';
                $isPast = rand(1, 100) <= 70; // 70% past exams
                
                $exam = Exam::create([
                    'course_id' => $batch->course_id,
                    'batch_id' => $batch->id,
                    'title' => $batch->course->name . ' - ' . $this->getExamName($i),
                    'type' => $type,
                    'duration_minutes' => $type === 'mcq' ? rand(45, 90) : rand(90, 180),
                    'total_marks' => $type === 'mcq' ? 50 : 100,
                    'pass_marks' => $type === 'mcq' ? 20 : 40,
                    'start_time' => $isPast ? now()->subDays(rand(5, 60)) : now()->addDays(rand(1, 30)),
                    'end_time' => $isPast ? now()->subDays(rand(5, 60))->addHours(2) : now()->addDays(rand(1, 30))->addHours(2),
                    'status' => 'active',
                    'instructions' => 'Read all questions carefully. Answer to the best of your ability. Good luck!',
                ]);
                
                // Add questions
                $this->createQuestionsForExam($exam, $type);
                
                // Create results for past exams
                if ($isPast) {
                    $students = Student::where('batch_id', $batch->id)->get();
                    
                    foreach ($students as $student) {
                        // 85% students took the exam
                        if (rand(1, 100) <= 85) {
                            $obtainedMarks = $this->generateRealisticMarks($exam->total_marks, $exam->pass_marks);
                            
                            ExamAttempt::create([
                                'exam_id' => $exam->id,
                                'student_id' => $student->id,
                                'started_at' => $exam->start_time,
                                'submitted_at' => $exam->start_time->addMinutes($exam->duration_minutes - rand(5, 20)),
                                'answers' => json_encode([]),
                                'status' => 'submitted',
                            ]);
                            
                            ExamResult::create([
                                'exam_id' => $exam->id,
                                'student_id' => $student->id,
                                'subject_name' => $exam->title,
                                'marks' => $obtainedMarks,
                                'obtained_marks' => $obtainedMarks,
                                'total_marks' => $exam->total_marks,
                                'grade' => $this->calculateGrade($obtainedMarks, $exam->total_marks),
                                'feedback' => $this->generateFeedback($obtainedMarks, $exam->total_marks),
                            ]);
                            
                            $resultCount++;
                        }
                    }
                }
                
                $examCount++;
            }
        }
        
        $this->command->info("✓ Created {$examCount} exams and {$resultCount} results");
    }

    /**
     * Seed invoices and payments
     */
    private function seedInvoicesAndPayments(): void
    {
        $this->command->info('💰 Seeding invoices and payments...');
        
        $students = Student::all();
        
        if ($students->isEmpty()) {
            $this->command->warn('No students found. Skipping invoice seeding.');
            return;
        }
        
        $invoiceCount = 0;
        $paymentCount = 0;
        
        foreach ($students as $student) {
            // Create 2-4 invoices per student
            $numInvoices = rand(2, 4);
            
            for ($i = 1; $i <= $numInvoices; $i++) {
                $amount = rand(3000, 15000);
                $isPaid = rand(1, 100) <= 65; // 65% paid
                $isOverdue = !$isPaid && rand(1, 100) <= 30; // 30% of unpaid are overdue
                
                $issueDate = now()->subMonths($numInvoices - $i + 1);
                $dueDate = $isOverdue 
                    ? $issueDate->copy()->addDays(rand(15, 30))
                    : $issueDate->copy()->addDays(rand(30, 60));
                
                $invoice = Invoice::create([
                    'student_id' => $student->id,
                    'invoice_number' => 'INV-' . date('Y') . '-' . str_pad($invoiceCount + 1, 5, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'due_date' => $dueDate,
                    'status' => $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'pending'),
                    'items' => json_encode([
                        [
                            'description' => $this->getInvoiceDescription($i),
                            'amount' => $amount,
                        ]
                    ]),
                ]);
                
                $invoiceCount++;
                
                // Create payment if paid
                if ($isPaid) {
                    $paymentDate = $issueDate->copy()->addDays(rand(1, 25));
                    
                    Payment::create([
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_date' => $paymentDate,
                        'payment_method' => $this->getRandomPaymentMethod(),
                        'transaction_id' => 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                        'status' => 'completed',
                        'notes' => 'Payment received successfully',
                    ]);
                    
                    $paymentCount++;
                }
            }
        }
        
        $this->command->info("✓ Created {$invoiceCount} invoices and {$paymentCount} payments");
    }

    /**
     * Seed parent accounts and relationships
     */
    private function seedParentAccounts(): void
    {
        $this->command->info('👨‍👩‍👧‍👦 Seeding parent accounts...');
        
        $students = Student::with('user')->get();
        
        if ($students->isEmpty()) {
            $this->command->warn('No students found. Skipping parent seeding.');
            return;
        }
        
        $parentCount = 0;
        $relationshipCount = 0;
        $createdParents = [];
        
        // First, create the main parent@gmail.com account
        $mainParent = ParentModel::updateOrCreate(
            ['email' => 'parent@gmail.com'],
            [
                'name' => 'Demo Parent',
                'phone' => '01700000001',
                'password' => Hash::make('password'),
                'notification_preferences' => [
                    'email_notifications' => true,
                    'sms_notifications' => true,
                    'exam_alerts' => true,
                    'attendance_alerts' => true,
                    'payment_reminders' => true,
                ],
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        
        // Create User account for main parent
        $mainParentUser = User::updateOrCreate(
            ['email' => 'parent@gmail.com'],
            [
                'name' => 'Demo Parent',
                'password' => Hash::make('password'),
            ]
        );
        
        // Assign parent role
        $parentRole = \App\Models\Role::where('slug', 'parent')->first();
        if ($parentRole && !$mainParentUser->hasRole('parent')) {
            $mainParentUser->roles()->attach($parentRole->id);
        }
        
        $createdParents[] = $mainParent;
        $parentCount++;
        
        // Link the main parent to the first 2 students (as children)
        $firstStudents = $students->take(2);
        foreach ($firstStudents as $index => $student) {
            $relationshipType = $index === 0 ? 'father' : 'mother';
            
            // Check if relationship already exists
            $exists = \DB::table('parent_student')
                ->where('parent_id', $mainParent->id)
                ->where('student_id', $student->id)
                ->exists();
            
            if (!$exists) {
                $mainParent->students()->attach($student->id, [
                    'relationship_type' => $relationshipType,
                    'status' => 'approved',
                    'approved_by' => 1,
                    'approved_at' => now(),
                ]);
                
                $relationshipCount++;
            }
        }
        
        // Continue with other students
        foreach ($students->skip(2) as $index => $student) {
            // 80% chance student has a parent account
            if (rand(1, 100) <= 80) {
                // 25% chance to reuse existing parent (siblings)
                if (rand(1, 100) <= 25 && count($createdParents) > 1) {
                    $parent = $createdParents[array_rand($createdParents)];
                } else {
                    // Create new parent
                    $parentEmail = 'parent' . ($parentCount + 1) . '@example.com';
                    $parentName = $this->generateParentName();
                    
                    $parent = ParentModel::create([
                        'name' => $parentName,
                        'email' => $parentEmail,
                        'phone' => '017' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                        'password' => Hash::make('password'),
                        'notification_preferences' => [
                            'email_notifications' => true,
                            'sms_notifications' => true,
                            'exam_alerts' => true,
                            'attendance_alerts' => true,
                            'payment_reminders' => true,
                        ],
                        'email_verified_at' => now(),
                        'phone_verified_at' => now(),
                    ]);
                    
                    // Create User account for this parent
                    $parentUser = User::updateOrCreate(
                        ['email' => $parentEmail],
                        [
                            'name' => $parentName,
                            'password' => Hash::make('password'),
                        ]
                    );
                    
                    // Assign parent role
                    $parentRole = \App\Models\Role::where('slug', 'parent')->first();
                    if ($parentRole && !$parentUser->hasRole('parent')) {
                        $parentUser->roles()->attach($parentRole->id);
                    }
                    
                    $createdParents[] = $parent;
                    $parentCount++;
                }
                
                // Link parent to student
                $relationshipType = $this->getRandomRelationship();
                
                // Check if relationship already exists
                $exists = \DB::table('parent_student')
                    ->where('parent_id', $parent->id)
                    ->where('student_id', $student->id)
                    ->exists();
                
                if (!$exists) {
                    $parent->students()->attach($student->id, [
                        'relationship_type' => $relationshipType,
                        'status' => 'approved',
                        'approved_by' => 1,
                        'approved_at' => now(),
                    ]);
                    
                    $relationshipCount++;
                }
            }
        }
        
        $this->command->info("✓ Created {$parentCount} parent accounts with {$relationshipCount} relationships");
    }

    /**
     * Seed accounts data (Income and Expenses)
     */
    private function seedAccountsData(): void
    {
        $this->command->info('💰 Seeding accounts data...');
        
        $incomeCount = 0;
        $expenseCount = 0;
        
        // Income categories
        $incomeCategories = [
            'Tuition Fees',
            'Admission Fees',
            'Exam Fees',
            'Library Fees',
            'Lab Fees',
            'Sports Fees',
            'Transport Fees',
            'Donations',
            'Government Grant',
            'Other Income'
        ];
        
        // Expense categories
        $expenseCategories = [
            'Salary',
            'Utilities',
            'Maintenance',
            'Supplies',
            'Equipment',
            'Transportation',
            'Marketing',
            'Insurance',
            'Rent',
            'Other Expenses'
        ];
        
        // Create income records for the last 6 months
        for ($month = 6; $month >= 0; $month--) {
            $numIncomes = rand(5, 10);
            
            for ($i = 0; $i < $numIncomes; $i++) {
                $date = now()->subMonths($month)->addDays(rand(1, 28));
                $category = $incomeCategories[array_rand($incomeCategories)];
                $amount = rand(5000, 50000);
                
                Income::create([
                    'income_date' => $date,
                    'category' => collect(['admission', 'tuition', 'materials', 'other'])->random(),
                    'amount' => $amount,
                    'description' => $this->generateIncomeDescription($category),
                    'reference' => 'INC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'created_by' => 1, // Admin user
                ]);
                
                $incomeCount++;
            }
        }
        
        // Create expense records for the last 6 months
        for ($month = 6; $month >= 0; $month--) {
            $numExpenses = rand(8, 15);
            
            for ($i = 0; $i < $numExpenses; $i++) {
                $date = now()->subMonths($month)->addDays(rand(1, 28));
                $category = $expenseCategories[array_rand($expenseCategories)];
                $amount = rand(2000, 30000);
                
                Expense::create([
                    'expense_date' => $date,
                    'category' => collect(['rent', 'salary', 'bills', 'advertisement', 'furniture', 'paper', 'stationary', 'other'])->random(),
                    'amount' => $amount,
                    'description' => $this->generateExpenseDescription($category),
                    'receipt_number' => 'EXP-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'notes' => 'Paid to ' . $this->generateVendorName($category),
                    'created_by' => 1, // Admin user
                ]);
                
                $expenseCount++;
            }
        }
        
        $this->command->info("✓ Created {$incomeCount} income records and {$expenseCount} expense records");
    }

    /**
     * Seed inventory data
     */
    private function seedInventoryData(): void
    {
        $this->command->info('📦 Seeding inventory data...');
        
        $categories = [
            'Furniture',
            'Electronics',
            'Sports Equipment',
            'Lab Equipment',
            'Stationery',
            'Books',
            'Computers',
            'Cleaning Supplies',
            'Medical Supplies',
            'Office Equipment'
        ];
        
        $itemCount = 0;
        $transactionCount = 0;
        
        // Create inventory items
        foreach ($categories as $category) {
            $numItems = rand(3, 8);
            
            for ($i = 1; $i <= $numItems; $i++) {
                $quantity = rand(5, 100);
                $unitPrice = rand(100, 5000);
                
                $item = InventoryItem::create([
                    'name' => $this->generateInventoryItemName($category, $i),
                    'category' => $category,
                    'description' => 'High quality ' . strtolower($category) . ' for institutional use',
                    'quantity' => $quantity,
                    'unit' => collect(['pcs', 'box', 'set', 'unit', 'pack'])->random(),
                    'unit_price' => $unitPrice,
                    'low_stock_threshold' => rand(5, 20),
                    'location' => collect(['Store Room A', 'Store Room B', 'Office', 'Lab', 'Library'])->random(),
                ]);
                
                $itemCount++;
                
                // Create transactions for this item
                $numTransactions = rand(3, 8);
                
                for ($t = 0; $t < $numTransactions; $t++) {
                    $type = collect(['in', 'out'])->random();
                    $qty = rand(1, 10);
                    $date = now()->subDays(rand(1, 180));
                    
                    InventoryTransaction::create([
                        'inventory_item_id' => $item->id,
                        'type' => collect(['purchase', 'usage', 'adjustment'])->random(),
                        'quantity' => $qty,
                        'unit_price' => $item->unit_price,
                        'total_amount' => $qty * $item->unit_price,
                        'supplier' => $type === 'purchase' ? $this->generateSupplierName() : null,
                        'purpose' => $type === 'usage' ? collect(['classroom', 'lab', 'office', 'maintenance'])->random() : null,
                        'transaction_date' => $date,
                        'notes' => $type === 'purchase' ? 'Stock received from supplier' : 'Issued for use',
                        'created_by' => 1, // Admin user
                    ]);
                    
                    $transactionCount++;
                }
            }
        }
        
        $this->command->info("✓ Created {$itemCount} inventory items and {$transactionCount} transactions");
    }

    /**
     * Seed communication data (SMS logs and templates)
     */
    private function seedCommunicationData(): void
    {
        $this->command->info('📱 Seeding communication data...');
        
        $templateCount = 0;
        $smsLogCount = 0;
        
        // Create SMS templates
        $templates = [
            [
                'name' => 'Attendance Alert',
                'slug' => 'attendance-alert',
                'type' => 'sms',
                'content' => 'Dear Parent, Your child {student_name} was absent on {date}. Please contact the school if this is unexpected.',
                'placeholders' => ['student_name', 'date'],
                'is_active' => true,
            ],
            [
                'name' => 'Fee Reminder',
                'slug' => 'fee-reminder',
                'type' => 'sms',
                'content' => 'Dear Parent, This is a reminder that {student_name}\'s fee of {amount} is due on {due_date}. Please pay at your earliest convenience.',
                'placeholders' => ['student_name', 'amount', 'due_date'],
                'is_active' => true,
            ],
            [
                'name' => 'Exam Notification',
                'slug' => 'exam-notification',
                'type' => 'sms',
                'content' => 'Dear Student, Your {exam_name} is scheduled for {exam_date} at {exam_time}. Please be on time. Good luck!',
                'placeholders' => ['exam_name', 'exam_date', 'exam_time'],
                'is_active' => true,
            ],
            [
                'name' => 'Result Published',
                'slug' => 'result-published',
                'type' => 'sms',
                'content' => 'Dear {student_name}, Your {exam_name} result is now available. You scored {marks}/{total_marks}. Check your portal for details.',
                'placeholders' => ['student_name', 'exam_name', 'marks', 'total_marks'],
                'is_active' => true,
            ],
            [
                'name' => 'Holiday Notice',
                'slug' => 'holiday-notice',
                'type' => 'sms',
                'content' => 'Dear All, The institution will remain closed on {date} due to {reason}. Classes will resume on {resume_date}.',
                'placeholders' => ['date', 'reason', 'resume_date'],
                'is_active' => true,
            ],
            [
                'name' => 'Meeting Invitation',
                'slug' => 'meeting-invitation',
                'type' => 'sms',
                'content' => 'Dear Parent, You are invited to attend a parent-teacher meeting on {date} at {time}. Your presence is highly appreciated.',
                'placeholders' => ['date', 'time'],
                'is_active' => true,
            ],
        ];
        
        foreach ($templates as $template) {
            MessageTemplate::create($template);
            $templateCount++;
        }
        
        // Create SMS logs
        $students = Student::with('user')->take(20)->get();
        
        if ($students->isNotEmpty()) {
            foreach ($students as $student) {
                $numSms = rand(3, 8);
                
                for ($i = 0; $i < $numSms; $i++) {
                    $status = collect(['sent', 'sent', 'sent', 'failed', 'pending'])->random();
                    $sentAt = now()->subDays(rand(1, 90));
                    
                    SmsLog::create([
                        'phone' => $student->phone ?? '01700000000',
                        'message' => $this->generateSmsMessage($student),
                        'status' => $status,
                        'type' => collect(['attendance', 'payment', 'exam', 'announcement'])->random(),
                        'sent_at' => $status === 'sent' ? $sentAt : null,
                    ]);
                    
                    $smsLogCount++;
                }
            }
        }
        
        $this->command->info("✓ Created {$templateCount} SMS templates and {$smsLogCount} SMS logs");
    }

    /**
     * Seed activity logs
     */
    private function seedActivityLogs(): void
    {
        $this->command->info('📋 Seeding activity logs...');
        
        $users = User::take(10)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping activity logs.');
            return;
        }
        
        $actions = [
            'created' => ['student', 'teacher', 'course', 'batch', 'exam', 'payment', 'invoice'],
            'updated' => ['student', 'teacher', 'course', 'batch', 'exam', 'settings'],
            'deleted' => ['student', 'teacher', 'course', 'announcement'],
            'viewed' => ['report', 'dashboard', 'student_list', 'payment_list'],
            'exported' => ['students', 'payments', 'attendance', 'results'],
            'imported' => ['students', 'teachers'],
            'sent' => ['sms', 'email', 'notification'],
        ];
        
        $logCount = 0;
        
        foreach ($users as $user) {
            $numLogs = rand(10, 30);
            
            for ($i = 0; $i < $numLogs; $i++) {
                $action = array_rand($actions);
                $subject = $actions[$action][array_rand($actions[$action])];
                $createdAt = now()->subDays(rand(1, 90))->subHours(rand(0, 23));
                
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'model_type' => 'App\\Models\\' . ucfirst($subject),
                    'model_id' => rand(1, 100),
                    'changes' => [
                        'description' => $this->generateActivityDescription($action, $subject, $user->name),
                    ],
                    'ip_address' => $this->generateIpAddress(),
                    'user_agent' => $this->generateUserAgent(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                
                $logCount++;
            }
        }
        
        $this->command->info("✓ Created {$logCount} activity logs");
    }

    // Helper methods
    
    private function weightedRandom(array $values, array $weights): string
    {
        $total = array_sum($weights);
        $random = rand(1, $total);
        
        $sum = 0;
        foreach ($values as $i => $value) {
            $sum += $weights[$i];
            if ($random <= $sum) {
                return $value;
            }
        }
        
        return $values[0];
    }

    private function getExamName(int $number): string
    {
        $names = ['First Term', 'Mid Term', 'Final Term', 'Monthly Test', 'Unit Test'];
        return $names[($number - 1) % count($names)];
    }

    private function createQuestionsForExam(Exam $exam, string $type): void
    {
        $numQuestions = $type === 'mcq' ? 10 : 5;
        $marksPerQuestion = $exam->total_marks / $numQuestions;
        
        for ($q = 1; $q <= $numQuestions; $q++) {
            $questionData = [
                'exam_id' => $exam->id,
                'question_text' => "Question {$q}: " . $this->generateQuestionText($type),
                'type' => $type,
                'marks' => $marksPerQuestion,
                'order' => $q,
            ];
            
            if ($type === 'mcq') {
                $questionData['options'] = [
                    'A) ' . $this->generateOption(),
                    'B) ' . $this->generateOption(),
                    'C) ' . $this->generateOption(),
                    'D) ' . $this->generateOption(),
                ];
                $questionData['correct_answer'] = $questionData['options'][0];
            }
            
            Question::create($questionData);
        }
    }

    private function generateQuestionText(string $type): string
    {
        $questions = [
            'What is the primary concept discussed in this topic?',
            'Explain the fundamental principles of this subject.',
            'How does this concept apply in real-world scenarios?',
            'What are the key differences between these approaches?',
            'Describe the process and methodology involved.',
        ];
        
        return $questions[array_rand($questions)];
    }

    private function generateOption(): string
    {
        $options = [
            'The correct answer based on theory',
            'An alternative explanation',
            'A common misconception',
            'The traditional approach',
        ];
        
        return $options[array_rand($options)];
    }

    private function generateRealisticMarks(int $total, int $pass): int
    {
        // Generate marks with realistic distribution
        $rand = rand(1, 100);
        
        if ($rand <= 10) {
            // 10% fail
            return rand(0, $pass - 1);
        } elseif ($rand <= 40) {
            // 30% average (pass to 70%)
            return rand($pass, (int)($total * 0.7));
        } elseif ($rand <= 80) {
            // 40% good (70% to 85%)
            return rand((int)($total * 0.7), (int)($total * 0.85));
        } else {
            // 20% excellent (85% to 100%)
            return rand((int)($total * 0.85), $total);
        }
    }

    private function calculateGrade(int $obtained, int $total): string
    {
        $percentage = ($obtained / $total) * 100;
        
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'A-';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 40) return 'D';
        return 'F';
    }

    private function generateFeedback(int $obtained, int $total): string
    {
        $percentage = ($obtained / $total) * 100;
        
        if ($percentage >= 90) return 'Outstanding performance! Keep up the excellent work.';
        if ($percentage >= 80) return 'Excellent work! You have a strong grasp of the material.';
        if ($percentage >= 70) return 'Good job! Continue to build on this foundation.';
        if ($percentage >= 60) return 'Satisfactory performance. Focus on improving weak areas.';
        if ($percentage >= 50) return 'Average performance. More practice is recommended.';
        if ($percentage >= 40) return 'Needs improvement. Please review the material carefully.';
        return 'Requires significant improvement. Consider additional tutoring.';
    }

    private function getInvoiceDescription(int $number): string
    {
        $descriptions = [
            'Monthly Tuition Fee',
            'Admission Fee',
            'Exam Fee',
            'Course Materials Fee',
            'Library and Lab Fee',
            'Sports and Activities Fee',
        ];
        
        return $descriptions[($number - 1) % count($descriptions)];
    }

    private function getRandomPaymentMethod(): string
    {
        $methods = ['cash', 'bank_transfer', 'card', 'mobile_banking', 'bkash', 'nagad'];
        return $methods[array_rand($methods)];
    }

    private function getRandomRelationship(): string
    {
        $relationships = ['father', 'mother', 'guardian'];
        $weights = [40, 40, 20];
        return $this->weightedRandom($relationships, $weights);
    }

    private function generateParentName(): string
    {
        $firstNames = [
            'Mohammad', 'Abdul', 'Ahmed', 'Karim', 'Rahim', 'Jamal',
            'Fatima', 'Ayesha', 'Nasreen', 'Sultana', 'Begum', 'Khatun'
        ];
        $lastNames = [
            'Rahman', 'Ahmed', 'Khan', 'Islam', 'Hossain', 'Ali',
            'Begum', 'Akter', 'Khatun', 'Sultana'
        ];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function generateMaterialTitle(string $type, int $number): string
    {
        $titles = [
            'pdf' => [
                'Lecture Notes - Chapter ' . $number,
                'Study Guide - Unit ' . $number,
                'Practice Problems Set ' . $number,
                'Reference Material ' . $number,
            ],
            'document' => [
                'Assignment ' . $number . ' Instructions',
                'Project Guidelines ' . $number,
                'Reading Material ' . $number,
                'Supplementary Notes ' . $number,
            ],
            'link' => [
                'External Resource ' . $number,
                'Recommended Reading ' . $number,
                'Online Tutorial ' . $number,
                'Reference Link ' . $number,
            ],
            'video' => [
                'Video Lecture ' . $number,
                'Tutorial Video ' . $number,
                'Demonstration ' . $number,
                'Recorded Session ' . $number,
            ],
        ];
        
        return $titles[$type][array_rand($titles[$type])];
    }

    private function generateMaterialDescription(string $type): string
    {
        $descriptions = [
            'pdf' => 'Comprehensive study material covering key concepts and examples.',
            'document' => 'Detailed document with instructions and guidelines.',
            'link' => 'External resource for additional learning and reference.',
            'video' => 'Video content explaining important topics with visual demonstrations.',
        ];
        
        return $descriptions[$type];
    }

    private function generateIncomeDescription(string $category): string
    {
        $descriptions = [
            'Tuition Fees' => 'Monthly tuition fee collection from students',
            'Admission Fees' => 'New student admission and registration fees',
            'Exam Fees' => 'Examination and assessment fees',
            'Library Fees' => 'Library membership and book rental fees',
            'Lab Fees' => 'Laboratory usage and equipment fees',
            'Sports Fees' => 'Sports activities and equipment fees',
            'Transport Fees' => 'Student transportation service fees',
            'Donations' => 'Charitable donations and contributions',
            'Government Grant' => 'Government funding and grants',
            'Other Income' => 'Miscellaneous income',
        ];
        
        return $descriptions[$category] ?? 'Income received';
    }

    private function generateExpenseDescription(string $category): string
    {
        $descriptions = [
            'Salary' => 'Staff salary and wages payment',
            'Utilities' => 'Electricity, water, and internet bills',
            'Maintenance' => 'Building and equipment maintenance',
            'Supplies' => 'Office and classroom supplies purchase',
            'Equipment' => 'New equipment and furniture purchase',
            'Transportation' => 'Vehicle fuel and maintenance',
            'Marketing' => 'Advertising and promotional expenses',
            'Insurance' => 'Insurance premium payment',
            'Rent' => 'Building rent payment',
            'Other Expenses' => 'Miscellaneous expenses',
        ];
        
        return $descriptions[$category] ?? 'Expense paid';
    }

    private function generateVendorName(string $category): string
    {
        $vendors = [
            'Salary' => collect(['Staff', 'Teachers', 'Admin Staff'])->random(),
            'Utilities' => collect(['DESCO', 'WASA', 'ISP Provider'])->random(),
            'Maintenance' => collect(['ABC Maintenance', 'XYZ Services', 'Local Contractor'])->random(),
            'Supplies' => collect(['Office Mart', 'Stationery Plus', 'Supply House'])->random(),
            'Equipment' => collect(['Tech Solutions', 'Furniture World', 'Equipment Store'])->random(),
            'Transportation' => collect(['Fuel Station', 'Transport Service', 'Vehicle Maintenance'])->random(),
            'Marketing' => collect(['Ad Agency', 'Print House', 'Digital Marketing'])->random(),
            'Insurance' => collect(['Insurance Company A', 'Insurance Company B'])->random(),
            'Rent' => 'Property Owner',
            'Other Expenses' => 'Various Vendors',
        ];
        
        return $vendors[$category] ?? 'Vendor';
    }

    private function generateInventoryItemName(string $category, int $number): string
    {
        $items = [
            'Furniture' => ['Desk', 'Chair', 'Table', 'Cabinet', 'Shelf', 'Bench'],
            'Electronics' => ['Projector', 'Speaker', 'Microphone', 'Monitor', 'Printer', 'Scanner'],
            'Sports Equipment' => ['Football', 'Cricket Bat', 'Volleyball', 'Badminton Racket', 'Basketball'],
            'Lab Equipment' => ['Microscope', 'Beaker', 'Test Tube', 'Bunsen Burner', 'Flask'],
            'Stationery' => ['Pen', 'Pencil', 'Notebook', 'Marker', 'Eraser', 'Ruler'],
            'Books' => ['Textbook', 'Reference Book', 'Workbook', 'Dictionary', 'Atlas'],
            'Computers' => ['Desktop PC', 'Laptop', 'Keyboard', 'Mouse', 'Webcam'],
            'Cleaning Supplies' => ['Broom', 'Mop', 'Detergent', 'Dustbin', 'Cleaning Cloth'],
            'Medical Supplies' => ['First Aid Kit', 'Bandage', 'Antiseptic', 'Thermometer', 'Mask'],
            'Office Equipment' => ['Stapler', 'Punch', 'Calculator', 'Whiteboard', 'Notice Board'],
        ];
        
        $itemList = $items[$category] ?? ['Item'];
        return $itemList[array_rand($itemList)] . ' ' . $number;
    }

    private function generateSupplierName(): string
    {
        $suppliers = [
            'ABC Suppliers Ltd',
            'XYZ Trading Company',
            'Quality Goods Store',
            'Premium Suppliers',
            'Best Buy Wholesale',
            'Top Quality Traders',
            'Reliable Suppliers',
            'Express Trading',
        ];
        
        return $suppliers[array_rand($suppliers)];
    }

    private function generateSmsMessage(Student $student): string
    {
        $messages = [
            "Dear Parent, {$student->user->name} attended class today. Thank you.",
            "Reminder: Fee payment due for {$student->user->name}. Please pay at your earliest convenience.",
            "Dear {$student->user->name}, Your exam is scheduled for tomorrow. Good luck!",
            "Parent-teacher meeting scheduled for next week. Your presence is appreciated.",
            "Dear Parent, {$student->user->name} has shown excellent progress this month.",
        ];
        
        return $messages[array_rand($messages)];
    }

    private function generateActivityDescription(string $action, string $subject, string $userName): string
    {
        $descriptions = [
            'created' => "{$userName} created a new {$subject}",
            'updated' => "{$userName} updated {$subject} information",
            'deleted' => "{$userName} deleted a {$subject}",
            'viewed' => "{$userName} viewed {$subject}",
            'exported' => "{$userName} exported {$subject} data",
            'imported' => "{$userName} imported {$subject} data",
            'sent' => "{$userName} sent {$subject}",
        ];
        
        return $descriptions[$action] ?? "{$userName} performed {$action} on {$subject}";
    }

    private function generateIpAddress(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }

    private function generateUserAgent(): string
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
        
        return $agents[array_rand($agents)];
    }
}
