<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Host
    |--------------------------------------------------------------------------
    |
    | The institute hosts images, course video, exam screenshots and payment
    | proofs on Catbox rather than on the web server's own disk, because a
    | shared host cannot carry lecture video at a workable size.
    |
    | Two consequences are worth stating plainly:
    |
    |   1. Every hosted object is world-readable. A URL that leaks — in a shared
    |      browser history, a forwarded email, a referrer header — stays public
    |      forever. There is no signed-URL equivalent on this host.
    |
    |   2. Deletion is not possible. Catbox only removes files uploaded under an
    |      account key, and this deployment is deliberately keyless, so removing
    |      a record here stops the application pointing at the object and leaves
    |      the remote copy in place.
    |
    */

    'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 200 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Upload Timeout
    |--------------------------------------------------------------------------
    |
    | Course video is the worst case. These bounds must stay generous enough for
    | a full-size lecture to finish, because Catbox is not resumable and a
    | timeout halfway through loses the whole transfer.
    |
    */

    'timeout_seconds' => (int) env('MEDIA_TIMEOUT_SECONDS', 300),

];
