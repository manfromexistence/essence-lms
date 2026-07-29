<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class InvoiceService
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    /**
     * Generate a unique sequential invoice number with database locking.
     * 
     * This method uses database locking to ensure invoice numbers are
     * generated sequentially without gaps or duplicates, even under
     * concurrent requests.
     *
     * @return string
     * @throws Exception If invoice number generation fails
     */
    public function generateInvoiceNumber(): string
    {
        try {
            return DB::transaction(function () {
                $prefix = $this->settingsService->get('invoice_prefix', 'INV');
                $year = date('Y');
                $month = date('m');

                // Lock the invoices table for reading to prevent race conditions
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
                $lastInvoice = Invoice::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $sequence = 1;
                if ($lastInvoice && $lastInvoice->invoice_number) {
                    // Extract the sequence number from the last invoice
                    $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
                    $sequence = $lastSequence + 1;
                }

                return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            });
        } catch (Exception $e) {
            Log::error('Failed to generate invoice number', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to generate invoice number: ' . $e->getMessage());
        }
    }

    /**
     * Create a new invoice for a student.
     *
     * @param Student $student
     * @param float $amount
     * @param array $details Additional invoice details (due_date, description, items, etc.)
     * @return Invoice
     * @throws Exception If invoice creation fails
     */
    public function createInvoice(Student $student, float $amount, array $details = []): Invoice
    {
        try {
            return DB::transaction(function () use ($student, $amount, $details) {
                $invoiceNumber = $this->generateInvoiceNumber();

                $invoiceData = [
                    'student_id' => $student->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $amount,
                    'due_date' => $details['due_date'] ?? now()->addDays(30),
                    'status' => $details['status'] ?? 'pending',
                    'items' => $details['items'] ?? null,
                ];

                return Invoice::create($invoiceData);
            });
        } catch (Exception $e) {
            Log::error('Failed to create invoice', [
                'student_id' => $student->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve an invoice by its invoice number.
     *
     * @param string $invoiceNumber
     * @return Invoice|null
     */
    public function getInvoice(string $invoiceNumber): ?Invoice
    {
        try {
            return Invoice::where('invoice_number', $invoiceNumber)
                ->with(['student.user'])
                ->first();
        } catch (QueryException $e) {
            Log::error('Database error retrieving invoice by number', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('Failed to retrieve invoice by number', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retrieve an invoice by its ID.
     *
     * @param int $id
     * @return Invoice|null
     */
    public function getInvoiceById(int $id): ?Invoice
    {
        try {
            return Invoice::with(['student.user'])
                ->find($id);
        } catch (QueryException $e) {
            Log::error('Database error retrieving invoice by ID', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('Failed to retrieve invoice by ID', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update an existing invoice.
     *
     * @param Invoice $invoice
     * @param array $data
     * @return Invoice
     * @throws Exception If update fails
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        try {
            $invoice->update($data);
            return $invoice->fresh();
        } catch (QueryException $e) {
            Log::error('Database error updating invoice', [
                'invoice_id' => $invoice->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update invoice. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to update invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Mark an invoice as paid when a payment is recorded.
     *
     * @param Invoice $invoice
     * @param Payment $payment
     * @return void
     * @throws Exception If update fails
     */
    public function markInvoiceAsPaid(Invoice $invoice, Payment $payment): void
    {
        try {
            DB::transaction(function () use ($invoice, $payment) {
                $totalPaid = Payment::where('student_id', $invoice->student_id)
                    ->whereIn('status', \App\Models\Payment::settledStatuses())
                    ->sum('amount');

                if ($totalPaid >= $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'partial']);
                }
            });
        } catch (QueryException $e) {
            Log::error('Database error marking invoice as paid', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update invoice payment status. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to mark invoice as paid', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to update invoice payment status: ' . $e->getMessage());
        }
    }

    /**
     * Get all invoices for a student.
     *
     * @param Student $student
     * @param array $filters Optional filters (status, date_range, etc.)
     * @return Collection
     */
    public function getStudentInvoices(Student $student, array $filters = []): Collection
    {
        try {
            $query = Invoice::where('student_id', $student->id);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['start_date'])) {
                $query->where('due_date', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('due_date', '<=', $filters['end_date']);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving student invoices', [
                'student_id' => $student->id,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve student invoices', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Get all pending invoices.
     *
     * @return Collection
     */
    public function getPendingInvoices(): Collection
    {
        try {
            return Invoice::where('status', 'pending')
                ->with(['student.user'])
                ->orderBy('due_date')
                ->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving pending invoices', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve pending invoices', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Get all overdue invoices.
     *
     * @return Collection
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
     * Cancel an invoice.
     *
     * @param Invoice $invoice
     * @param string|null $reason
     * @return Invoice
     * @throws Exception If cancellation fails
     */
    public function cancelInvoice(Invoice $invoice, ?string $reason = null): Invoice
    {
        try {
            $invoice->update([
                'status' => 'cancelled',
            ]);

            return $invoice->fresh();
        } catch (QueryException $e) {
            Log::error('Database error cancelling invoice', [
                'invoice_id' => $invoice->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to cancel invoice. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to cancel invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to cancel invoice: ' . $e->getMessage());
        }
    }

    /**
     * Get invoice statistics.
     *
     * @return array
     */
    public function getInvoiceStatistics(): array
    {
        try {
            return [
                'total_pending' => Invoice::where('status', 'pending')->count(),
                'total_paid' => Invoice::where('status', 'paid')->count(),
                'total_overdue' => Invoice::overdue()->count(),
                'total_amount_pending' => Invoice::where('status', 'pending')->sum('amount'),
                'total_amount_paid' => Invoice::where('status', 'paid')->sum('amount'),
                'total_amount_overdue' => Invoice::overdue()->sum('amount'),
            ];
        } catch (QueryException $e) {
            Log::error('Database error retrieving invoice statistics', [
                'error' => $e->getMessage(),
            ]);
            return [
                'total_pending' => 0,
                'total_paid' => 0,
                'total_overdue' => 0,
                'total_amount_pending' => 0,
                'total_amount_paid' => 0,
                'total_amount_overdue' => 0,
                'error' => 'Unable to load invoice statistics.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve invoice statistics', [
                'error' => $e->getMessage(),
            ]);
            return [
                'total_pending' => 0,
                'total_paid' => 0,
                'total_overdue' => 0,
                'total_amount_pending' => 0,
                'total_amount_paid' => 0,
                'total_amount_overdue' => 0,
                'error' => 'Unable to load invoice statistics.',
            ];
        }
    }

    /**
     * Get invoices with filtering and pagination support.
     *
     * @param array $filters
     * @return Collection
     */
    public function getInvoices(array $filters = []): Collection
    {
        try {
            $query = Invoice::with(['student.user']);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['student_id'])) {
                $query->where('student_id', $filters['student_id']);
            }

            if (!empty($filters['start_date'])) {
                $query->where('due_date', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('due_date', '<=', $filters['end_date']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('student', function ($sq) use ($search) {
                          $sq->where('registration_no', 'like', "%{$search}%");
                      });
                });
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving invoices', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve invoices', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Update overdue invoice statuses.
     * This method should be called periodically (e.g., via scheduled task).
     *
     * @return int Number of invoices updated
     */
    public function updateOverdueStatuses(): int
    {
        try {
            return Invoice::where('status', 'pending')
                ->where('due_date', '<', now())
                ->update(['status' => 'overdue']);
        } catch (QueryException $e) {
            Log::error('Database error updating overdue statuses', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        } catch (Exception $e) {
            Log::error('Failed to update overdue statuses', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get the remaining amount for an invoice.
     *
     * @param Invoice $invoice
     * @return float
     */
    public function getRemainingAmount(Invoice $invoice): float
    {
        try {
            $totalPaid = Payment::where('student_id', $invoice->student_id)
                ->whereIn('status', \App\Models\Payment::settledStatuses())
                ->sum('amount');

            return max(0, $invoice->amount - $totalPaid);
        } catch (QueryException $e) {
            Log::error('Database error calculating remaining amount', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            // Return the full invoice amount as fallback
            return (float) $invoice->amount;
        } catch (Exception $e) {
            Log::error('Failed to calculate remaining amount', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return (float) $invoice->amount;
        }
    }
}
