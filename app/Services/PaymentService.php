<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class PaymentService
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    /**
     * Record a payment for a student.
     * Enhanced to support both Student object and array data with student_id.
     * 
     * @throws Exception If payment recording fails
     */
    public function recordPayment($studentOrData, ?array $additionalData = null): Payment
    {
        // Support both signatures: recordPayment(Student, array) and recordPayment(array)
        if ($studentOrData instanceof Student) {
            $student = $studentOrData;
            $data = $additionalData ?? [];
        } else {
            $data = $studentOrData;
            $student = Student::findOrFail($data['student_id']);
        }

        try {
            return DB::transaction(function () use ($student, $data) {
                // Generate receipt number if not provided
                $receiptNumber = $data['receipt_number'] ?? $this->generateReceiptNumber();

                $payment = Payment::create([
                    'student_id' => $student->id,
                    'invoice_id' => $data['invoice_id'] ?? null,
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'receipt_number' => $receiptNumber,
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'payment_date' => $data['payment_date'] ?? now(),
                    'notes' => $data['notes'] ?? null,
                    'status' => $data['status'] ?? 'completed',
                ]);

                // Update student balance
                if ($payment->isCompleted()) {
                    $this->updateStudentBalance($student);
                }

                return $payment;
            });
        } catch (Exception $e) {
            Log::error('Failed to record payment', [
                'student_id' => $student->id,
                'amount' => $data['amount'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to record payment: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique receipt number.
     * 
     * @return string The generated receipt number
     * @throws Exception If receipt number generation fails
     */
    public function generateReceiptNumber(): string
    {
        try {
            $prefix = $this->settingsService->get('receipt_prefix', 'RCP');
            $year = date('Y');
            $month = date('m');

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            $lastPayment = Payment::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;
            if ($lastPayment && $lastPayment->receipt_number) {
                $lastSequence = (int) substr($lastPayment->receipt_number, -4);
                $sequence = $lastSequence + 1;
            }

            return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        } catch (QueryException $e) {
            Log::error('Database error generating receipt number', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to generate receipt number. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to generate receipt number', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to generate receipt number. Please try again.');
        }
    }

    /**
     * Generate an invoice for a student.
     * 
     * @param Student $student The student to generate invoice for
     * @param array $data Invoice data
     * @return Invoice The created invoice
     * @throws Exception If invoice generation fails
     */
    public function generateInvoice(Student $student, array $data = []): Invoice
    {
        try {
            $invoiceNumber = $data['invoice_number'] ?? $this->generateInvoiceNumber();

            return Invoice::create([
                'student_id' => $student->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $data['amount'] ?? $student->due_amount,
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'status' => 'pending',
                'items' => $data['items'] ?? [
                    ['description' => 'Course Fee', 'amount' => $student->due_amount],
                ],
            ]);
        } catch (QueryException $e) {
            Log::error('Database error generating invoice', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to generate invoice. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to generate invoice', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to generate invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique invoice number.
     * 
     * @return string The generated invoice number
     * @throws Exception If invoice number generation fails
     */
    public function generateInvoiceNumber(): string
    {
        try {
            $prefix = $this->settingsService->get('invoice_prefix', 'INV');
            $year = date('Y');
            $month = date('m');

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            $lastInvoice = Invoice::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;
            if ($lastInvoice && $lastInvoice->invoice_number) {
                $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
                $sequence = $lastSequence + 1;
            }

            return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        } catch (QueryException $e) {
            Log::error('Database error generating invoice number', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to generate invoice number. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to generate invoice number', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to generate invoice number. Please try again.');
        }
    }

    /**
     * Update student balance after payment.
     * 
     * @param Student $student The student to update
     * @return Student The updated student
     * @throws Exception If balance update fails
     */
    public function updateStudentBalance(Student $student): Student
    {
        try {
            $totalPaid = $student->payments()
                ->whereIn('status', Payment::settledStatuses())
                ->sum('amount');

            $student->update([
                'paid_amount' => $totalPaid,
                'due_amount' => max(0, $student->total_amount - $totalPaid),
            ]);

            return $student->fresh();
        } catch (QueryException $e) {
            Log::error('Database error updating student balance', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update student balance. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to update student balance', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update student balance: ' . $e->getMessage());
        }
    }

    /**
     * Calculate current balance for a student.
     * Returns the due amount (total - paid).
     * 
     * @param Student $student The student to calculate balance for
     * @return float The calculated balance
     */
    public function calculateBalance(Student $student): float
    {
        try {
            $totalPaid = $student->payments()
                ->whereIn('status', Payment::settledStatuses())
                ->sum('amount');

            return max(0, $student->total_amount - $totalPaid);
        } catch (QueryException $e) {
            Log::error('Database error calculating student balance', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            // Return the stored due_amount as fallback
            return (float) ($student->due_amount ?? 0);
        } catch (Exception $e) {
            Log::error('Failed to calculate student balance', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            return (float) ($student->due_amount ?? 0);
        }
    }

    /**
     * Send payment notification via SMS.
     * Integrates with SmsService to send payment confirmation.
     * 
     * @param Payment $payment The payment to notify about
     * @return bool Whether the notification was sent successfully
     */
    public function sendPaymentNotification(Payment $payment): bool
    {
        try {
            $payment->load(['student.user']);
            $student = $payment->student;
            
            if (!$student) {
                Log::warning('Cannot send payment notification: student not found', [
                    'payment_id' => $payment->id,
                ]);
                return false;
            }

            // Get SmsService instance
            $smsService = app(SmsService::class);
            
            // Prepare payment details for SMS
            $paymentDetails = [
                'amount' => $payment->amount,
                'receipt_number' => $payment->receipt_number,
                'balance' => $this->calculateBalance($student),
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : now()->format('Y-m-d'),
            ];

            // Send payment confirmation SMS
            $smsLog = $smsService->sendPaymentConfirmation($student, $paymentDetails);
            
            if ($smsLog && ($smsLog->isSent() || $smsLog->isDelivered())) {
                Log::info('Payment notification sent successfully', [
                    'payment_id' => $payment->id,
                    'student_id' => $student->id,
                    'sms_log_id' => $smsLog->id,
                ]);
                return true;
            }
            
            Log::warning('Payment notification may have failed', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'sms_status' => $smsLog ? $smsLog->status : 'no_log',
            ]);
            return false;
        } catch (Exception $e) {
            // Log error but don't throw - SMS failure shouldn't block payment
            Log::error('Failed to send payment notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get payment history for a student with optional filtering.
     * Supports filtering by date range, payment method, and status.
     * 
     * @param Student $student The student to get history for
     * @param array $filters Filter criteria
     * @return Collection Payment history collection
     */
    public function getPaymentHistory(Student $student, array $filters = []): Collection
    {
        try {
            $query = $student->payments()
                ->orderBy('payment_date', 'desc');

            // Apply filters
            if (!empty($filters['start_date'])) {
                $query->where('payment_date', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('payment_date', '<=', $filters['end_date']);
            }

            if (!empty($filters['payment_method'])) {
                $query->where('payment_method', $filters['payment_method']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            return $query->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving payment history', [
                'student_id' => $student->id,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment history', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Get dashboard statistics with optional filtering.
     * Returns metrics like total due, total collected, payment counts, etc.
     * 
     * @param array $filters Filter criteria
     * @return array Dashboard statistics
     */
    public function getDashboardStats(array $filters = []): array
    {
        try {
            // Build payment query with filters
            $paymentQuery = Payment::query();
            
            if (!empty($filters['start_date'])) {
                $paymentQuery->where('payment_date', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $paymentQuery->where('payment_date', '<=', $filters['end_date']);
            }

            if (!empty($filters['batch_id'])) {
                $paymentQuery->whereHas('student', function ($q) use ($filters) {
                    $q->where('batch_id', $filters['batch_id']);
                });
            }

            if (!empty($filters['payment_method'])) {
                $paymentQuery->where('payment_method', $filters['payment_method']);
            }

            $payments = $paymentQuery->get();

            // Build student query for due amounts
            $studentQuery = Student::query();
            
            if (!empty($filters['batch_id'])) {
                $studentQuery->where('batch_id', $filters['batch_id']);
            }

            // Calculate statistics
            $totalDue = $studentQuery->sum('due_amount');
            $totalCollected = $payments->whereIn('status', Payment::settledStatuses())->sum('amount');
            $totalPending = $payments->where('status', 'pending')->sum('amount');
            
            // Payment method breakdown
            $byMethod = $payments->whereIn('status', Payment::settledStatuses())
                ->groupBy('payment_method')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ]);

            // Count statistics
            $completedCount = $payments->whereIn('status', Payment::settledStatuses())->count();
            $pendingCount = $payments->where('status', 'pending')->count();
            $studentsWithDues = $studentQuery->where('due_amount', '>', 0)->count();

            return [
                'total_due' => $totalDue,
                'total_collected' => $totalCollected,
                'total_pending' => $totalPending,
                'completed_count' => $completedCount,
                'pending_count' => $pendingCount,
                'students_with_dues' => $studentsWithDues,
                'by_method' => $byMethod,
                'average_payment' => $completedCount > 0 ? $totalCollected / $completedCount : 0,
            ];
        } catch (QueryException $e) {
            Log::error('Database error retrieving dashboard stats', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return [
                'total_due' => 0,
                'total_collected' => 0,
                'total_pending' => 0,
                'completed_count' => 0,
                'pending_count' => 0,
                'students_with_dues' => 0,
                'by_method' => collect(),
                'average_payment' => 0,
                'error' => 'Unable to load dashboard statistics. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve dashboard stats', [
                'error' => $e->getMessage(),
            ]);
            return [
                'total_due' => 0,
                'total_collected' => 0,
                'total_pending' => 0,
                'completed_count' => 0,
                'pending_count' => 0,
                'students_with_dues' => 0,
                'by_method' => collect(),
                'average_payment' => 0,
                'error' => 'Unable to load dashboard statistics. Please try again.',
            ];
        }
    }

    /**
     * Get payment summary for a date range.
     * 
     * @param Carbon|null $startDate Start date filter
     * @param Carbon|null $endDate End date filter
     * @return array Payment summary
     */
    public function getPaymentSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        try {
            $query = Payment::query();

            if ($startDate) {
                $query->where('payment_date', '>=', $startDate->toDateString());
            }

            if ($endDate) {
                $query->where('payment_date', '<=', $endDate->toDateString());
            }

            $payments = $query->get();

            return [
                'total_amount' => $payments->whereIn('status', Payment::settledStatuses())->sum('amount'),
                'total_count' => $payments->whereIn('status', Payment::settledStatuses())->count(),
                'pending_amount' => $payments->where('status', 'pending')->sum('amount'),
                'pending_count' => $payments->where('status', 'pending')->count(),
                'by_method' => $payments->whereIn('status', Payment::settledStatuses())
                    ->groupBy('payment_method')
                    ->map(fn($group) => [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount'),
                    ]),
            ];
        } catch (QueryException $e) {
            Log::error('Database error retrieving payment summary', [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate?->toDateString(),
                'error' => $e->getMessage(),
            ]);
            return [
                'total_amount' => 0,
                'total_count' => 0,
                'pending_amount' => 0,
                'pending_count' => 0,
                'by_method' => collect(),
                'error' => 'Unable to load payment summary.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment summary', [
                'error' => $e->getMessage(),
            ]);
            return [
                'total_amount' => 0,
                'total_count' => 0,
                'pending_amount' => 0,
                'pending_count' => 0,
                'by_method' => collect(),
                'error' => 'Unable to load payment summary.',
            ];
        }
    }

    /**
     * Get paginated payments.
     * 
     * @param array $filters Filter criteria
     * @param int $perPage Items per page
     * @return LengthAwarePaginator Paginated payments
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            $query = Payment::with(['student.user']);

            if (!empty($filters['student_id'])) {
                $query->where('student_id', $filters['student_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['payment_method'])) {
                $query->where('payment_method', $filters['payment_method']);
            }

            if (!empty($filters['start_date'])) {
                $query->where('payment_date', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('payment_date', '<=', $filters['end_date']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhereHas('student', function ($sq) use ($search) {
                          $sq->where('registration_no', 'like', "%{$search}%");
                      });
                });
            }

            return $query->orderBy('payment_date', 'desc')->paginate($perPage);
        } catch (QueryException $e) {
            Log::error('Database error retrieving paginated payments', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            // Return empty paginator on error
            return new LengthAwarePaginator([], 0, $perPage);
        } catch (Exception $e) {
            Log::error('Failed to retrieve paginated payments', [
                'error' => $e->getMessage(),
            ]);
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Get students with outstanding dues.
     * 
     * @return Collection Students with dues
     */
    public function getStudentsWithDues(): Collection
    {
        try {
            return Student::where('due_amount', '>', 0)
                ->with(['batch', 'user'])
                ->orderBy('due_amount', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving students with dues', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve students with dues', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Get overdue invoices.
     * 
     * @return Collection Overdue invoices
     */
    public function getOverdueInvoices(): Collection
    {
        try {
            return Invoice::overdue()
                ->with(['student.user'])
                ->orderBy('due_date')
                ->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving overdue invoices', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve overdue invoices', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Mark invoice as paid.
     * 
     * @param Invoice $invoice The invoice to mark as paid
     * @return Invoice The updated invoice
     * @throws Exception If update fails
     */
    public function markInvoicePaid(Invoice $invoice): Invoice
    {
        try {
            $invoice->update(['status' => 'paid']);
            return $invoice->fresh();
        } catch (QueryException $e) {
            Log::error('Database error marking invoice as paid', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update invoice status. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to mark invoice as paid', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update invoice status: ' . $e->getMessage());
        }
    }

    /**
     * Get payment statistics.
     * 
     * @return array Payment statistics
     */
    public function getStatistics(): array
    {
        try {
            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            return [
                'today' => Payment::completed()->where('payment_date', $today)->sum('amount'),
                'this_month' => Payment::completed()->where('payment_date', '>=', $thisMonth)->sum('amount'),
                'last_month' => Payment::completed()
                    ->whereBetween('payment_date', [$lastMonth, $thisMonth->subDay()])
                    ->sum('amount'),
                'total_dues' => Student::sum('due_amount'),
                'total_collected' => Payment::completed()->sum('amount'),
            ];
        } catch (QueryException $e) {
            Log::error('Database error retrieving payment statistics', [
                'error' => $e->getMessage(),
            ]);
            return [
                'today' => 0,
                'this_month' => 0,
                'last_month' => 0,
                'total_dues' => 0,
                'total_collected' => 0,
                'error' => 'Unable to load payment statistics.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment statistics', [
                'error' => $e->getMessage(),
            ]);
            return [
                'today' => 0,
                'this_month' => 0,
                'last_month' => 0,
                'total_dues' => 0,
                'total_collected' => 0,
                'error' => 'Unable to load payment statistics.',
            ];
        }
    }

    /**
     * Refund a payment.
     * 
     * @param Payment $payment The payment to refund
     * @param string|null $reason Refund reason
     * @return Payment The updated payment
     * @throws Exception If refund fails
     */
    public function refundPayment(Payment $payment, ?string $reason = null): Payment
    {
        try {
            return DB::transaction(function () use ($payment, $reason) {
                $payment->update([
                    'status' => 'refunded',
                    'notes' => $payment->notes . "\nRefunded: " . ($reason ?? 'No reason provided'),
                ]);

                // Update student balance
                $this->updateStudentBalance($payment->student);

                return $payment->fresh();
            });
        } catch (QueryException $e) {
            Log::error('Database error processing refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to process refund. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to process refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Get daily payment report.
     * 
     * @param Carbon $date The date to get report for
     * @return array Daily report data
     */
    public function getDailyReport(Carbon $date): array
    {
        try {
            $payments = Payment::where('payment_date', $date->toDateString())
                ->with(['student.user'])
                ->get();

            return [
                'date' => $date->toDateString(),
                'total_amount' => $payments->whereIn('status', Payment::settledStatuses())->sum('amount'),
                'total_count' => $payments->whereIn('status', Payment::settledStatuses())->count(),
                'payments' => $payments,
            ];
        } catch (QueryException $e) {
            Log::error('Database error retrieving daily report', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);
            return [
                'date' => $date->toDateString(),
                'total_amount' => 0,
                'total_count' => 0,
                'payments' => collect(),
                'error' => 'Unable to load daily report.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve daily report', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);
            return [
                'date' => $date->toDateString(),
                'total_amount' => 0,
                'total_count' => 0,
                'payments' => collect(),
                'error' => 'Unable to load daily report.',
            ];
        }
    }
}
