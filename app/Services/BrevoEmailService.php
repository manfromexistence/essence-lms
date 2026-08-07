<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoEmailService
{
    protected ?string $apiKey;
    protected ?string $senderEmail;
    protected ?string $senderName;

    public function __construct()
    {
        $this->apiKey = Setting::where('key', 'brevo_api_key')->value('value');
        $this->senderEmail = Setting::where('key', 'brevo_sender_email')->value('value') ?: 'ajju40959@gmail.com';
        $this->senderName = Setting::where('key', 'brevo_sender_name')->value('value') ?: 'Dhaka IT Institute';
    }

    /**
     * Send a single email via Brevo transactional API.
     *
     * @param string $to Recipient email
     * @param string $subject Subject line
     * @param string $html HTML body
     * @param array $metadata Optional metadata (type, related model)
     * @return EmailLog
     */
    public function send(string $to, string $subject, string $html, array $metadata = []): EmailLog
    {
        $type = $metadata['type'] ?? 'general';
        $related = $metadata['related'] ?? null;

        // The email_logs table only allows 'sent' or 'failed' (no 'pending'),
        // so create the log with its final state after the API attempt.
        if (!$this->apiKey) {
            $log = EmailLog::create([
                'to' => $to,
                'subject' => $subject,
                'template_type' => $type,
                'status' => 'failed',
                'error_message' => 'Brevo API key is not configured in Settings.',
            ]);
            if ($related) {
                $log->update([
                    'user_id' => $related->user_id ?? $related->id,
                    'user_type' => get_class($related),
                ]);
            }
            return $log->fresh();
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $this->senderName,
                    'email' => $this->senderEmail,
                ],
                'to' => [
                    ['email' => $to, 'name' => $metadata['name'] ?? null],
                ],
                'subject' => $subject,
                'htmlContent' => $html,
            ]);

            if ($response->successful()) {
                $log = EmailLog::create([
                    'to' => $to,
                    'subject' => $subject,
                    'template_type' => $type,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                if ($related) {
                    $log->update([
                        'user_id' => $related->user_id ?? $related->id,
                        'user_type' => get_class($related),
                    ]);
                }
                return $log->fresh();
            }

            $log = EmailLog::create([
                'to' => $to,
                'subject' => $subject,
                'template_type' => $type,
                'status' => 'failed',
                'error_message' => substr('Brevo API error: ' . $response->body(), 0, 2000),
            ]);
            if ($related) {
                $log->update([
                    'user_id' => $related->user_id ?? $related->id,
                    'user_type' => get_class($related),
                ]);
            }
            return $log->fresh();
        } catch (\Exception $e) {
            Log::error('Brevo email send failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            $log = EmailLog::create([
                'to' => $to,
                'subject' => $subject,
                'template_type' => $type,
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 2000),
            ]);
            if ($related) {
                $log->update([
                    'user_id' => $related->user_id ?? $related->id,
                    'user_type' => get_class($related),
                ]);
            }
            return $log->fresh();
        }
    }

    /**
     * Send bulk emails to multiple recipients.
     *
     * @param array $recipients List of ['email' => ..., 'name' => ..., 'related' => ...]
     * @param string $subject
     * @param string $html
     * @param array $metadata
     * @return array ['total', 'successful', 'failed']
     */
    public function sendBulk(array $recipients, string $subject, string $html, array $metadata = []): array
    {
        $successful = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            if (!$email) {
                $failed++;
                continue;
            }

            $log = $this->send($email, $subject, $html, array_merge($metadata, [
                'name' => $recipient['name'] ?? null,
                'related' => $recipient['related'] ?? null,
            ]));

            if ($log->isSent()) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => count($recipients),
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Get recent email logs.
     */
    public function recentLogs(int $limit = 20)
    {
        return EmailLog::orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get email stats for the dashboard.
     */
    public function stats(): array
    {
        return [
            'total_sent' => EmailLog::where('status', 'sent')->count(),
            'total_failed' => EmailLog::where('status', 'failed')->count(),
            'total_pending' => 0,
            'today_sent' => EmailLog::where('status', 'sent')
                ->whereDate('created_at', today())->count(),
        ];
    }
}
