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
     */
    public function generate(?Batch $batch = null): string
    {
        $pattern = $this->settingsService->get('student_id_format', '{YEAR}{BATCH}{SEQ:4}');

        return DB::transaction(function () use ($pattern, $batch) {
            $id = $this->parsePattern($pattern, $batch);
            return $id;
        });
    }

    /**
     * Parse the pattern and replace tokens with actual values.
     */
    protected function parsePattern(string $pattern, ?Batch $batch = null): string
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
            $sequence = $this->getNextSequence();
            $sequenceStr = str_pad($sequence, $digits, '0', STR_PAD_LEFT);
            $result = preg_replace('/\{SEQ(?::\d+)?\}/', $sequenceStr, $result);
        }

        return $result;
    }

    /**
     * Get the next sequence number with database locking.
     */
    public function getNextSequence(): int
    {
        // Get the current year for yearly sequence reset
        $currentYear = date('Y');

        // Get the highest sequence number for this year
        $startOfYear = Carbon::create($currentYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::create($currentYear, 12, 31)->endOfYear();
        $lastStudent = Student::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastStudent && preg_match('/(\d+)$/', $lastStudent->registration_no, $matches)) {
            return (int) $matches[1] + 1;
        }

        // Return the configured start number or default to 1
        return (int) $this->settingsService->get('student_id_sequence_start', 1);
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
     */
    public function generateUnique(?Batch $batch = null, int $maxAttempts = 10): string
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $id = $this->generate($batch);

            if (!$this->exists($id)) {
                return $id;
            }

            $attempts++;
        }

        throw new \RuntimeException('Unable to generate unique student ID after ' . $maxAttempts . ' attempts');
    }
}
