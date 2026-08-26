<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\BrevoEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued transactional email via Brevo so web requests never block on the
 * third-party API. Failures are logged and surfaced to admins as a
 * dashboard notification.
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        protected string $to,
        protected string $subject,
        protected string $html,
        protected array $metadata = [],
    ) {
        //
    }

    public function handle(BrevoEmailService $emailService): void
    {
        $emailService->send($this->to, $this->subject, $this->html, $this->metadata);
    }

    /**
     * Alert admins when the email could not be delivered after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Queued email permanently failed', [
            'to' => $this->to,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);

        self::notifyAdmins(
            'Queued email failed',
            "Delivery to {$this->to} for \"{$this->subject}\" failed: {$exception->getMessage()}",
            ['subject' => $this->subject, 'to' => $this->to]
        );
    }

    protected static function notifyAdmins(string $title, string $message, array $data = []): void
    {
        try {
            User::whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'super-admin']))
                ->pluck('id')
                ->each(fn ($userId) => Notification::create([
                    'user_id' => $userId,
                    'user_type' => 'admin',
                    'type' => 'queue_job_failed',
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]));
        } catch (\Throwable $notificationException) {
            Log::error('Failed to create queue-failure notification', [
                'error' => $notificationException->getMessage(),
            ]);
        }
    }
}
