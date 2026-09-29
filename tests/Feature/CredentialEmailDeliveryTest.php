<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end coverage for "the credentials email never reaches the student".
 *
 * StudentLoginCredentialFlowTest asserts that a job is *dispatched*. These
 * tests go one step further and assert that the HTTP request Brevo would
 * actually receive is well formed, that the failure is visible in email_logs,
 * and that an admin can recover without editing the database.
 */
class CredentialEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
    }

    private function configureBrevo(string $sender = 'sender@example.com'): void
    {
        config(['mail.from.address' => $sender]);
        foreach ([
            'BREVO_API_KEY' => 'test-api-key',
            'BREVO_SENDER_EMAIL' => $sender,
            'BREVO_SENDER_NAME' => 'Dhaka IT Institute',
        ] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function pendingStudent(string $email = 'applicant@example.com'): Student
    {
        $user = User::factory()->create([
            'name' => 'Applicant',
            'email' => $email,
            'password' => Hash::make('placeholder'),
            'is_active' => false,
            'must_change_password' => false,
        ]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']));

        return Student::factory()->create([
            'user_id' => $user->id,
            'name_bn' => 'Applicant',
            'phone' => '01711000001',
            'admission_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    public function test_approving_a_student_sends_a_well_formed_brevo_request(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<ok@brevo>'], 201)]);

        $student = $this->pendingStudent('approved.applicant@example.com');

        $this->actingAs($this->admin())
            ->post("/dashboard/students/{$student->id}/admission-status", ['admission_status' => 'approved'])
            ->assertRedirect();

        // With QUEUE_CONNECTION=sync the job runs inline, so the HTTP call has
        // already happened by the time the request returns.
        Http::assertSent(function ($request) {
            $payload = $request->data();

            $this->assertSame('https://api.brevo.com/v3/smtp/email', $request->url());
            $this->assertSame('approved.applicant@example.com', $payload['to'][0]['email']);
            $this->assertStringContainsString('Login Credentials', $payload['subject']);

            // The credential mail must be actionable: it names the login email
            // and carries a password the student can actually use.
            $this->assertStringContainsString('approved.applicant@example.com', $payload['htmlContent']);
            $this->assertStringContainsString('change your password', $payload['htmlContent']);

            return true;
        });

        $this->assertSame(1, EmailLog::where('status', 'sent')->count());
    }

    public function test_the_email_body_contains_the_generated_password_shown_in_the_log(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<ok@brevo>'], 201)]);

        $student = $this->pendingStudent('body.check@example.com');
        $password = app(\App\Services\StudentCredentialService::class)->issueFor($student);

        $this->assertNotNull($password);

        Http::assertSent(function ($request) use ($password) {
            // The whole point of the email: the student can actually read the
            // password and sign in with it.
            $this->assertStringContainsString($password, $request->data()['htmlContent']);

            return true;
        });
    }

    public function test_a_rejected_send_is_recorded_as_failed_so_it_is_never_invisible(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Sender not valid'], 400)]);

        $student = $this->pendingStudent('rejected.send@example.com');
        app(\App\Services\StudentCredentialService::class)->issueFor($student);

        $log = EmailLog::where('to', 'rejected.send@example.com')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('Sender not valid', (string) $log->error_message);
    }

    public function test_student_without_a_valid_email_gets_an_explicit_failure_row(): void
    {
        $this->configureBrevo();
        Http::fake();

        $user = User::factory()->create([
            'name' => 'No Email',
            'email' => 'broken-address',
            'password' => Hash::make('placeholder'),
            'is_active' => false,
        ]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']));
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'name_bn' => 'No Email',
            'phone' => '01711000009',
            'admission_status' => 'pending',
            'status' => 'pending',
        ]);

        app(\App\Services\StudentCredentialService::class)->issueFor($student);

        $log = EmailLog::where('to', 'broken-address')->latest('id')->first();

        $this->assertNotNull($log, 'a failed row must exist so the gap is visible to admins');
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('no valid email address', (string) $log->error_message);
        Http::assertNothingSent();
    }

    public function test_admin_can_resend_credentials_for_a_student(): void
    {
        $this->configureBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<ok@brevo>'], 201)]);

        $student = $this->pendingStudent('resend.me@example.com');

        $response = $this->actingAs($this->admin())
            ->post("/dashboard/email/students/{$student->id}/resend-credentials");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->user->refresh();
        $this->assertTrue((bool) $student->user->is_active);
        $this->assertTrue((bool) $student->user->must_change_password);

        Http::assertSent(fn ($request) => $request->data()['to'][0]['email'] === 'resend.me@example.com');
    }

    public function test_resend_credentials_refuses_an_undeliverable_address(): void
    {
        $this->configureBrevo();
        Http::fake();

        $user = User::factory()->create([
            'name' => 'Bad Email',
            'email' => 'not-an-email',
            'password' => Hash::make('placeholder'),
            'is_active' => false,
        ]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']));
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'name_bn' => 'Bad Email',
            'phone' => '01711000010',
            'admission_status' => 'pending',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin())
            ->post("/dashboard/email/students/{$student->id}/resend-credentials");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_email_dashboard_exposes_connection_health(): void
    {
        $this->configureBrevo();

        $response = $this->actingAs($this->admin())->get('/dashboard/email');

        $response->assertOk();
        $response->assertSee('Brevo Connection');
        $response->assertSee('sender@example.com');
    }

    public function test_diagnose_endpoint_is_available_to_admins(): void
    {
        $this->configureBrevo();
        Http::fake([
            'api.brevo.com/v3/account' => Http::response(['email' => 'owner@example.com'], 200),
            'api.brevo.com/v3/senders' => Http::response([
                'senders' => [['email' => 'sender@example.com']],
            ], 200),
        ]);

        $this->actingAs($this->admin())
            ->getJson('/dashboard/email/diagnose')
            ->assertOk()
            ->assertJson(['ok' => true, 'sender_verified' => true]);
    }

    public function test_diagnose_endpoint_is_not_public(): void
    {
        $this->get('/dashboard/email/diagnose')->assertStatus(302);
    }
}
