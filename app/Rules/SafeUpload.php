<?php

namespace App\Rules;

use App\Services\FileScanService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Content-level upload scan: verifies the file's real content matches its
 * declared type and contains no embedded threats (see FileScanService).
 *
 * Usage: 'screenshot' => ['required', 'file', 'mimes:jpg,png,pdf', new SafeUpload]
 */
class SafeUpload implements ValidationRule
{
    /**
     * @param array<int, string> $allowedExtensions extension whitelist; empty = any known type
     */
    public function __construct(protected array $allowedExtensions = [])
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $rejection = app(FileScanService::class)->inspect($value, $this->allowedExtensions);

        if ($rejection !== null) {
            $fail($rejection);
        }
    }
}
