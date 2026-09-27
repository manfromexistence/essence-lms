<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentIdGenerator
{
    /**
     * Supported tokens in the ID format pattern.
     */
    protected const SUPPORTED_TOKENS = [
        '{YEAR}',
        '{MONTH}',
        '{BATCH}',
        '{SEQ}',
    ];

    public function __construct(
        protected SettingsService $settingsService
    ) {}

    /**
     * Generate a student registration number based on the configured pattern.
     *
     * @param  int  $sequenceOffset  Added to the computed sequence. Used by
     *                               generateUnique() to step past a colliding
     *                               value without recomputing from scratch.
     */
    public function generate(?Batch $batch = null, int $sequenceOffset = 0): string
    {
        $pattern = $this->settingsService->get('student_id_format', '{YEAR}{BATCH}{SEQ:4}');

        return DB::transaction(function () use ($pattern, $batch, $sequenceOffset) {
            $id = $this->parsePattern($pattern, $batch, $sequenceOffset);
            return $id;
        });
    }

    /**
     * Parse the pattern and replace tokens with actual values.
     */
    protected function parsePattern(string $pattern, ?Batch $batch = null, int $sequenceOffset = 0): string
    {
        $result = $pattern;

        // Replace {YEAR} with current year
        $result = str_replace('{YEAR}', date('Y'), $result);

        // Replace {MONTH} with current month (2 digits)
        $result = str_replace('{MONTH}', date('m'), $result);

        // Replace {BATCH} with batch code or ID
        if ($batch) {
            $batchCode = $batch->code ?? str_pad($batch->id, 2, '0', STR_PAD_LEFT);
            $result = str_replace('{BATCH}', $batchCode, $result);
        } else {
            $result = str_replace('{BATCH}', '00', $result);
        }

        // Handle {SEQ} or {SEQ:n} where n is the number of digits
        if (preg_match('/\{SEQ(?::(\d+))?\}/', $result, $matches)) {
            $digits = isset($matches[1]) ? (int) $matches[1] : 4;
            $sequence = $this->safeInt($this->getNextSequence() + $sequenceOffset);
            $sequenceStr = str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT);
            $result = preg_replace('/\{SEQ(?::\d+)?\}/', $sequenceStr, $result);
        }

        return $result;
    }

    /**
     * Get the next sequence number with database locking.
     *
     * The sequence is derived from the *highest* trailing number present among
     * this year's registration numbers — not merely from the most recently
     * created row. Using the last row by id was fragile: whenever a legacy or
     * hand-edited registration number sat at the top of the table (e.g. a
     * duplicated/unparsable tail), every retry in generateUnique() recomputed
     * the exact same colliding ID and the admission failed after 10 attempts.
     */
    public function getNextSequence(): int
    {
        $currentYear = date('Y');

        $startOfYear = Carbon::create($currentYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::create($currentYear, 12, 31)->endOfYear();

        // Pull the trailing digit group of every registration number issued
        // this year and take the largest. Scanning the whole year (rather than
        // one row) makes the result idempotent: the same "next" value is
        // returned no matter which row was inserted last.
        $numbers = Student::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->pluck('registration_no');

        $highest = 0;
        foreach ($numbers as $registrationNo) {
            if (preg_match('/(\d+)$/', (string) $registrationNo, $matches)) {
                $candidate = $this->safeInt($matches[1]);
                if ($candidate > $highest && $candidate < PHP_INT_MAX) {
                    $highest = $candidate;
                }
            }
        }

        if ($highest > 0) {
            return $highest + 1;
        }

        // Fresh year / no parseable rows yet — start from the configured floor.
        return max(1, $this->safeInt($this->settingsService->get('student_id_sequence_start', 1)));
    }

    /**
     * Convert an arbitrary value to a non-negative int without ever
     * overflowing (which would silently produce a float and break the
     * `: int` return contract).
     */
    private function safeInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return 0;
        }

        // Trim leading zeros; anything longer than PHP_INT_MAX's digit count
        // can never fit in an int, so clamp it instead of letting (int)
        // saturate and arithmetic overflow into a float.
        $digits = ltrim($digits, '0');
        $maxDigits = strlen((string) PHP_INT_MAX);

        if (strlen($digits) > $maxDigits) {
            return PHP_INT_MAX;
        }

        $int = (int) $digits;

        return $int > PHP_INT_MAX ? PHP_INT_MAX : $int;
    }

    /**
     * Validate a pattern string.
     */
    public function validatePattern(string $pattern): bool
    {
        // Pattern must not be empty
        if (empty($pattern)) {
            return false;
        }

        // Pattern must contain at least one token
        $hasToken = false;
        foreach (self::SUPPORTED_TOKENS as $token) {
            if (str_contains($pattern, $token) || preg_match('/\{SEQ(:\d+)?\}/', $pattern)) {
                $hasToken = true;
                break;
            }
        }

        if (!$hasToken) {
            return false;
        }

        // Check for invalid tokens (anything in curly braces that's not supported)
        if (preg_match_all('/\{([^}]+)\}/', $pattern, $matches)) {
            foreach ($matches[1] as $token) {
                $fullToken = '{' . $token . '}';
                $isValid = in_array($fullToken, self::SUPPORTED_TOKENS) ||
                           preg_match('/^SEQ(:\d+)?$/', $token);
                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get a preview of what the generated ID would look like.
     */
    public function preview(string $pattern, ?Batch $batch = null): string
    {
        $result = $pattern;

        // Replace {YEAR} with current year
        $result = str_replace('{YEAR}', date('Y'), $result);

        // Replace {MONTH} with current month
        $result = str_replace('{MONTH}', date('m'), $result);

        // Replace {BATCH} with batch code or placeholder
        if ($batch) {
            $batchCode = $batch->code ?? str_pad($batch->id, 2, '0', STR_PAD_LEFT);
            $result = str_replace('{BATCH}', $batchCode, $result);
        } else {
            $result = str_replace('{BATCH}', 'XX', $result);
        }

        // Handle {SEQ} or {SEQ:n}
        if (preg_match('/\{SEQ(?::(\d+))?\}/', $result, $matches)) {
            $digits = isset($matches[1]) ? (int) $matches[1] : 4;
            $sequenceStr = str_repeat('0', $digits - 1) . '1';
            $result = preg_replace('/\{SEQ(?::\d+)?\}/', $sequenceStr, $result);
        }

        return $result;
    }

    /**
     * Check if a registration number already exists.
     */
    public function exists(string $registrationNo): bool
    {
        return Student::where('registration_no', $registrationNo)->exists();
    }

    /**
     * Generate a unique registration number, retrying if collision occurs.
     *
     * getNextSequence() is now deterministic across retries (it reads the
     * highest sequence for the year), so a plain "call it again" loop would
     * spin on the same value. After the first collision we therefore escalate
     * the requested sequence so a stubborn legacy row can never wedge the
     * admission permanently.
     */
    public function generateUnique(?Batch $batch = null, int $maxAttempts = 10): string
    {
        $attempts = 0;
        $offset = 0;

        while ($attempts < $maxAttempts) {
            $id = $this->generate($batch, $offset);

            if (!$this->exists($id)) {
                return $id;
            }

            $offset++;
            $attempts++;
        }

        throw new \RuntimeException('Unable to generate unique student ID after ' . $maxAttempts . ' attempts');
    }
}
