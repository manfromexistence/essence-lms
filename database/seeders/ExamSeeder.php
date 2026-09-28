<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\ExamResult;
use App\Models\ExamAttempt;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        
        // Get all batches and courses
        $batches = Batch::with('course')->get();
        
        if($batches->isEmpty()) {
            echo "No batches found. Skipping ExamSeeder.\n";
            return;
        }

        $examTypes = ['mcq', 'cq'];
        $examCount = 0;

        // Create 2-3 exams per batch
        foreach($batches as $batch) {
            $numExams = rand(2, 3);
            
            for($i = 1; $i <= $numExams; $i++) {
                $type = $faker->randomElement($examTypes);
                $isPast = $faker->boolean(60); // 60% chance it's a past exam
                
                $exam = Exam::create([
                    'course_id' => $batch->course_id,
                    'batch_id' => $batch->id,
                    'title' => $batch->course->name . ' - Exam ' . $i,
                    'type' => $type,
                    'duration_minutes' => $type === 'mcq' ? rand(30, 60) : rand(60, 120),
                    'total_marks' => $type === 'mcq' ? 50 : 100,
                    'pass_marks' => $type === 'mcq' ? 20 : 40,
                    'start_time' => $isPast ? now()->subDays(rand(1, 30)) : now()->addDays(rand(1, 30)),
                    'end_time' => $isPast ? now()->subDays(rand(1, 30))->addHours(2) : now()->addDays(rand(1, 30))->addHours(2),
                    'status' => 'active',
                    'instructions' => 'Please read all questions carefully before answering. Good luck!',
                ]);

                // Add questions based on exam type
                if($type === 'mcq') {
                    // Add 10 MCQ questions
                    for($q = 1; $q <= 10; $q++) {
                        Question::create([
                            'exam_id' => $exam->id,
                            'question_text' => "Question $q: " . $faker->sentence() . "?",
                            'type' => 'mcq',
                            'options' => [
                                'A) ' . $faker->word(),
                                'B) ' . $faker->word(),
                                'C) ' . $faker->word(),
                                'D) ' . $faker->word(),
                            ],
                            'correct_answer' => 'A) ' . $faker->word(),
                            'marks' => 5,
                            'order' => $q,
                        ]);
                    }
                } else {
                    // Add 5 CQ questions
                    for($q = 1; $q <= 5; $q++) {
                        Question::create([
                            'exam_id' => $exam->id,
                            'question_text' => "Question $q: " . $faker->paragraph(),
                            'type' => 'cq',
                            'marks' => 20,
                            'order' => $q,
                        ]);
                    }
                }

                // If it's a past exam, create results for students
                if($isPast) {
                    $students = Student::where('batch_id', $batch->id)->get();
                    
                    foreach($students as $student) {
                        // 80% chance student took the exam
                        if($faker->boolean(80)) {
                            $obtainedMarks = rand($exam->pass_marks - 10, $exam->total_marks);
                            $obtainedMarks = max(0, min($obtainedMarks, $exam->total_marks));
                            
                            // Create exam attempt
                            $attempt = ExamAttempt::create([
                                'exam_id' => $exam->id,
                                'student_id' => $student->id,
                                'started_at' => $exam->start_time,
                                'submitted_at' => $exam->start_time->addMinutes($exam->duration_minutes - rand(5, 15)),
                                'answers' => [],
                                'status' => 'submitted',
                            ]);
                            
                            // Create exam result
                            ExamResult::create([
                                'exam_id' => $exam->id,
                                'student_id' => $student->id,
                                'subject_name' => $exam->title,
                                'marks' => $obtainedMarks,
                                'obtained_marks' => $obtainedMarks,
                                'total_marks' => $exam->total_marks,
                                'grade' => $this->calculateGrade($obtainedMarks, $exam->total_marks),
                                'feedback' => $obtainedMarks >= $exam->pass_marks ? 'Good performance!' : 'Needs improvement',
                            ]);
                        }
                    }
                }

                $examCount++;
            }
        }

        echo "Created $examCount exams with questions and results!\n";
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade($obtained, $total): string
    {
        $percentage = ($obtained / $total) * 100;
        
        if($percentage >= 80) return 'A+';
        if($percentage >= 70) return 'A';
        if($percentage >= 60) return 'A-';
        if($percentage >= 50) return 'B';
        if($percentage >= 40) return 'C';
        if($percentage >= 33) return 'D';
        return 'F';
    }
}
