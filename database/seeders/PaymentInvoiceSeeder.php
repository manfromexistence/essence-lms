<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;

class PaymentInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        
        $students = Student::all();
        
        if($students->isEmpty()) {
            echo "No students found. Skipping PaymentInvoiceSeeder.\n";
            return;
        }
        
        $invoiceCount = 0;
        $paymentCount = 0;
        
        // Create invoices and payments for each student
        foreach($students as $student) {
            // Create 2-4 invoices per student
            $numInvoices = rand(2, 4);
            
            for($i = 1; $i <= $numInvoices; $i++) {
                $amount = rand(3000, 15000);
                $isPaid = $faker->boolean(70); // 70% chance invoice is paid
                $isOverdue = !$isPaid && $faker->boolean(40); // 40% of unpaid are overdue
                
                $dueDate = $isOverdue 
                    ? $faker->dateTimeBetween('-2 months', '-1 day')
                    : $faker->dateTimeBetween('now', '+1 month');
                
                $invoice = Invoice::create([
                    'student_id' => $student->id,
                    'invoice_number' => 'INV-' . date('Y') . '-' . str_pad($invoiceCount + 1, 5, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'due_date' => $dueDate,
                    'status' => $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'pending'),
                    // Invoice::items is cast to 'array', so pass a plain array here.
                    // Wrapping it in json_encode() double-encoded the value and made
                    // $invoice->items resolve to a string, breaking the invoice page.
                    'items' => [
                        [
                            'description' => $faker->randomElement([
                                'Monthly Tuition Fee',
                                'Admission Fee',
                                'Exam Fee',
                                'Course Materials Fee',
                                'Library Fee',
                                'Sports Fee',
                            ]),
                            'amount' => $amount,
                        ]
                    ],
                ]);
                
                $invoiceCount++;
                
                // If invoice is paid, create payment record
                if($isPaid) {
                    $paymentDate = $faker->dateTimeBetween($invoice->issued_at, 'now');
                    
                    Payment::create([
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_date' => $paymentDate,
                        'payment_method' => $faker->randomElement(['cash', 'bank_transfer', 'card', 'mobile_banking']),
                        'transaction_id' => 'TXN-' . strtoupper($faker->bothify('??###??###')),
                        'status' => 'completed',
                        'notes' => 'Payment received',
                    ]);
                    
                    $paymentCount++;
                }
            }
        }
        
        echo "Created $invoiceCount invoices and $paymentCount payments!\n";
    }
}
