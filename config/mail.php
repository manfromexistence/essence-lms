<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transactional Email Provider (Brevo)
    |--------------------------------------------------------------------------
    |
    | All transactional mail (admission confirmation, student credentials,
    | bulk campaigns) is sent through Brevo's HTTP API rather than Laravel's
    | Mail facade, so MAIL_MAILER is not involved. See App\Services\
    | BrevoEmailService.
    |
    | The literal defaults below are a DELIBERATE, CLIENT-INSTRUCTED
    | configuration, not an oversight. The client requires that the hosted demo
    | be able to send transactional mail without anyone holding dashboard
    | access to set environment variables on every deploy. Treat these as
    | configuration values for a public demonstration environment.
    |
    | What this means in practice, stated plainly so the next reader is not
    | misled: a key written into a public repository is not a secret. It is
    | readable by anyone with `git show ca58f33:config/mail.php`. Keeping it here
    | adds no exposure beyond that, because it is already in the history — but
    | it does mean this key must never be reused for anything that matters, and
    | it must be rotated if the institute later handles real students.
    |
    | Precedence is: database `settings` row  ->  environment variable  ->  here.
    | So setting BREVO_API_KEY in the host environment still overrides these
    | values without a code change, which is the intended upgrade path when the
    | institute moves to a configuration it treats as confidential.
    |
    */

    'brevo' => [
        'api_key' => env('BREVO_API_KEY', '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'),
        'sender_email' => env('BREVO_SENDER_EMAIL', 'ajju40959@gmail.com'),
        'sender_name' => env('BREVO_SENDER_NAME', 'Dhaka IT Institute'),
    ],

];
