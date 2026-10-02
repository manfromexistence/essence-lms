<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'question_text',
        'type',
        'options',
        'correct_answer',
        'marks',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'marks' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Get the exam that owns the question.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Options keyed by their letter (A, B, C, ...).
     *
     * `options` is stored inconsistently: the admin form and the seeder write a
     * plain list (`['A) apple', 'B) ball']`), while older rows are letter-keyed
     * (`['A' => 'apple']`). The student paper iterates letters and indexes by
     * letter, so reading a list that way returned null and rendered no options at
     * all — an unanswerable MCQ paper that silently scored zero.
     *
     * Both shapes are accepted here so the data does not need migrating.
     *
     * @return array<string, string>
     */
    public function optionMap(): array
    {
        $options = $this->options;

        if (! is_array($options) || $options === []) {
            return [];
        }

        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        // Letter-keyed already: keep the keys, just upper-case them.
        $hasLetters = array_intersect(array_map('strval', array_keys($options)), $letters) !== [];
        if ($hasLetters) {
            $map = [];
            foreach ($options as $key => $value) {
                $map[strtoupper((string) $key)] = $value;
            }

            return $map;
        }

        $map = [];
        foreach (array_values($options) as $index => $value) {
            if ($index >= count($letters)) {
                break;
            }
            $map[$letters[$index]] = $value;
        }

        return $map;
    }

    /**
     * The letter (A, B, C, ...) that `correct_answer` designates, if any.
     *
     * The admin form asks for "the correct option text", so `correct_answer` is
     * commonly stored as free text like "A) apple" rather than as a bare letter.
     */
    public function correctAnswerLetter(): ?string
    {
        $raw = trim((string) $this->correct_answer);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^([A-F])(?:\s*[).:\-]|\s+)/i', $raw, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        // A bare letter, e.g. "A".
        if (preg_match('/^[A-F]$/i', $raw) === 1) {
            return strtoupper($raw);
        }

        return null;
    }

    /**
     * Check if the given answer is correct.
     *
     * The student submits a bare letter ("A"), which was previously compared with
     * `===` against the stored free text ("A) apple"). That never matched, so every
     * MCQ question scored zero no matter what the student chose.
     */
    public function isCorrectAnswer(string $answer): bool
    {
        $answer = trim($answer);
        $expected = trim((string) $this->correct_answer);

        if ($answer === '' || $expected === '') {
            return false;
        }

        // Exact text match still wins, so an explicitly typed-out answer works.
        if (strcasecmp($answer, $expected) === 0) {
            return true;
        }

        $letter = $this->correctAnswerLetter();

        return $letter !== null && strcasecmp($answer, $letter) === 0;
    }

    /**
     * Check if this is an MCQ question.
     */
    public function isMcq(): bool
    {
        return $this->type === 'mcq';
    }

    /**
     * Check if this is a CQ (creative question).
     */
    public function isCq(): bool
    {
        return $this->type === 'cq';
    }

    /**
     * Check if this is a true/false question.
     */
    public function isTrueFalse(): bool
    {
        return $this->type === 'true_false';
    }

    /**
     * Check if this is a short answer question.
     */
    public function isShortAnswer(): bool
    {
        return $this->type === 'short_answer';
    }

    /**
     * Get the options as a formatted array for display.
     */
    public function getFormattedOptionsAttribute(): array
    {
        if (! $this->options || ! is_array($this->options)) {
            return [];
        }

        return $this->options;
    }

    /**
     * Scope a query to only include questions of a given type.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order by the question order.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
