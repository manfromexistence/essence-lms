<?php

namespace App\Storage;

use RuntimeException;
use Throwable;

/**
 * Raised when Catbox rejects an upload or cannot be reached.
 *
 * This is surfaced to admins verbatim because, unlike most storage failures,
 * it almost always means the file itself is unacceptable to Catbox (over the
 * size limit, or an extension it refuses) rather than a transient fault.
 */
class CatboxUploadFailed extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $reason,
        public readonly int $status,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function transport(string $error, int $status = 0): self
    {
        return new self(
            'Could not reach the media host (catbox.moe). Check that outbound HTTPS is allowed, then try again.',
            $error,
            $status,
        );
    }

    public static function rejected(string $reason, int $status = 0): self
    {
        return new self(
            'The media host (catbox.moe) refused this file: '.$reason.'.',
            $reason,
            $status,
        );
    }
}
