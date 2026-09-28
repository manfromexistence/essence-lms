<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\BrevoEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $log = (new BrevoEmailService())->send('someone@example.com', 'Subject', '<p>Hi</p>');

        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('not configured', (string) $log->error_message);
    }
}
