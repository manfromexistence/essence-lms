<?php

namespace App\Exports;

use App\Exports\Concerns\NeutralisesExcelFormulas;
use App\Models\ExamResult;
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
 * PerformanceExport class for exporting performance/exam data to Excel.
 *
 * Implements Laravel Excel interfaces for generating formatted Excel files
 * with student scores and rankings. Supports filtering by batch, course, exam,
 * student, and date range.
 *
 * Requirements: 7.3, 16.2, 16.3, 16.5
 */
class PerformanceExport implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use NeutralisesExcelFormulas;

    /**
     * Filters to apply when retrieving performance data.
     *
     * Supported filters:
     * - batch_id: Filter by specific batch
     * - course_id: Filter by specific course
     * - exam_id: Filter by specific exam
     * - student_id: Filter by specific student
     * - start_date: Filter records from this date
     * - end_date: Filter records until this date
     */
    protected array $filters;

    /**
     * Create a new PerformanceExport instance.
     *
     * @param  array  $filters  Filters to apply to the performance query
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return the collection of performance data for export.
     *
     * Retrieves exam result records with related student, batch, course, and exam data,
     * applying the same filters used in the report view.
     *
     * Requirements: 7.3, 16.3
     */
    public function collection(): Collection
    {
        $query = ExamResult::with(['student.user', 'student.batch', 'exam.course', 'exam.batch']);

        // Apply batch filter through exam relationship
        if (! empty($this->filters['batch_id'])) {
            $query->whereHas('exam', function ($q) {
                $q->where('batch_id', $this->filters['batch_id']);
            });
        }

        // Apply course filter through exam relationship
        if (! empty($this->filters['course_id'])) {
            $query->whereHas('exam', function ($q) {
                $q->where('course_id', $this->filters['course_id']);
            });
        }

        // Apply exam filter
        if (! empty($this->filters['exam_id'])) {
            $query->where('exam_id', $this->filters['exam_id']);
        }

        // Apply student filter
        if (! empty($this->filters['student_id'])) {
            $query->where('student_id', $this->filters['student_id']);
        }

        // Apply date range filters based on exam start_time
        if (! empty($this->filters['start_date'])) {
            $query->whereHas('exam', function ($q) {
                $q->where('start_time', '>=', $this->filters['start_date']);
            });
        }

        if (! empty($this->filters['end_date'])) {
            $query->whereHas('exam', function ($q) {
                $q->where('start_time', '<=', $this->filters['end_date']);
            });
        }

        return $query->orderBy('exam_id')
            ->orderBy('rank')
            ->orderByDesc('obtained_marks')
            ->get();
    }

    /**
     * Define the column headers for the Excel file.
     *
     * Provides proper column headers for performance data including:
     * Student Name, Student ID, Batch, Course, Exam Name, Score, Total Marks,
     * Percentage, Grade, Rank, and Exam Date.
     *
     * Requirements: 16.2
     */
    public function headings(): array
    {
        return [
            'Student Name',
            'Student ID',
            'Batch',
            'Course',
            'Exam Name',
            'Score',
            'Total Marks',
            'Percentage',
            'Grade',
            'Rank',
            'Exam Date',
            'Status',
        ];
    }

    /**
     * Map each exam result record to Excel columns.
     *
     * Transforms the exam result model data into an array format
     * suitable for Excel export, handling null values gracefully.
     *
     * Requirements: 7.3, 16.2
     *
     * @param  mixed  $result  The exam result record to map
     */
    public function map($result): array
    {
        // Get student name from user relationship or fallback
        $studentName = 'Unknown';
        if ($result->student && $result->student->user) {
            $studentName = $result->student->user->name;
        } elseif ($result->student && $result->student->name_bn) {
            $studentName = $result->student->name_bn;
        }

        // Get student registration number
        $studentId = $result->student
            ? $result->student->registration_no
            : 'N/A';

        // Get batch name from exam or student
        $batchName = 'N/A';
        if ($result->exam && $result->exam->batch) {
            $batchName = $result->exam->batch->name;
        } elseif ($result->student && $result->student->batch) {
            $batchName = $result->student->batch->name;
        }

        // Get course name from exam relationship
        $courseName = $result->exam && $result->exam->course
            ? $result->exam->course->name
            : ($result->subject_name ?? 'N/A');

        // Get exam name/title
        $examName = $result->exam
            ? $result->exam->title
            : 'N/A';

        // Get score (obtained marks)
        $score = $result->obtained_marks ?? $result->marks ?? 0;

        // Get total marks from result or exam
        $totalMarks = $result->total_marks ?? ($result->exam ? $result->exam->total_marks : 0);

        // Calculate percentage
        $percentage = $totalMarks > 0
            ? round(($score / $totalMarks) * 100, 2).'%'
            : '0%';

        // Get grade - use stored grade or calculate
        $grade = $result->grade ?? $result->calculateGrade();

        // Get rank
        $rank = $result->rank ?? '-';

        // Get exam date
        $examDate = $result->exam && $result->exam->start_time
            ? $result->exam->start_time->format('Y-m-d')
            : 'N/A';

        // Determine pass/fail status
        $status = 'N/A';
        if ($result->exam && $result->exam->pass_marks) {
            $status = $score >= $result->exam->pass_marks ? 'Passed' : 'Failed';
        }

        return [
            $studentName,
            $studentId,
            $batchName,
            $courseName,
            $examName,
            $score,
            $totalMarks,
            $percentage,
            $grade,
            $rank,
            $examDate,
            $status,
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
                    'startColor' => ['rgb' => '7B1FA2'], // Purple color for performance reports
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
