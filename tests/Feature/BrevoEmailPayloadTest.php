<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Services\BrevoEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression tests for the "credentials email never arrives" bug.
 *
 * The original payload always included `"name": null` for recipients without a
 * name (applicants are stored as `name` OR `name_bn`, and the bulk-mail path
 * passes a null name for custom addresses). Brevo accepted that request with a
 * 201 and a messageId, the code logged the send as `sent`, and the mail was
 * then dropped in transit — so `email_logs` showed success while the student's
 * inbox stayed empty. Every one of these tests fails against the old payload.
 */
class BrevoEmailPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function configureBrevo(): void
    {
        config([
            'mail.from.address' => 'sender@example.com',
        ]);
        putenv('BREVO_API_KEY=test-api-key');
        $_ENV['BREVO_API_KEY'] = 'test-api-key';
        $_SERVER['BREVO_API_KEY'] = 'test-api-key';
        putenv('BREVO_SENDER_EMAIL=sender@example.com');
        $_ENV['BREVO_SENDER_EMAIL'] = 'sender@example.com';
        $_SERVER['BREVO_SENDER_EMAIL'] = 'sender@example.com';
        putenv('BREVO_SENDER_NAME=Dhaka IT Institute');
        $_ENV['BREVO_SENDER_NAME'] = 'Dhaka IT Institute';
    }

    public function test_recipient_name_is_omitted_entirely_when_the_sender_has_no_name(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        (new BrevoEmailService())->send('student@example.com', 'Subject', '<p>Hi</p>');

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            // The name key must be absent, not present-and-null.
            $this->assertArrayNotHasKey('name', $payload['to'][0]);
            $this->assertSame('student@example.com', $payload['to'][0]['email']);

            return true;
        });
    }

    public function test_recipient_name_is_included_when_one_is_known(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        (new BrevoEmailService())->send('student@example.com', 'Subject', '<p>Hi</p>', [
            'name' => 'Rahim Uddin',
        ]);

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            $this->assertSame('Rahim Uddin', $payload['to'][0]['name']);

            return true;
        });
    }

    public function test_blank_and_whitespace_names_are_omitted(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        (new BrevoEmailService())->send('student@example.com', 'Subject', '<p>Hi</p>', ['name' => '   ']);

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            $this->assertArrayNotHasKey('name', $payload['to'][0]);

            return true;
        });
    }

    public function test_payload_carries_sender_reply_to_and_subject(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        (new BrevoEmailService())->send('student@example.com', 'Your Login Credentials', '<p>Hi</p>');

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            $this->assertSame('sender@example.com', $payload['sender']['email']);
            $this->assertSame('Dhaka IT Institute', $payload['sender']['name']);
            $this->assertSame('sender@example.com', $payload['replyTo']['email']);
            $this->assertSame('Your Login Credentials', $payload['subject']);
            $this->assertSame('<p>Hi</p>', $payload['htmlContent']);

            return true;
        });
    }

    public function test_undeliverable_address_fails_without_calling_brevo(): void
    {
        $this->configureBrevo();
        Http::fake();

        $service = new BrevoEmailService();

        foreach (['', '   ', 'not-an-email', 'no-tld@localhost', 'trailing@example.com.', 'double@ex..com'] as $bad) {
            $log = $service->send($bad, 'Subject', '<p>Hi</p>');

            $this->assertSame('failed', $log->status, "Expected [{$bad}] to be rejected.");
            $this->assertStringContainsString('valid email', (string) $log->error_message);
        }

        Http::assertNothingSent();
    }

    public function test_recipient_whitespace_is_trimmed_before_sending(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        $log = (new BrevoEmailService())->send('  student@example.com  ', 'Subject', '<p>Hi</p>');

        $this->assertSame('sent', $log->status);
        $this->assertSame('student@example.com', $log->to);
    }

    public function test_brevo_rejection_is_recorded_as_failed_with_the_api_body(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Sender not valid'], 400)]);

        $log = (new BrevoEmailService())->send('student@example.com', 'Subject', '<p>Hi</p>');

        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('Sender not valid', (string) $log->error_message);
        $this->assertNull($log->sent_at);
    }

    public function test_deliverability_check_accepts_real_world_addresses(): void
    {
        $service = new BrevoEmailService();

        foreach ([
            'student@example.com',
            'first.last+tag@sub.example.co.uk',
            'rahim.uddin_92@yahoo.com',
            'a@b.co',
        ] as $good) {
            $this->assertTrue($service->isDeliverableAddress($good), "Expected [{$good}] to be accepted.");
        }

        foreach (['', '   ', 'plain', '@example.com', 'student@', 'a@b', 'a@b.', 'a@b..c'] as $bad) {
            $this->assertFalse($service->isDeliverableAddress($bad), "Expected [{$bad}] to be rejected.");
        }
    }

    public function test_diagnose_reports_a_missing_key_instead_of_throwing(): void
    {
        putenv('BREVO_API_KEY');
        unset($_ENV['BREVO_API_KEY'], $_SERVER['BREVO_API_KEY']);
        config(['mail.from.address' => null]);
        putenv('BREVO_SENDER_EMAIL');
        unset($_ENV['BREVO_SENDER_EMAIL'], $_SERVER['BREVO_SENDER_EMAIL']);
        // config/mail.php ships a demo fallback key; clear it to reach the
        // genuinely-unconfigured state.
        config(['mail.brevo.api_key' => null, 'mail.brevo.sender_email' => null]);
        Http::fake();

        $result = (new BrevoEmailService())->diagnose();

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['api_key_present']);
        $this->assertStringContainsString('BREVO_API_KEY', $result['message']);
        Http::assertNothingSent();
    }

    public function test_diagnose_detects_an_unverified_sender(): void
    {
        $this->configureBrevo();
        Http::fake([
            'api.brevo.com/v3/account' => Http::response(['email' => 'owner@example.com'], 200),
            'api.brevo.com/v3/senders' => Http::response([
                'senders' => [['email' => 'someone-else@example.com']],
            ], 200),
        ]);

        $result = (new BrevoEmailService())->diagnose();

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['sender_verified']);
        $this->assertStringContainsString('not in the verified senders list', $result['message']);
    }

    public function test_diagnose_passes_for_a_healthy_account(): void
    {
        $this->configureBrevo();
        Http::fake([
            'api.brevo.com/v3/account' => Http::response([
                'email' => 'owner@example.com',
                'plan' => ['type' => 'free', 'credits' => 300],
            ], 200),
            'api.brevo.com/v3/senders' => Http::response([
                'senders' => [['email' => 'SENDER@example.com']],
            ], 200),
        ]);

        $result = (new BrevoEmailService())->diagnose();

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['sender_verified']);
        $this->assertSame('sender@example.com', $result['sender_email']);
    }

    public function test_email_log_rows_are_still_written_for_each_attempt(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        (new BrevoEmailService())->send('one@example.com', 'Subject', '<p>Hi</p>');
        (new BrevoEmailService())->send('bad-address', 'Subject', '<p>Hi</p>');

        $this->assertSame(1, EmailLog::where('status', 'sent')->count());
        $this->assertSame(1, EmailLog::where('status', 'failed')->count());
    }
}
