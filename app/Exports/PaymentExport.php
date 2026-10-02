<?php

namespace App\Exports;

use App\Exports\Concerns\NeutralisesExcelFormulas;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * PaymentExport class for exporting payment data to Excel.
 *
 * Implements Laravel Excel interfaces for generating formatted Excel files
 * with payment records. Supports filtering by date range, batch, payment method, and student.
 *
 * Requirements: 6.3, 16.2, 16.3, 16.5
 */
class PaymentExport implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use NeutralisesExcelFormulas;

    /**
     * Filters to apply when retrieving payment data.
     *
     * Supported filters:
     * - batch_id: Filter by specific batch
     * - student_id: Filter by specific student
     * - start_date: Filter records from this date
     * - end_date: Filter records until this date
     * - payment_method: Filter by payment method (Cash, bKash, Nagad, Bank Transfer)
     * - status: Filter by payment status (completed, pending, failed, refunded)
     */
    protected array $filters;

    /**
     * Create a new PaymentExport instance.
     *
     * @param  array  $filters  Filters to apply to the payment query
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return the collection of payment data for export.
     *
     * Retrieves payment records with related student, batch, and invoice data,
     * applying the same filters used in the report view.
     *
     * Requirements: 6.3, 16.3
     */
    public function collection(): Collection
    {
        $query = Payment::with(['student.user', 'student.batch', 'invoice']);

        // Apply batch filter through student relationship
        if (! empty($this->filters['batch_id'])) {
            $query->whereHas('student', function ($q) {
                $q->where('batch_id', $this->filters['batch_id']);
            });
        }

        // Apply student filter
        if (! empty($this->filters['student_id'])) {
            $query->where('student_id', $this->filters['student_id']);
        }

        // Apply date range filters
        if (! empty($this->filters['start_date'])) {
            $query->where('payment_date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->where('payment_date', '<=', $this->filters['end_date']);
        }

        // Apply payment method filter
        if (! empty($this->filters['payment_method'])) {
            $query->where('payment_method', $this->filters['payment_method']);
        }

        // Apply status filter
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Define the column headers for the Excel file.
     *
     * Provides proper column headers for payment data including:
     * Invoice Number, Student Name, Student ID, Batch, Amount, Payment Method,
     * Payment Date, Transaction Reference, Status, and Notes.
     *
     * Requirements: 16.2
     */
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Student Name',
            'Student ID',
            'Batch',
            'Amount',
            'Payment Method',
            'Payment Date',
            'Transaction Reference',
            'Receipt Number',
            'Status',
            'Notes',
        ];
    }

    /**
     * Map each payment record to Excel columns.
     *
     * Transforms the payment model data into an array format
     * suitable for Excel export, handling null values gracefully.
     *
     * Requirements: 6.3, 16.2
     *
     * @param  mixed  $payment  The payment record to map
     */
    public function map($payment): array
    {
        // Get invoice number
        $invoiceNumber = $payment->invoice
            ? $payment->invoice->invoice_number
            : 'N/A';

        // Get student name from user relationship or fallback
        $studentName = 'Unknown';
        if ($payment->student && $payment->student->user) {
            $studentName = $payment->student->user->name;
        } elseif ($payment->student && $payment->student->name_bn) {
            $studentName = $payment->student->name_bn;
        }

        // Get student registration number
        $studentId = $payment->student
            ? $payment->student->registration_no
            : 'N/A';

        // Get batch name
        $batchName = $payment->student && $payment->student->batch
            ? $payment->student->batch->name
            : 'N/A';

        // Format amount
        $amount = number_format($payment->amount, 2);

        // Format payment method with proper capitalization
        $paymentMethod = ucfirst($payment->payment_method ?? 'Unknown');

        // Format payment date
        $paymentDate = $payment->payment_date
            ? $payment->payment_date->format('Y-m-d')
            : 'N/A';

        // Transaction reference
        $transactionRef = $payment->transaction_id ?? '-';

        // Receipt number
        $receiptNumber = $payment->receipt_number ?? '-';

        // Format status with proper capitalization
        $status = ucfirst($payment->status ?? 'Unknown');

        // Notes
        $notes = $payment->notes ?? '-';

        return [
            $invoiceNumber,
            $studentName,
            $studentId,
            $batchName,
            $amount,
            $paymentMethod,
            $paymentDate,
            $transactionRef,
            $receiptNumber,
            $status,
            $notes,
        ];
    }

    /**
     * Apply styles to the Excel worksheet.
     *
     * Formats the header row with bold text and background color
     * for better readability.
     *
     * Requirements: 16.2
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the header row (row 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E7D32'], // Green color for payment reports
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Get the filters applied to this export.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
