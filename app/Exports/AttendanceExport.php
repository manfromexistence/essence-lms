<?php

namespace App\Exports;

use App\Exports\Concerns\NeutralisesExcelFormulas;
use App\Models\Attendance;
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
 * AttendanceExport class for exporting attendance data to Excel.
 *
 * Implements Laravel Excel interfaces for generating formatted Excel files
 * with attendance records. Supports filtering by batch, date range, and student.
 *
 * Requirements: 5.3, 16.2, 16.3, 16.5
 */
class AttendanceExport implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use NeutralisesExcelFormulas;

    /**
     * Filters to apply when retrieving attendance data.
     *
     * Supported filters:
     * - batch_id: Filter by specific batch
     * - student_id: Filter by specific student
     * - start_date: Filter records from this date
     * - end_date: Filter records until this date
     * - status: Filter by attendance status (present, absent, late, excused)
     */
    protected array $filters;

    /**
     * Create a new AttendanceExport instance.
     *
     * @param  array  $filters  Filters to apply to the attendance query
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return the collection of attendance data for export.
     *
     * Retrieves attendance records with related student and batch data,
     * applying the same filters used in the report view.
     *
     * Requirements: 5.3, 16.3
     */
    public function collection(): Collection
    {
        $query = Attendance::with(['student.user', 'student.batch', 'batch']);

        // Apply batch filter
        if (! empty($this->filters['batch_id'])) {
            $query->where('batch_id', $this->filters['batch_id']);
        }

        // Apply student filter
        if (! empty($this->filters['student_id'])) {
            $query->where('student_id', $this->filters['student_id']);
        }

        // Apply date range filters
        if (! empty($this->filters['start_date'])) {
            $query->where('date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->where('date', '<=', $this->filters['end_date']);
        }

        // Apply status filter
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('student_id')
            ->get();
    }

    /**
     * Define the column headers for the Excel file.
     *
     * Provides proper column headers for attendance data including:
     * Student Name, Student ID, Batch, Date, Status, Check-in Time,
     * Check-out Time, and Notes.
     *
     * Requirements: 16.2
     */
    public function headings(): array
    {
        return [
            'Student Name',
            'Student ID',
            'Batch',
            'Date',
            'Day',
            'Status',
            'Check-in Time',
            'Check-out Time',
            'Notes',
        ];
    }

    /**
     * Map each attendance record to Excel columns.
     *
     * Transforms the attendance model data into an array format
     * suitable for Excel export, handling null values gracefully.
     *
     * Requirements: 5.3, 16.2
     *
     * @param  mixed  $attendance  The attendance record to map
     */
    public function map($attendance): array
    {
        // Get student name from user relationship or fallback
        $studentName = 'Unknown';
        if ($attendance->student && $attendance->student->user) {
            $studentName = $attendance->student->user->name;
        } elseif ($attendance->student && $attendance->student->name_bn) {
            $studentName = $attendance->student->name_bn;
        }

        // Get student registration number
        $studentId = $attendance->student
            ? $attendance->student->registration_no
            : 'N/A';

        // Get batch name
        $batchName = $attendance->batch
            ? $attendance->batch->name
            : ($attendance->student && $attendance->student->batch
                ? $attendance->student->batch->name
                : 'N/A');

        // Format date and get day name
        $date = $attendance->date
            ? $attendance->date->format('Y-m-d')
            : 'N/A';

        $dayName = $attendance->date
            ? $attendance->date->format('l')
            : 'N/A';

        // Format status with proper capitalization
        $status = ucfirst($attendance->status ?? 'Unknown');

        // Check-in and Check-out times (placeholders for future implementation)
        // These fields can be added to the attendance model when needed
        $checkInTime = $attendance->check_in_time ?? '-';
        $checkOutTime = $attendance->check_out_time ?? '-';

        // Notes field (placeholder for future implementation)
        $notes = $attendance->notes ?? '-';

        return [
            $studentName,
            $studentId,
            $batchName,
            $date,
            $dayName,
            $status,
            $checkInTime,
            $checkOutTime,
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
                    'startColor' => ['rgb' => '4472C4'],
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
