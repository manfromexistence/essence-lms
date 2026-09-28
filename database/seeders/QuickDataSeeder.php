<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamResult;
use App\Models\Batch;
use App\Models\Student;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;

class QuickDataSeeder extends Seeder
{
    /**
     * Run the database seeds - FAST VERSION
     */
    public function run(): void
    {
        echo "Starting quick seeding...\n";
        
        // 1. Quick Exams - Just 5 exams total
        $batches = Batch::limit(5)->get();
        if($batches->isNotEmpty()) {
            foreach($batches as $batch) {
                $exam = Exam::create([
                    'course_id' => $batch->course_id,
                    'batch_id' => $batch->id,
                    'title' => $batch->course->name . ' - Final Exam',
                    'type' => 'mcq',
                    'duration_minutes' => 60,
                    'total_marks' => 100,
                    'pass_marks' => 40,
                    'start_time' => now()->subDays(5),
                    'end_time' => now()->subDays(5)->addHours(2),
                    'status' => 'active',
                    'instructions' => 'Answer all questions carefully.',
                ]);
                
                // Add 5 questions
                for($i = 1; $i <= 5; $i++) {
                    Question::create([
                        'exam_id' => $exam->id,
                        'question_text' => "Question $i for this exam?",
                        'type' => 'mcq',
                        'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                        'correct_answer' => 'Option A',
                        'marks' => 20,
                        'order' => $i,
                    ]);
                }
                
                // Add results for 5 students
                $students = Student::where('batch_id', $batch->id)->limit(5)->get();
                foreach($students as $student) {
                    ExamResult::create([
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'subject_name' => $exam->title,
                        'marks' => rand(40, 100),
                        'obtained_marks' => rand(40, 100),
                        'total_marks' => 100,
                        'grade' => 'A',
                        'feedback' => 'Good work!',
                    ]);
                }
            }
            echo "Created 5 exams with questions and results\n";
        }
        
        // 2. Quick Income - Just 10 entries
        for($i = 1; $i <= 10; $i++) {
            Income::create([
                'amount' => rand(5000, 15000),
                'income_date' => now()->subDays(rand(1, 30)),
                'category' => 'tuition',
                'description' => 'Tuition Fee Payment',
                'created_by' => 1,
            ]);
        }
        echo "Created 10 income entries\n";
        
        // 3. Quick Expenses - Just 10 entries
        for($i = 1; $i <= 10; $i++) {
            Expense::create([
                'amount' => rand(2000, 10000),
                'expense_date' => now()->subDays(rand(1, 30)),
                'category' => 'salary',
                'description' => 'Staff Salary',
                'created_by' => 1,
            ]);
        }
        echo "Created 10 expense entries\n";
        
        // 4. Quick Invoices - Just 10 invoices
        $students = Student::limit(10)->get();
        $invoiceCount = 0;
        foreach($students as $student) {
            $amount = rand(5000, 15000);
            $isPaid = rand(0, 1);
            
            // Check if invoice already exists
            $existingInvoice = Invoice::where('student_id', $student->id)->first();
            if($existingInvoice) {
                continue;
            }
            
            $invoice = Invoice::create([
                'student_id' => $student->id,
                'invoice_number' => 'INV-2026-' . str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT),
                'amount' => $amount,
                'due_date' => now()->addDays(30),
                'status' => $isPaid ? 'paid' : 'pending',
                'items' => [['description' => 'Tuition Fee', 'amount' => $amount]],
            ]);
            
            $invoiceCount++;
            
            if($isPaid) {
                Payment::create([
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_date' => now()->subDays(rand(1, 10)),
                    'payment_method' => 'cash',
                    'transaction_id' => 'TXN-' . rand(100000, 999999),
                    'status' => 'completed',
                    'notes' => 'Payment received',
                ]);
            }
        }
        echo "Created $invoiceCount invoices and payments\n";
        
        echo "\n✅ Quick seeding completed!\n";
        echo "Created:\n";
        echo "- 5 exams with 5 questions each\n";
        echo "- 25 exam results\n";
        echo "- 10 income entries\n";
        echo "- 10 expense entries\n";
        echo "- 10 invoices\n";
        echo "- ~5 payments\n";
    }
}
