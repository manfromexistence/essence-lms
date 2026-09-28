<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'type',
        'batch_id',
        'course_id',
        'total_marks',
        'pass_marks',
        'duration_minutes',
        'start_time',
        'end_time',
        'status',
        'instructions',
    ];

    /**
     * Alias for the `title` column.
     *
     * Several views and exports were written against `$exam->name`, but the
     * column is `title` — so those headings silently rendered empty (an
     * undefined attribute returns null rather than raising). Exposing `name`
     * as an accessor fixes every one of those call sites at once.
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'total_marks' => 'integer',
            'pass_marks' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * Get the batch that owns the exam.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the course that owns the exam.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the questions for the exam.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Get the results for the exam.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Check if the exam is currently active (within time window).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->start_time && $now->lt($this->start_time)) {
            return false;
        }

        if ($this->end_time && $now->gt($this->end_time)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the exam is scheduled for the future.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->start_time && now()->lt($this->start_time);
    }

    /**
     * Check if the exam has ended.
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->status === 'completed' || ($this->end_time && now()->gt($this->end_time));
    }

    /**
     * Get the total number of questions.
     *
     * @return int
     */
    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }

    /**
     * Scope a query to only include exams of a given type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include exams with a given status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active exams.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include exams for a specific batch.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $batchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope a query to only include exams for a specific course.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $courseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }
}
