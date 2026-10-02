<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     *
     * An exam with no duration is untimed, and is reported as unlimited rather
     * than as zero. Treating a NULL duration as zero made `isExpired()` return
     * true immediately, so every answer was rejected and the student was scored
     * zero while their on-screen timer counted down from PHP_INT_MAX.
     */
    public function getRemainingTimeAttribute(): int
    {
        if (! $this->exam || ! $this->started_at || ! $this->exam->duration_minutes) {
            return $this->exam && ! $this->exam->duration_minutes ? PHP_INT_MAX : 0;
        }

        $durationSeconds = (int) $this->exam->duration_minutes * 60;

        // Subtracted from raw timestamps: Carbon's diffInSeconds() changed sign
        // convention between major versions, which silently produced a huge
        // elapsed value here.
        $elapsedSeconds = max(0, Carbon::now()->getTimestamp() - $this->started_at->getTimestamp());

        return max(0, $durationSeconds - $elapsedSeconds);
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
        return $this->status === 'in_progress' && ! $this->isExpired();
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
            ->whereRaw('TIMESTAMPDIFF(SECOND, started_at, NOW()) > (SELECT duration_minutes * 60 FROM exams WHERE exams.id = exam_attempts.exam_id)');
    }
}
