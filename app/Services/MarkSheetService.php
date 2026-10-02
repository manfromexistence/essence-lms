<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class MarkSheetService
{
    /**
     * Generate a mark sheet PDF for a student and exam.
     */
    public function generateMarkSheet(Student $student, ?Exam $exam = null): Response
    {
        $student->load(['batch.course']);

        if ($exam) {
            $result = ExamResult::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();

            $data = [
                'student' => $student,
                'exam' => $exam,
                'result' => $result,
                'generated_at' => now(),
                'type' => 'single',
            ];
        } else {
            // Generate overall mark sheet with all results
            $results = ExamResult::where('student_id', $student->id)
                ->with('exam')
                ->orderBy('created_at')
                ->get();

            $totalMarks = $results->sum('marks');
            $totalPossible = $results->sum('total_marks');
            $overallPercentage = $totalPossible > 0 ? round(($totalMarks / $totalPossible) * 100, 2) : 0;

            $data = [
                'student' => $student,
                'results' => $results,
                'total_marks' => $totalMarks,
                'total_possible' => $totalPossible,
                'overall_percentage' => $overallPercentage,
                'overall_grade' => $this->calculateGrade($overallPercentage),
                'generated_at' => now(),
                'type' => 'overall',
            ];
        }

        $pdf = Pdf::loadView('pdf.mark-sheet', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'mark-sheet-'.$student->student_id.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate mark sheet as stream (for preview).
     */
    public function previewMarkSheet(Student $student, ?Exam $exam = null): string
    {
        $student->load(['batch.course']);

        if ($exam) {
            $result = ExamResult::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();

            $data = [
                'student' => $student,
                'exam' => $exam,
                'result' => $result,
                'generated_at' => now(),
                'type' => 'single',
            ];
        } else {
            $results = ExamResult::where('student_id', $student->id)
                ->with('exam')
                ->orderBy('created_at')
                ->get();

            $totalMarks = $results->sum('marks');
            $totalPossible = $results->sum('total_marks');
            $overallPercentage = $totalPossible > 0 ? round(($totalMarks / $totalPossible) * 100, 2) : 0;

            $data = [
                'student' => $student,
                'results' => $results,
                'total_marks' => $totalMarks,
                'total_possible' => $totalPossible,
                'overall_percentage' => $overallPercentage,
                'overall_grade' => $this->calculateGrade($overallPercentage),
                'generated_at' => now(),
                'type' => 'overall',
            ];
        }

        $pdf = Pdf::loadView('pdf.mark-sheet', $data);

        return $pdf->stream();
    }

    /**
     * Calculate grade from percentage.
     */
    private function calculateGrade(float $percentage): string
    {
        return ExamResult::gradeForPercentage($percentage);
    }
}
