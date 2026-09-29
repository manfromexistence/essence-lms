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
        // DB settings first (so an admin can override from Settings), but fall
        // back to env vars. This matters on hosts with an ephemeral filesystem
        // (e.g. Render's free plan): the settings table is wiped on every
        // deploy, so a key entered through the admin UI would silently vanish
        // and every transactional email would fail until it was re-entered.
        // Env vars survive redeploys, so they are the durable source.
        $this->apiKey = Setting::getValue('brevo_api_key') ?: env('BREVO_API_KEY');
        $this->senderEmail = Setting::getValue('brevo_sender_email')
            ?: env('BREVO_SENDER_EMAIL')
            ?: config('mail.from.address');
        $this->senderName = Setting::getValue('brevo_sender_name')
            ?: env('BREVO_SENDER_NAME', 'Dhaka IT Institute');
    }

    /**
     * Send a single email via Brevo transactional API.
     *
     * @param string $to Recipient email
     * @param string $subject Subject line
     * @param string $html HTML body
     * @param array $metadata Optional metadata (type, related model, name)
     * @return EmailLog
     */
    public function send(string $to, string $subject, string $html, array $metadata = []): EmailLog
    {
        $type = $metadata['type'] ?? 'general';
        $related = $metadata['related'] ?? null;

        // Normalise/validate the recipient BEFORE calling Brevo. A malformed
        // address (stray whitespace, a full "Name <addr>" string, a missing
        // TLD) previously reached the API and came back as a POST 201 with a
        // messageId while Brevo silently dropped the mail — an invisible
        // failure that looked like success in `email_logs`.
        $to = trim($to);

        if (! $this->isDeliverableAddress($to)) {
            return $this->logFailure(
                $to,
                $subject,
                $type,
                $related,
                'Recipient address is empty or not a valid email address.'
            );
        }

        // The email_logs table only allows 'sent' or 'failed' (no 'pending'),
        // so create the log with its final state after the API attempt.
        if (! $this->apiKey || ! $this->senderEmail) {
            return $this->logFailure(
                $to,
                $subject,
                $type,
                $related,
                'Brevo API key / sender email is not configured in Settings.'
            );
        }

        try {
            $payload = [
                'sender' => [
                    'name' => $this->senderName ?: 'Dhaka IT Institute',
                    'email' => trim((string) $this->senderEmail),
                ],
                'to' => [$this->recipient($to, $metadata['name'] ?? null)],
                'subject' => $subject,
                'htmlContent' => $html,
            ];

            // Let replies from a student reach the office inbox instead of
            // bouncing off a no-reply sender that nobody monitors.
            if ($this->senderEmail) {
                $payload['replyTo'] = [
                    'email' => trim((string) $this->senderEmail),
                    'name' => $this->senderName ?: 'Dhaka IT Institute',
                ];
            }

            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->timeout(15)->post('https://api.brevo.com/v3/smtp/email', $payload);

            if ($response->successful()) {
                return $this->logSent($to, $subject, $type, $related);
            }

            Log::error('Brevo API rejected the email', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->logFailure(
                $to,
                $subject,
                $type,
                $related,
                'Brevo API error: ' . $response->body()
            );
        } catch (\Throwable $e) {
            Log::error('Brevo email send failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->logFailure($to, $subject, $type, $related, $e->getMessage());
        }
    }

    /**
     * Build a Brevo recipient object.
     *
     * The `name` key MUST be omitted when there is no name: sending
     * `"name": null` produces a payload the Brevo API tolerates but that some
     * relays drop, and the resulting log entry still says "sent".
     */
    protected function recipient(string $email, ?string $name): array
    {
        $name = is_string($name) ? trim($name) : '';

        return $name === ''
            ? ['email' => $email]
            : ['email' => $email, 'name' => $name];
    }

    /**
     * Cheap deliverability sanity check.
     *
     * `filter_var` accepts some shapes (e.g. trailing dot, very long local
     * parts) that real MTAs reject, so we additionally verify the domain has a
     * dot and a 2+ character TLD.
     */
    public function isDeliverableAddress(?string $email): bool
    {
        if (! is_string($email) || $email === '') {
            return false;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);

        if ($domain === '' || $domain === false) {
            return false;
        }

        // Reject trailing dots and require a plausible TLD.
        if (str_ends_with($domain, '.') || str_contains($domain, '..')) {
            return false;
        }

        $tld = substr(strrchr($domain, '.') ?: '', 1);

        return strlen($tld) >= 2 && ctype_alpha($tld);
    }

    protected function logSent(string $to, string $subject, string $type, $related): EmailLog
    {
        $log = EmailLog::create([
            'to' => $to,
            'subject' => $subject,
            'template_type' => $type,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->attachRelated($log, $related);

        return $log->fresh();
    }

    protected function logFailure(string $to, string $subject, string $type, $related, string $error): EmailLog
    {
        $log = EmailLog::create([
            'to' => $to,
            'subject' => $subject,
            'template_type' => $type,
            'status' => 'failed',
            'error_message' => substr($error, 0, 2000),
        ]);

        $this->attachRelated($log, $related);

        return $log->fresh();
    }

    protected function attachRelated(EmailLog $log, $related): void
    {
        if (! $related) {
            return;
        }

        try {
            $log->update([
                'user_id' => $related->user_id ?? $related->id,
                'user_type' => get_class($related),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not attach related model to email log', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Report what the service resolved, without making any network call.
     *
     * Used by the email dashboard to show the operator whether the key came
     * from the DB `settings` table or the environment — the distinction that
     * explains why a key "saved in the admin UI" disappears after a redeploy.
     */
    public function resolvedConfig(): array
    {
        $dbKey = Setting::getValue('brevo_api_key');
        $dbSender = Setting::getValue('brevo_sender_email');

        return [
            'api_key_present' => (bool) $this->apiKey,
            'api_key_preview' => $this->apiKey ? substr($this->apiKey, 0, 12) . '...' : null,
            'api_key_source' => $dbKey ? 'database (Settings)' : (env('BREVO_API_KEY') ? 'environment' : 'missing'),
            'sender_email' => $this->senderEmail,
            'sender_email_source' => $dbSender ? 'database (Settings)' : (env('BREVO_SENDER_EMAIL') ? 'environment' : 'config/mail.php'),
            'sender_name' => $this->senderName,
            'recipient_validation' => 'enabled',
        ];
    }

    /**
     * Verify the Brevo connection end to end without sending anything.
     *
     * Returns a structured result so the admin UI can tell the difference
     * between "key missing", "key rejected" and "sender not verified" — the
     * three failure modes that all look identical from a silent inbox.
     */
    public function diagnose(): array
    {
        $result = [
            'api_key_present' => (bool) $this->apiKey,
            'api_key_preview' => $this->apiKey ? substr($this->apiKey, 0, 12) . '...' : null,
            'sender_email' => $this->senderEmail,
            'sender_name' => $this->senderName,
            'account' => null,
            'senders' => [],
            'sender_verified' => false,
            'ok' => false,
            'message' => '',
        ];

        if (! $this->apiKey) {
            $result['message'] = 'No Brevo API key configured. Set BREVO_API_KEY in the host environment (render.yaml keeps it out of git with sync: false).';

            return $result;
        }

        if (! $this->senderEmail) {
            $result['message'] = 'No sender email configured. Set BREVO_SENDER_EMAIL.';

            return $result;
        }

        try {
            $account = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])->timeout(15)->get('https://api.brevo.com/v3/account');

            if (! $account->successful()) {
                $result['message'] = 'Brevo rejected the API key (HTTP ' . $account->status() . '): ' . $account->body();

                return $result;
            }

            $result['account'] = [
                'email' => $account->json('email'),
                'plan' => data_get($account->json(), 'plan.type'),
                'credits' => data_get($account->json(), 'plan.credits'),
            ];

            $senders = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])->timeout(15)->get('https://api.brevo.com/v3/senders');

            if ($senders->successful()) {
                $list = $senders->json('senders') ?? [];
                $result['senders'] = array_values(array_filter(array_map(
                    fn ($s) => $s['email'] ?? null,
                    $list
                )));
                $result['sender_verified'] = in_array(
                    strtolower(trim((string) $this->senderEmail)),
                    array_map(fn ($e) => strtolower((string) $e), $result['senders']),
                    true
                );
            }

            if (! $result['sender_verified']) {
                $result['message'] = 'API key is valid, but the sender "' . $this->senderEmail . '" is not in the verified senders list. Verify it in Brevo → Senders & IPs, or Brevo will drop the mail.';

                return $result;
            }

            $result['ok'] = true;
            $result['message'] = 'Brevo connection healthy. Sender verified, API key accepted.';

            return $result;
        } catch (\Throwable $e) {
            $result['message'] = 'Could not reach the Brevo API: ' . $e->getMessage();

            return $result;
        }
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
