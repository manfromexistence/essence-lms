<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Content-level security scanning for user uploads.
 *
 * Defense in depth for documents and payment proofs:
 *  1. Magic-byte signature validation (a file must actually be what it claims).
 *  2. Embedded-threat pattern checks (PHP code, active PDF content, script
 *     markup in text/SVG payloads).
 *  3. Optional ClamAV antivirus scan via the clamd INSTREAM protocol. When
 *     CLAMAV_HOST is configured but unreachable, scanning fails closed.
 */
class FileScanService
{
    /** Binary signatures: extension => list of (offset, bytes) requirements. */
    protected const SIGNATURES = [
        'jpg' => [[0, "\xFF\xD8\xFF"]],
        'jpeg' => [[0, "\xFF\xD8\xFF"]],
        'png' => [[0, "\x89PNG\r\n\x1A\n"]],
        'gif' => [[0, 'GIF87a'], [0, 'GIF89a']],
        'webp' => [[0, 'RIFF'], [8, 'WEBP']],
        'pdf' => [[0, '%PDF-']],
        'mp4' => [[4, 'ftyp']],
        'm4v' => [[4, 'ftyp']],
        'mov' => [[4, 'ftyp']],
        'webm' => [[0, "\x1A\x45\xDF\xA3"]],
        'mp3' => [[0, 'ID3'], [0, "\xFF\xFB"], [0, "\xFF\xF3"], [0, "\xFF\xF2"]],
        'wav' => [[0, 'RIFF'], [8, 'WAVE']],
        'zip' => [[0, "PK\x03\x04"]],
        // Office Open XML / OOXML formats are ZIP containers.
        'docx' => [[0, "PK\x03\x04"]],
        'xlsx' => [[0, "PK\x03\x04"]],
        'pptx' => [[0, "PK\x03\x04"]],
        'doc' => [[0, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"]],
        'xls' => [[0, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"]],
        'ppt' => [[0, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"]],
    ];

    /** Text-oriented formats validated as UTF-8 text instead of signatures. */
    protected const TEXT_TYPES = ['txt', 'csv'];

    /** Patterns that must never appear inside an uploaded file. */
    protected const THREAT_PATTERNS = [
        '<?php',
        '<?=',
    ];

    /** Active-content patterns rejected inside PDF payloads. */
    protected const PDF_THREAT_PATTERNS = [
        '/JavaScript',
        '/JS ',
        '/JS/',
        '/Launch',
        '/EmbeddedFile',
        '/OpenAction',
        '/AA',
    ];

    /** Patterns rejected inside text/SVG payloads. */
    protected const TEXT_THREAT_PATTERNS = [
        '<script',
        'javascript:',
        'onerror=',
        'onload=',
        '<iframe',
        '<object',
        '<embed',
    ];

    public function __construct()
    {
        //
    }

    /**
     * Inspect a file. Returns null when safe, or a human-readable rejection
     * reason.
     *
     * @param  array<int, string>  $allowedExtensions
     */
    public function inspect(UploadedFile $file, array $allowedExtensions = []): ?string
    {
        if (! config('uploads.scan.enabled', true)) {
            return null;
        }

        if (! $file->isValid()) {
            return 'The file upload is invalid or incomplete.';
        }

        // Content-derived extension decides everything. getClientOriginalExtension()
        // is attacker-controlled and Symfony's own docblock says it must not be
        // trusted. Preferring it let a file named "notes.pdf.html" carry a
        // byte-valid PDF body — which satisfies Laravel's content-based `mimes:`
        // check — while this allowlist, and the extension actually stored, both
        // saw "html", and the host serves that inline as text/html on a public
        // origin.
        //
        // A disagreement between the two is logged rather than rejected: content
        // sniffing is legitimately imprecise (progressive JPEGs, EXIF), and
        // rejecting outright breaks honest uploads. It is no longer a security
        // event, because the client-declared name no longer influences either
        // the allowlist decision or the stored extension.
        $guessed = strtolower((string) $file->guessExtension());
        $claimed = strtolower($file->getClientOriginalExtension());

        $extension = $guessed !== '' ? $guessed : $claimed;

        if ($claimed !== '' && $guessed !== '' && $claimed !== $guessed) {
            Log::notice('Uploaded file extension disagrees with its contents; using the content-derived type.', [
                'claimed' => $claimed,
                'detected' => $guessed,
            ]);
        }

        if ($allowedExtensions !== [] && ! in_array($extension, $allowedExtensions, true)) {
            return 'The file type "'.$extension.'" is not allowed.';
        }

        $size = $file->getSize();
        if ($size === false || $size === null) {
            return 'The file size could not be determined.';
        }

        // A file can disappear between upload and scan (e.g. an antivirus
        // engine quarantining it); treat that as a rejection, not a crash.
        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            Log::warning('Upload rejected: file vanished before scanning', [
                'original_name' => $file->getClientOriginalName(),
            ]);

            return 'The uploaded file could not be read for scanning.';
        }

        $handle = @fopen($realPath, 'rb');
        if ($handle === false) {
            return 'The uploaded file could not be read for scanning.';
        }

        try {
            $head = (string) fread($handle, 64);
            $maxPatternBytes = min(
                (int) config('uploads.scan.max_pattern_bytes', 32 * 1024 * 1024),
                max($size, 1)
            );
            rewind($handle);
            $content = (string) fread($handle, $maxPatternBytes);

            // 1) Signature validation — the content must match its declared type.
            if (! in_array($extension, self::TEXT_TYPES, true)) {
                $signatureError = $this->checkSignature($extension, $head);
                if ($signatureError !== null) {
                    return $signatureError;
                }
            } elseif (! mb_check_encoding($content, 'UTF-8')) {
                return 'Text files must be valid UTF-8 documents.';
            }

            // 2) Embedded-threat patterns.
            foreach (self::THREAT_PATTERNS as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    Log::warning('Upload rejected: embedded code detected', [
                        'original_name' => $file->getClientOriginalName(),
                        'extension' => $extension,
                        'pattern' => $pattern,
                    ]);

                    return 'The file contains executable code and was rejected.';
                }
            }

            if ($extension === 'pdf') {
                foreach (self::PDF_THREAT_PATTERNS as $pattern) {
                    if (stripos($content, $pattern) !== false) {
                        Log::warning('Upload rejected: active PDF content detected', [
                            'original_name' => $file->getClientOriginalName(),
                            'pattern' => trim($pattern),
                        ]);

                        return 'The PDF contains active content (JavaScript/embedded files) and was rejected.';
                    }
                }
            }

            if (in_array($extension, self::TEXT_TYPES, true) || $extension === 'svg') {
                foreach (self::TEXT_THREAT_PATTERNS as $pattern) {
                    if (stripos($content, $pattern) !== false) {
                        Log::warning('Upload rejected: script markup detected', [
                            'original_name' => $file->getClientOriginalName(),
                            'pattern' => $pattern,
                        ]);

                        return 'The file contains script markup and was rejected.';
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        // 3) Optional antivirus scan (ClamAV clamd INSTREAM).
        return $this->runAntivirus($file);
    }

    /**
     * Verify the leading bytes match the declared type's signature.
     */
    protected function checkSignature(string $extension, string $head): ?string
    {
        $signatures = self::SIGNATURES[$extension] ?? null;

        if ($signatures === null) {
            // Unknown binary type: no signature to verify, allow pattern checks only.
            return null;
        }

        foreach ($signatures as [$offset, $bytes]) {
            if (substr($head, $offset, strlen($bytes)) === $bytes) {
                return null;
            }
        }

        Log::warning('Upload rejected: content does not match declared type', [
            'extension' => $extension,
            'head_bytes' => bin2hex(substr($head, 0, 16)),
        ]);

        return 'The file content does not match its declared type ('.$extension.') and was rejected.';
    }

    /**
     * Stream the file to clamd when configured. Fails closed on any daemon
     * error so a broken AV pipeline cannot silently accept uploads.
     */
    protected function runAntivirus(UploadedFile $file): ?string
    {
        $host = config('uploads.scan.clamav.host');

        if (empty($host)) {
            return null;
        }

        $port = (int) config('uploads.scan.clamav.port', 3310);
        $timeout = (int) config('uploads.scan.clamav.timeout', 30);
        $maxStream = (int) config('uploads.scan.clamav.max_stream_bytes', 25 * 1024 * 1024);

        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, $timeout);

        if ($socket === false) {
            Log::critical('ClamAV unreachable while scanning upload — failing closed', [
                'host' => $host,
                'port' => $port,
                'error' => $errorMessage,
                'code' => $errorCode,
            ]);

            throw new RuntimeException(
                'The antivirus service is unavailable, so the upload cannot be accepted right now. Please try again later.'
            );
        }

        try {
            fwrite($socket, "zINSTREAM\0");

            $handle = @fopen($file->getRealPath(), 'rb');
            if ($handle === false) {
                throw new RuntimeException('Unable to read upload for antivirus scanning.');
            }

            try {
                $streamed = 0;
                while (! feof($handle) && $streamed < $maxStream) {
                    $chunk = (string) fread($handle, 65536);
                    if ($chunk === '') {
                        break;
                    }
                    $streamed += strlen($chunk);
                    fwrite($socket, pack('N', strlen($chunk)).$chunk);
                }
                fwrite($socket, pack('N', 0));

                $response = '';
                while (! feof($socket)) {
                    $response .= (string) fgets($socket, 4096);
                }
            } finally {
                fclose($handle);
            }

            $response = trim($response);

            if (preg_match('/^stream:\s*OK/i', $response)) {
                return null;
            }

            if (preg_match('/^stream:\s*(.+?)\s+FOUND\b/i', $response, $matches)) {
                Log::warning('Antivirus detected a threat in an upload', [
                    'original_name' => $file->getClientOriginalName(),
                    'signature' => $matches[1],
                ]);

                return 'The file failed the antivirus scan and was rejected.';
            }

            Log::critical('ClamAV returned an unexpected response — failing closed', [
                'response' => $response,
            ]);

            throw new RuntimeException(
                'The antivirus scan could not be completed, so the upload was not accepted. Please try again later.'
            );
        } finally {
            fclose($socket);
        }
    }
}
