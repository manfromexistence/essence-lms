<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'exam_id',
        'subject_name',
        'marks',
        'grade',
        'answers',
        'total_marks',
        'obtained_marks',
        'rank',
        'feedback',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'marks' => 'integer',
            'total_marks' => 'integer',
            'obtained_marks' => 'integer',
            'rank' => 'integer',
        ];
    }

    /**
     * Get the student that owns the result.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the exam that owns the result.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the percentage score.
     */
    public function getPercentageAttribute(): float
    {
        if (! $this->total_marks || $this->total_marks === 0) {
            return 0;
        }

        return round(($this->obtained_marks / $this->total_marks) * 100, 2);
    }

    /**
     * Check if the student passed the exam.
     */
    public function hasPassed(): bool
    {
        if (! $this->exam) {
            return false;
        }

        return $this->obtained_marks >= $this->exam->pass_marks;
    }

    /**
     * The institute's single grading scale.
     *
     * This is the canonical definition. It used to exist in five places with
     * two different sets of thresholds, so the letter stored on the row and the
     * letter recomputed for a report could disagree: 72% was stored as "A" but
     * reported as "B", and 45% was stored as "C" but reported as "F".
     *
     * These thresholds are the ones that write the `grade` column, so they are
     * the ones adopted. Changing them would re-letter historical results; if
     * the institute ever wants different bands, change them here and nowhere
     * else.
     */
    public static function gradeForPercentage(float $percentage): string
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
     * Calculate grade based on percentage.
     */
    public function calculateGrade(): string
    {
        return self::gradeForPercentage($this->percentage);
    }

    /**
     * Scope a query to only include results for a specific exam.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope a query to only include results for a specific student.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to order by rank.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrderByRank($query)
    {
        return $query->orderBy('rank');
    }

    /**
     * Scope a query to order by obtained marks descending.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrderByScore($query)
    {
        return $query->orderByDesc('obtained_marks');
    }
}
