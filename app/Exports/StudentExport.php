<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * StudentExport class for exporting comprehensive student data to Excel.
 * 
 * Implements Laravel Excel interfaces for generating formatted Excel files
 * with comprehensive student information including enrollment, payments, and attendance.
 * Supports filtering by batch, course, enrollment status, search (name), and date range.
 * 
 * Requirements: 8.3, 16.2, 16.3, 16.5
 */
class StudentExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * Filters to apply when retrieving student data.
     * 
     * Supported filters:
     * - batch_id: Filter by specific batch
     * - course_id: Filter by specific course (through batch relationship)
     * - enrollment_status: Filter by enrollment status (active, inactive, graduated, etc.)
     * - search: Search by student name
     * - start_date: Filter students enrolled from this date
     * - end_date: Filter students enrolled until this date
     *
     * @var array
     */
    protected array $filters;

    /**
     * Create a new StudentExport instance.
     *
     * @param array $filters Filters to apply to the student query
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return the collection of student data for export.
     * 
     * Retrieves student records with related user, batch, course, payments, and attendance data,
     * applying the same filters used in the report view.
     * 
     * Requirements: 8.3, 16.3
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = Student::with(['user', 'batch.course', 'payments', 'attendances']);

        // Apply batch filter
        if (!empty($this->filters['batch_id'])) {
            $query->where('batch_id', $this->filters['batch_id']);
        }

        // Apply course filter through batch relationship
        if (!empty($this->filters['course_id'])) {
            $query->whereHas('batch', function ($q) {
                $q->where('course_id', $this->filters['course_id']);
            });
        }

        // Apply enrollment status filter
        // Status is determined by batch status or student-specific status if available
        if (!empty($this->filters['enrollment_status'])) {
            $status = $this->filters['enrollment_status'];
            
            if ($status === 'active') {
                $query->whereHas('batch', function ($q) {
                    $q->where('status', 'active');
                });
            } elseif ($status === 'inactive') {
                $query->whereHas('batch', function ($q) {
                    $q->where('status', 'inactive');
                });
            } elseif ($status === 'completed' || $status === 'graduated') {
                $query->whereHas('batch', function ($q) {
                    $q->whereIn('status', \App\Models\Payment::settledStatuses());
                });
            }
        }

        // Apply search filter (by student name)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name_bn', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply date range filters (enrollment date based on created_at)
        if (!empty($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }

        return $query->orderBy('batch_id')
            ->orderBy('registration_no')
            ->get();
    }

    /**
     * Define the column headers for the Excel file.
     * 
     * Provides proper column headers for comprehensive student data including:
     * Student Name, Student ID, Email, Phone, Batch, Course, Enrollment Date,
     * Status, Total Fees, Total Paid, Balance, and Attendance Rate.
     * 
     * Requirements: 16.2
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Student Name',
            'Student ID',
            'Email',
            'Phone',
            'Batch',
            'Course',
            'Enrollment Date',
            'Status',
            'Total Fees',
            'Total Paid',
            'Balance',
            'Attendance Rate',
        ];
    }

    /**
     * Map each student record to Excel columns.
     * 
     * Transforms the student model data into an array format
     * suitable for Excel export, handling null values gracefully.
     * Includes comprehensive student information with calculated fields.
     * 
     * Requirements: 8.3, 16.2
     *
     * @param mixed $student The student record to map
     * @return array
     */
    public function map($student): array
    {
        // Get student name from user relationship or fallback to name_bn
        $studentName = 'Unknown';
        if ($student->user) {
            $studentName = $student->user->name;
        } elseif ($student->name_bn) {
            $studentName = $student->name_bn;
        }

        // Get student registration number
        $studentId = $student->registration_no ?? 'N/A';

        // Get email from user relationship
        $email = $student->user ? $student->user->email : 'N/A';

        // Get phone number (from student or user)
        $phone = $student->phone ?? ($student->user ? $student->user->phone : 'N/A');

        // Get batch name
        $batchName = $student->batch ? $student->batch->name : 'N/A';

        // Get course name through batch relationship
        $courseName = 'N/A';
        if ($student->batch && $student->batch->course) {
            $courseName = $student->batch->course->name;
        } elseif ($student->course_name) {
            $courseName = $student->course_name;
        }

        // Get enrollment date (created_at)
        $enrollmentDate = $student->created_at 
            ? $student->created_at->format('Y-m-d') 
            : 'N/A';

        // Determine enrollment status based on batch status
        $status = 'Unknown';
        if ($student->batch) {
            $batchStatus = $student->batch->status ?? 'active';
            $status = match ($batchStatus) {
                'active' => 'Active',
                'inactive' => 'Inactive',
                'completed' => 'Graduated',
                default => ucfirst($batchStatus),
            };
        }

        // Get total fees
        $totalFees = number_format($student->total_amount ?? 0, 2);

        // Calculate total paid from payments or use stored value
        $totalPaid = 0;
        if ($student->payments && $student->payments->count() > 0) {
            $totalPaid = $student->payments
                ->whereIn('status', \App\Models\Payment::settledStatuses())
                ->sum('amount');
        } else {
            $totalPaid = $student->paid_amount ?? 0;
        }
        $totalPaidFormatted = number_format($totalPaid, 2);

        // Calculate balance (due amount)
        $balance = ($student->total_amount ?? 0) - $totalPaid;
        if ($student->due_amount !== null) {
            $balance = $student->due_amount;
        }
        $balanceFormatted = number_format($balance, 2);

        // Calculate attendance rate
        $attendanceRate = '0%';
        if ($student->attendances && $student->attendances->count() > 0) {
            $totalAttendance = $student->attendances->count();
            $presentCount = $student->attendances
                ->whereIn('status', ['present', 'late'])
                ->count();
            $rate = round(($presentCount / $totalAttendance) * 100, 1);
            $attendanceRate = $rate . '%';
        } elseif (method_exists($student, 'getAttendancePercentageAttribute')) {
            $attendanceRate = $student->attendance_percentage . '%';
        }

        return [
            $studentName,
            $studentId,
            $email,
            $phone,
            $batchName,
            $courseName,
            $enrollmentDate,
            $status,
            $totalFees,
            $totalPaidFormatted,
            $balanceFormatted,
            $attendanceRate,
        ];
    }

    /**
     * Apply styles to the Excel worksheet.
     * 
     * Formats the header row with bold text and background color
     * for better readability.
     * 
     * Requirements: 16.2
     *
     * @param Worksheet $sheet
     * @return array
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
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E65100'], // Orange color for student reports
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Get the filters applied to this export.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
