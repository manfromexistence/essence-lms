<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\EmailLog;
use App\Models\Student;
use Illuminate\Support\Str;

/**
 * Issues login credentials for an approved applicant.
 *
 * Flow: a public applicant submits an application WITHOUT a password. When an
 * administrator approves the admission this service generates a strong random
 * password, stores its hash on the user, flags `must_change_password` so the
 * applicant is forced to pick a private password on first login, and emails the
 * login details (email + temporary password) to the applicant.
 */
class StudentCredentialService
{
    /**
     * Letters + digits only so the password is easy to copy from an email and
     * never breaks naive "special character" form validators on login.
     * 16 characters gives ~95 bits of entropy.
     */
    private const PASSWORD_LENGTH = 16;

    /**
     * Generate, persist and email a fresh password for the student's user.
     *
     * @return string|null The plaintext password (for callers/tests), or null
     *                     when the student has no linked user.
     */
    public function issueFor(Student $student): ?string
    {
        $user = $student->user;

        if (! $user) {
            return null;
        }

        $password = $this->generatePassword();

        $user->forceFill([
            'password' => $password, // hashed by the model cast
            'is_active' => true,
            'must_change_password' => true,
        ])->save();

        $this->emailCredentials($student, $password);

        return $password;
    }

    /**
     * Build a cryptographically-random, login-safe password.
     */
    public function generatePassword(): string
    {
        // Str::password gives a mixed-case alphanumeric string when symbols are
        // disabled; no ambiguous characters are produced by the charset.
        return Str::password(self::PASSWORD_LENGTH, symbols: false, spaces: false);
    }

    /**
     * Email the login credentials. Queued so approval never blocks on Brevo.
     */
    public function emailCredentials(Student $student, string $password): void
    {
        $user = $student->user;

        if (! $user) {
            return;
        }

        $student->loadMissing('batch.course');
        $courseName = $student->batch?->course?->name
            ?? $student->course_name
            ?? 'your selected course';

        // Fail loudly if the account has no reachable address: silently
        // returning here is exactly how "the student never got an email"
        // became invisible for so long.
        $recipient = trim((string) $user->email);

        if (! app(BrevoEmailService::class)->isDeliverableAddress($recipient)) {
            \Illuminate\Support\Facades\Log::error('Cannot email credentials: student has no valid email address', [
                'user_id' => $user->id,
                'student_id' => $student->id,
                'email' => $recipient,
            ]);

            EmailLog::create([
                'to' => $recipient !== '' ? $recipient : '(missing)',
                'subject' => 'Your Login Credentials',
                'template_type' => 'admission',
                'status' => 'failed',
                'error_message' => 'Student account has no valid email address, so credentials could not be delivered.',
                'user_id' => $user->id,
                'user_type' => \App\Models\User::class,
            ]);

            return;
        }

        $loginUrl = route('login');
        $name = e($user->name ?? 'Student');
        $email = e($recipient);
        $safePassword = e($password);

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;">'
            . '<div style="background:#168536;padding:24px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h2 style="color:#fff;margin:0;">Admission Approved</h2></div>'
            . '<div style="border:1px solid #e5e7eb;border-top:0;padding:32px;border-radius:0 0 12px 12px;">'
            . '<p>Dear <strong>' . $name . '</strong>,</p>'
            . '<p>Congratulations! Your admission to <strong>' . e($courseName) . '</strong> at Dhaka IT Institute has been approved.</p>'
            . '<p>Your login credentials are below. Please sign in and change your password immediately.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:20px 0;">'
            . '<tr><td style="padding:10px 12px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Email</td>'
            . '<td style="padding:10px 12px;border:1px solid #e5e7eb;">' . $email . '</td></tr>'
            . '<tr><td style="padding:10px 12px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Password</td>'
            . '<td style="padding:10px 12px;border:1px solid #e5e7eb;font-family:monospace;font-size:15px;">' . $safePassword . '</td></tr>'
            . '</table>'
            . '<p style="text-align:center;margin:24px 0;">'
            . '<a href="' . e($loginUrl) . '" style="background:#168536;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:bold;display:inline-block;">Sign in to your account</a></p>'
            . '<p style="color:#b45309;background:#fffbeb;border:1px solid #fde68a;padding:12px;border-radius:8px;font-size:13px;">'
            . 'For your security you will be asked to set a new private password the first time you log in.</p>'
            . '<p style="margin-top:24px;color:#6b7280;font-size:13px;">Dhaka IT Institute — Let\'s Build Your Dream</p>'
            . '</div></div>';

        // Credentials are already persisted above, so a mail failure must not
        // fail the approval action. The sync queue runs inline and re-throws,
        // hence the broad Throwable catch.
        try {
            SendEmailJob::dispatch(
                $recipient,
                'Your Login Credentials — ' . $courseName,
                $html,
                [
                    'type' => 'admission',
                    'subtype' => 'credentials',
                    'name' => $user->name ?? null,
                    'related' => $student,
                ]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Credential email failed to dispatch', [
                'email' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
