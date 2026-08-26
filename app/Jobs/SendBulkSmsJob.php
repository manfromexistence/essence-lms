<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued bulk SMS campaign so large recipient lists never block the HTTP
 * request. Delivery results land in sms_logs for review in the dashboard.
 */
class SendBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        protected array $recipients,
        protected string $message,
        protected array $metadata = [],
    ) {
        //
    }

    public function handle(SmsService $smsService): void
    {
        $smsService->sendBulk($this->recipients, $this->message, $this->metadata);
    }
}
