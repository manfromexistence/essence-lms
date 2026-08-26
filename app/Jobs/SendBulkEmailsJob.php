<?php

namespace App\Jobs;

use App\Services\BrevoEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued bulk email campaign. Each recipient is sent individually through the
 * Brevo service so one bad address cannot abort the whole batch.
 */
class SendBulkEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        protected array $recipients,
        protected string $subject,
        protected string $html,
        protected array $metadata = [],
    ) {
        //
    }

    public function handle(BrevoEmailService $emailService): void
    {
        $emailService->sendBulk($this->recipients, $this->subject, $this->html, $this->metadata);
    }
}
