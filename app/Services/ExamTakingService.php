<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Exam;
use App\Storage\CatboxStorage;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\CqSubmission;
use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExamTakingService
{
    /**
     * Start an exam attempt for a student.
     */
    public function startAttempt(Student $student, Exam $exam): ExamAttempt
    {
        // Check if already has an attempt
        $existing = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ExamAttempt::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'started_at' => Carbon::now(),
            'status' => 'in_progress',
            'ip_address' => request()->ip(),
            'answers' => [],
            'time_per_question' => [],
        ]);
    }

    /**
     * Save an answer for a question (auto-save).
     */
    public function saveAnswer(ExamAttempt $attempt, int $questionId, string $answer): bool
    {
        if ($attempt->status !== 'in_progress' || $attempt->isExpired()) {
            return false;
        }

        $answers = $attempt->answers ?? [];
        $answers[$questionId] = $answer;
        
        $timePerQuestion = $attempt->time_per_question ?? [];
        $timePerQuestion[$questionId] = Carbon::now()->toISOString();

        $attempt->update([
            'answers' => $answers,
            'time_per_question' => $timePerQuestion,
        ]);

        return true;
    }

    /**
     * Persist written CQ answers alongside the attempt so nothing is lost
     * before grading. Text answers live inside ExamAttempt.answers keyed by
     * question id; file uploads continue through submitCqAnswer().
     *
     * @param array<int, string> $textAnswers
     */
    public function saveCqTextAnswers(ExamAttempt $attempt, array $textAnswers): void
    {
        $answers = $attempt->answers ?? [];

        foreach ($textAnswers as $questionId => $text) {
            $clean = trim(strip_tags((string) $text, '<p><br><ul><ol><li><b><strong><i><em><u>'));
            if ($clean === '') {
                unset($answers[$questionId]);
                continue;
            }
            $answers[$questionId] = mb_substr((string) $text, 0, 20000);
        }

        $attempt->update(['answers' => $answers]);
    }

    /**
     * Submit an exam attempt.
     * 
     * Requirements: 2.2
     * 
     * Task details:
     * - Validate exam attempt ownership (handled by controller)
     * - Save all answers to exam_attempts table (already saved via auto-save)
     * - Create ExamResult record with score calculation
     * - Mark attempt as submitted
     * - Redirect to results page (handled by controller)
     */
    public function submitExam(ExamAttempt $attempt): ExamResult
    {
        return DB::transaction(function () use ($attempt) {
            // Get exam details
            $exam = $attempt->exam;
            
            // Calculate score based on exam type
            if ($exam->type === 'mcq') {
                $score = $this->calculateMcqScore($attempt);
            } else {
                // CQ exams need manual grading, set score to 0 initially
                $score = 0;
            }
            
            // Mark attempt as submitted
            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => Carbon::now(),
            ]);

            $percentage = $exam->total_marks > 0 ? ($score / $exam->total_marks) * 100 : 0;

            // Get subject name from exam title or course
            $subjectName = $exam->title;
            if ($exam->course) {
                $subjectName = $exam->course->name ?? $exam->title;
            }

            // Create ExamResult record with score calculation
            return ExamResult::updateOrCreate(
                [
                    'student_id' => $attempt->student_id,
                    'exam_id' => $attempt->exam_id,
                ],
                [
                    'subject_name' => $subjectName,
                    'marks' => $score,
                    'obtained_marks' => $score,
                    'total_marks' => $exam->total_marks,
                    'grade' => $exam->type === 'cq' ? 'Pending' : $this->calculateGrade($percentage),
                    'feedback' => $exam->type === 'cq' ? 'Awaiting manual grading' : $this->getRemarks($percentage),
                ]
            );
        });
    }

    /**
     * Calculate MCQ score by comparing answers to correct answers.
     */
    public function calculateMcqScore(ExamAttempt $attempt): float
    {
        $exam = $attempt->exam;
        $questions = $exam->questions;
        $answers = $attempt->answers ?? [];
        $score = 0;

        foreach ($questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;
            if ($studentAnswer !== null && $studentAnswer === $question->correct_answer) {
                $score += $question->marks ?? 1;
            }
        }

        return $score;
    }

    /**
     * Get exam data with timer for the attempt.
     */
    public function getExamWithTimer(ExamAttempt $attempt): array
    {
        $exam = $attempt->exam;
        $questions = $exam->questions()->get();

        return [
            'attempt' => $attempt,
            'exam' => $exam,
            'questions' => $questions,
            'answers' => $attempt->answers ?? [],
            'remaining_time' => $attempt->remaining_time,
            'total_questions' => $questions->count(),
        ];
    }

    /**
     * Auto-submit expired exams (for scheduled job).
     */
    public function autoSubmitExpired(): int
    {
        $count = 0;
        
        $expiredAttempts = ExamAttempt::where('status', 'in_progress')
            ->get()
            ->filter(fn($attempt) => $attempt->isExpired());

        foreach ($expiredAttempts as $attempt) {
            $attempt->update(['status' => 'expired']);
            $this->submitExam($attempt);
            $count++;
        }

        return $count;
    }

    /**
     * Submit CQ answer with file uploads.
     */
    public function submitCqAnswer(Student $student, Exam $exam, array $files): CqSubmission
    {
        $storedFiles = [];
        $storage = app(CatboxStorage::class);

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $storedFiles[] = [
                    'path' => $storage->store(
                        $file,
                        'cq-submissions/' . $exam->id,
                        $file->getClientOriginalName()
                    ),
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
        }

        return CqSubmission::updateOrCreate(
            [
                'student_id' => $student->id,
                'exam_id' => $exam->id,
            ],
            [
                'files' => $storedFiles,
                'submitted_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Validate CQ files.
     */
    public function validateCqFiles(array $files): array
    {
        $errors = [];
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                if (!in_array($file->getMimeType(), $allowedTypes)) {
                    $errors[] = "File {$index}: Invalid file type. Allowed: PDF, JPG, PNG.";
                    continue;
                }
                if ($rejection = app(FileScanService::class)->inspect($file, $allowedExtensions)) {
                    $errors[] = "File {$index}: {$rejection}";
                    continue;
                }
                if ($file->getSize() > $maxSize) {
                    $errors[] = "File {$index}: File size exceeds 10MB limit.";
                }
            }
        }

        return $errors;
    }

    /**
     * Evaluate a CQ submission.
     */
    public function evaluateCq(CqSubmission $submission, float $marks, string $feedback, int $evaluatorId): CqSubmission
    {
        $submission->update([
            'marks' => $marks,
            'feedback' => $feedback,
            'evaluated_at' => Carbon::now(),
            'evaluated_by' => $evaluatorId,
        ]);

        // Create or update exam result
        $exam = $submission->exam;
        
        // Get subject name from exam title or course
        $subjectName = $exam->title;
        if ($exam->course) {
            $subjectName = $exam->course->name ?? $exam->title;
        }
        
        ExamResult::updateOrCreate(
            [
                'student_id' => $submission->student_id,
                'exam_id' => $submission->exam_id,
            ],
            [
                'subject_name' => $subjectName,
                'marks' => $marks,
                'obtained_marks' => $marks,
                'total_marks' => $exam->total_marks,
                'grade' => $this->calculateGrade(($marks / $exam->total_marks) * 100),
                'feedback' => $feedback,
            ]
        );

        return $submission;
    }

    /**
     * Get aggregated result combining MCQ and CQ marks.
     */
    public function getAggregatedResult(Student $student, Exam $exam): array
    {
        $mcqResult = ExamResult::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $cqSubmission = CqSubmission::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $mcqMarks = $mcqResult?->marks ?? 0;
        $cqMarks = $cqSubmission?->marks ?? 0;
        $totalMarks = $mcqMarks + $cqMarks;
        $totalPossible = $exam->total_marks;

        return [
            'mcq_marks' => $mcqMarks,
            'cq_marks' => $cqMarks,
            'total_marks' => $totalMarks,
            'total_possible' => $totalPossible,
            'percentage' => $totalPossible > 0 ? round(($totalMarks / $totalPossible) * 100, 2) : 0,
            'grade' => $this->calculateGrade(($totalMarks / $totalPossible) * 100),
        ];
    }

    /**
     * Calculate grade from percentage.
     */
    private function calculateGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'A+',
            $percentage >= 70 => 'A',
            $percentage >= 60 => 'A-',
            $percentage >= 50 => 'B',
            $percentage >= 40 => 'C',
            $percentage >= 33 => 'D',
            default => 'F',
        };
    }

    /**
     * Get remarks from percentage.
     */
    private function getRemarks(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Excellent',
            $percentage >= 70 => 'Very Good',
            $percentage >= 60 => 'Good',
            $percentage >= 50 => 'Satisfactory',
            $percentage >= 40 => 'Needs Improvement',
            default => 'Failed',
        };
    }
}
