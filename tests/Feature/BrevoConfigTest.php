<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\BrevoEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Transactional email (approval credentials, payment notifications) is sent
 * through Brevo's HTTP API, not Laravel Mail — so MAIL_MAILER is irrelevant and
 * the API key is what matters.
 *
 * The key used to be read ONLY from the `settings` table. On a host with an
 * ephemeral filesystem (Render's free plan) that table is wiped on every deploy,
 * so a key entered in the admin UI silently disappeared and every transactional
 * email started failing. These tests pin the env fallback that makes the
 * configuration survive a redeploy.
 */
class BrevoConfigTest extends TestCase
{
    use RefreshDatabase;

    private function resolved(string $property): mixed
    {
        $service = new BrevoEmailService();
        $ref = new \ReflectionClass($service);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($service);
    }

    public function test_api_key_falls_back_to_env_when_the_db_setting_is_empty(): void
    {
        Setting::query()->delete();
        putenv('BREVO_API_KEY=env-key-123');
        $_ENV['BREVO_API_KEY'] = 'env-key-123';
        $_SERVER['BREVO_API_KEY'] = 'env-key-123';

        $this->assertSame('env-key-123', $this->resolved('apiKey'));
    }

    public function test_db_setting_wins_over_env_so_admins_can_override(): void
    {
        Setting::create(['key' => 'brevo_api_key', 'value' => 'db-key-456']);
        putenv('BREVO_API_KEY=env-key-123');
        $_ENV['BREVO_API_KEY'] = 'env-key-123';

        $this->assertSame('db-key-456', $this->resolved('apiKey'));
    }

    public function test_sender_email_falls_back_to_env(): void
    {
        Setting::query()->delete();
        putenv('BREVO_SENDER_EMAIL=sender@example.com');
        $_ENV['BREVO_SENDER_EMAIL'] = 'sender@example.com';
        $_SERVER['BREVO_SENDER_EMAIL'] = 'sender@example.com';

        $this->assertSame('sender@example.com', $this->resolved('senderEmail'));
    }

    public function test_missing_config_is_reported_as_a_failed_email_log_not_an_exception(): void
    {
        Setting::query()->delete();
        putenv('BREVO_API_KEY');
        unset($_ENV['BREVO_API_KEY'], $_SERVER['BREVO_API_KEY']);
        config(['mail.from.address' => null]);
        putenv('BREVO_SENDER_EMAIL');
        unset($_ENV['BREVO_SENDER_EMAIL'], $_SERVER['BREVO_SENDER_EMAIL']);
        // config/mail.php ships a demo fallback key so the hosted demo can send
        // mail without dashboard access. Blank it here so we are genuinely
        // testing the "nothing configured anywhere" path.
        config(['mail.brevo.api_key' => null, 'mail.brevo.sender_email' => null]);

        $log = (new BrevoEmailService())->send('someone@example.com', 'Subject', '<p>Hi</p>');

        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('not configured', (string) $log->error_message);
    }

    public function test_config_file_fallback_is_used_when_neither_db_nor_env_are_set(): void
    {
        Setting::query()->delete();
        putenv('BREVO_API_KEY');
        unset($_ENV['BREVO_API_KEY'], $_SERVER['BREVO_API_KEY']);
        putenv('BREVO_SENDER_EMAIL');
        unset($_ENV['BREVO_SENDER_EMAIL'], $_SERVER['BREVO_SENDER_EMAIL']);

        config([
            'mail.brevo.api_key' => 'config-fallback-key',
            'mail.brevo.sender_email' => 'fallback@example.com',
        ]);

        // This is the Render demo scenario: no env var, no dashboard access.
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<x@brevo>'], 201)]);
        $service = new BrevoEmailService();

        $this->assertSame('config-fallback-key', $this->resolved('apiKey'));
        $this->assertSame('fallback@example.com', $this->resolved('senderEmail'));
        $this->assertSame('config/mail.php demo fallback (NOT secret)', $service->resolvedConfig()['api_key_source']);

        $log = $service->send('student@example.com', 'Subject', '<p>Hi</p>');

        $this->assertSame('sent', $log->status);
        Http::assertSent(fn ($request) => $request->data()['sender']['email'] === 'fallback@example.com');
    }

    public function test_env_var_still_wins_over_the_config_fallback(): void
    {
        Setting::query()->delete();
        putenv('BREVO_API_KEY=env-wins-key');
        $_ENV['BREVO_API_KEY'] = 'env-wins-key';
        $_SERVER['BREVO_API_KEY'] = 'env-wins-key';
        config(['mail.brevo.api_key' => 'config-fallback-key']);

        // Setting a proper Render env var must override the demo fallback, so
        // the two-line cleanup (rotate key + set env var) needs no code change.
        $this->assertSame('env-wins-key', $this->resolved('apiKey'));
        $this->assertSame(
            'environment variable',
            (new BrevoEmailService())->resolvedConfig()['api_key_source']
        );
    }
}
