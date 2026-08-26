<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Content Scanning
    |--------------------------------------------------------------------------
    |
    | Every user-uploaded document and payment proof is scanned before it is
    | stored: magic-byte signature validation, embedded-threat pattern checks
    | (PHP tags, active PDF content), and an optional ClamAV antivirus scan.
    |
    */

    'scan' => [
        'enabled' => env('UPLOAD_SCAN_ENABLED', true),

        // Number of bytes inspected for embedded-threat patterns.
        'max_pattern_bytes' => (int) env('UPLOAD_SCAN_MAX_BYTES', 32 * 1024 * 1024),

        /*
        |----------------------------------------------------------------------
        | ClamAV (optional antivirus daemon)
        |----------------------------------------------------------------------
        |
        | When CLAMAV_HOST is set (e.g. "clamav" or "127.0.0.1"), uploads are
        | streamed to clamd over the INSTREAM protocol. If the daemon is
        | configured but unreachable, uploads FAIL CLOSED so a broken
        | antivirus pipeline can never silently accept files.
        |
        */

        'clamav' => [
            'host' => env('CLAMAV_HOST'),
            'port' => (int) env('CLAMAV_PORT', 3310),
            'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
            'max_stream_bytes' => (int) env('CLAMAV_MAX_STREAM_BYTES', 25 * 1024 * 1024),
        ],
    ],
];
