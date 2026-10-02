<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    /**
     * Create an MCQ exam.
     */
    public function createMcqExam(array $data): Exam
    {
        $data['type'] = 'mcq';

        return $this->createExam($data);
    }

    /**
     * Create a CQ exam.
     */
    public function createCqExam(array $data): Exam
    {
        $data['type'] = 'cq';

        return $this->createExam($data);
    }

    /**
     * Create an exam.
     */
    protected function createExam(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'title' => $data['title'],
                'type' => $data['type'],
                'batch_id' => $data['batch_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'total_marks' => $data['total_marks'] ?? 100,
                'pass_marks' => $data['pass_marks'] ?? 40,
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'instructions' => $data['instructions'] ?? null,
            ]);

            // Add questions if provided
            if (! empty($data['questions'])) {
                foreach ($data['questions'] as $index => $questionData) {
                    $this->addQuestion($exam, array_merge($questionData, ['order' => $index + 1]));
                }
            }

            return $exam;
        });
    }

    /**
     * Add a question to an exam.
     */
    public function addQuestion(Exam $exam, array $questionData): Question
    {
        $maxOrder = $exam->questions()->max('order') ?? 0;

        return Question::create([
            'exam_id' => $exam->id,
            'question_text' => $questionData['question_text'],
            'type' => $questionData['type'] ?? $exam->type,
            'options' => $questionData['options'] ?? null,
            'correct_answer' => $questionData['correct_answer'] ?? null,
            'marks' => $questionData['marks'] ?? 1,
            'order' => $questionData['order'] ?? $maxOrder + 1,
        ]);
    }

    /**
     * Update a question.
     */
    public function updateQuestion(Question $question, array $data): Question
    {
        $question->update($data);

        return $question->fresh();
    }

    /**
     * Delete a question.
     */
    public function deleteQuestion(Question $question): bool
    {
        return $question->delete();
    }

    /**
     * Submit exam answers and calculate score for MCQ.
     */
    public function submitExam(Exam $exam, Student $student, array $answers): ExamResult
    {
        // Check if exam is active
        if (! $exam->isActive()) {
            throw new \RuntimeException('Exam is not currently active');
        }

        // Check if student already submitted
        $existingResult = ExamResult::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingResult) {
            throw new \RuntimeException('Student has already submitted this exam');
        }

        return DB::transaction(function () use ($exam, $student, $answers) {
            $obtainedMarks = 0;

            if ($exam->type === 'mcq') {
                $obtainedMarks = $this->calculateMcqScore($exam, $answers);
            }

            // Get subject name from exam title or course
            $subjectName = $exam->title;
            if ($exam->course) {
                $subjectName = $exam->course->name ?? $exam->title;
            }

            $result = ExamResult::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'subject_name' => $subjectName,
                'answers' => $answers,
                'marks' => $obtainedMarks,
                'total_marks' => $exam->total_marks,
                'obtained_marks' => $obtainedMarks,
                'grade' => $this->calculateGrade($obtainedMarks, $exam->total_marks),
            ]);

            // Update rankings
            $this->updateRankings($exam);

            return $result;
        });
    }

    /**
     * Calculate MCQ score.
     */
    public function calculateMcqScore(Exam $exam, array $answers): int
    {
        $score = 0;
        $questions = $exam->questions;

        foreach ($questions as $question) {
            $questionId = (string) $question->id;
            if (isset($answers[$questionId])) {
                if ($question->isCorrectAnswer($answers[$questionId])) {
                    $score += $question->marks;
                }
            }
        }

        return $score;
    }

    /**
     * Calculate grade based on marks.
     */
    public function calculateGrade(int $obtainedMarks, int $totalMarks): string
    {
        if ($totalMarks === 0) {
            return 'N/A';
        }

        return ExamResult::gradeForPercentage(($obtainedMarks / $totalMarks) * 100);
    }

    /**
     * Get leaderboard for an exam.
     */
    public function getLeaderboard(Exam $exam, int $limit = 50): Collection
    {
        return ExamResult::where('exam_id', $exam->id)
            ->with(['student.user'])
            ->orderByDesc('obtained_marks')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($result, $index) {
                $result->position = $index + 1;

                return $result;
            });
    }

    /**
     * Update rankings for an exam.
     */
    protected function updateRankings(Exam $exam): void
    {
        $results = ExamResult::where('exam_id', $exam->id)
            ->orderByDesc('obtained_marks')
            ->orderBy('created_at')
            ->get();

        $rank = 1;
        foreach ($results as $result) {
            $result->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Schedule an exam.
     */
    public function scheduleExam(Exam $exam, Carbon $startTime, Carbon $endTime): Exam
    {
        if ($startTime->gte($endTime)) {
            throw new \InvalidArgumentException('Start time must be before end time');
        }

        $exam->update([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
        ]);

        return $exam->fresh();
    }

    /**
     * Activate an exam.
     */
    public function activateExam(Exam $exam): Exam
    {
        $exam->update(['status' => 'active']);

        return $exam->fresh();
    }

    /**
     * Complete an exam.
     */
    public function completeExam(Exam $exam): Exam
    {
        $exam->update(['status' => 'completed']);
        $this->updateRankings($exam);

        return $exam->fresh();
    }

    /**
     * Get exam with all relations.
     */
    public function getWithRelations(int $id): Exam
    {
        return Exam::with([
            'batch',
            'course',
            'questions',
            'results' => function ($query) {
                $query->with('student.user')->orderByDesc('obtained_marks');
            },
        ])->findOrFail($id);
    }

    /**
     * Get paginated exams.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Exam::with(['batch', 'course'])
            ->withCount('questions');

        if (! empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (! empty($filters['batch_id'])) {
            $query->forBatch($filters['batch_id']);
        }

        if (! empty($filters['course_id'])) {
            $query->forCourse($filters['course_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get active exams for a student.
     */
    public function getActiveExamsForStudent(Student $student): Collection
    {
        return Exam::active()
            ->where(function ($query) use ($student) {
                $query->where('batch_id', $student->batch_id)
                    ->orWhereNull('batch_id');
            })
            ->whereDoesntHave('results', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->with(['batch', 'course'])
            ->get();
    }

    /**
     * Record manual result for CQ exam.
     */
    public function recordManualResult(Exam $exam, Student $student, array $data): ExamResult
    {
        // Get subject name from exam title or course
        $subjectName = $exam->title;
        if ($exam->course) {
            $subjectName = $exam->course->name ?? $exam->title;
        }

        return ExamResult::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'subject_name' => $subjectName,
                'marks' => $data['obtained_marks'],
                'obtained_marks' => $data['obtained_marks'],
                'total_marks' => $exam->total_marks,
                'grade' => $this->calculateGrade($data['obtained_marks'], $exam->total_marks),
                'feedback' => $data['feedback'] ?? null,
            ]
        );
    }

    /**
     * Get exam statistics.
     */
    public function getStatistics(Exam $exam): array
    {
        $results = $exam->results;

        if ($results->isEmpty()) {
            return [
                'total_participants' => 0,
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'pass_rate' => 0,
            ];
        }

        return [
            'total_participants' => $results->count(),
            'average_score' => round($results->avg('obtained_marks'), 2),
            'highest_score' => $results->max('obtained_marks'),
            'lowest_score' => $results->min('obtained_marks'),
            'pass_rate' => round(($results->where('obtained_marks', '>=', $exam->pass_marks)->count() / $results->count()) * 100, 2),
        ];
    }
}
