<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_id',
        'started_at',
        'submitted_at',
        'auto_submitted_at',
        'answers',
        'screenshots',
        'time_per_question',
        'status',
        'ip_address',
        'tab_switches',
        'flagged_for_cheating',
        'cheating_notes',
        'cheating_events',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'auto_submitted_at' => 'datetime',
            'answers' => 'array',
            'screenshots' => 'array',
            'cheating_events' => 'array',
            'time_per_question' => 'array',
            'flagged_for_cheating' => 'boolean',
        ];
    }

    /**
     * Get the student who made the attempt.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the exam being attempted.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get remaining time in seconds.
     */
    public function getRemainingTimeAttribute(): int
    {
        if (!$this->exam || !$this->started_at) {
            return 0;
        }

        $durationSeconds = ($this->exam->duration_minutes ?? 0) * 60;
        $elapsedSeconds = Carbon::now()->diffInSeconds($this->started_at);
        $remaining = $durationSeconds - $elapsedSeconds;

        return max(0, $remaining);
    }

    /**
     * Check if the attempt has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        return $this->remaining_time <= 0;
    }

    /**
     * Check if the attempt is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress' && !$this->isExpired();
    }

    /**
     * Get the answer for a specific question.
     */
    public function getAnswer(int $questionId): ?string
    {
        return $this->answers[$questionId] ?? null;
    }

    /**
     * Set an answer for a specific question.
     */
    public function setAnswer(int $questionId, string $answer): void
    {
        $answers = $this->answers ?? [];
        $answers[$questionId] = $answer;
        $this->answers = $answers;
    }

    /**
     * Scope to get in-progress attempts.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get submitted attempts.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope to get expired attempts that need auto-submission.
     */
    public function scopeExpiredAndPending($query)
    {
        return $query->where('status', 'in_progress')
            ->whereRaw('TIMESTAMPDIFF(SECOND, started_at, NOW()) > (SELECT duration * 60 FROM exams WHERE exams.id = exam_attempts.exam_id)');
    }
}
