# Passwords & Email (Login Credentials)

This document describes how login credentials are created, approved and
communicated in the LMS. It is kept in sync with `AdmissionController`,
`Admin\StudentController`, `Admin\TeacherController`, `AuthController` and
`StudentCredentialService`.

## Who sets the password?

It depends on how the account comes into existence.

| Account created by | Password source |
|---|---|
| Public admission form applicant | **Nobody.** No password field exists. The system generates one and emails it on approval. |
| Admin, on the *Add Student* page | The admin types it, and the student logs in with it directly. |
| Admin, on the *Add Teacher* page | The admin types it, likewise. |

The public applicant deliberately has no password field. An applicant who has not
yet paid, or whose application the office has not reviewed, must not be able to
reach an account, and must not be sitting on credentials before anyone has
looked at their application. The admission form says so in place of the field
(*"No password needed to apply"* — `resources/views/admission/create.blade.php`).

Internally the applicant's account is created with an unguessable 64-character
placeholder hash (`Str::random(64)`, hashed). Nobody knows it, and
`is_active = false` means it can never be used. It exists only so the `users`
row is valid.

## Where a password is collected

| Form | File | Fields |
|------|------|--------|
| Public admission form | `resources/views/admission/create.blade.php` | *none* — replaced by an explanatory panel |
| Admin add-student | `resources/views/dashboard/students/create.blade.php` | `password` + `password_confirmation` (Student Information section) |
| Admin add-teacher | `resources/views/dashboard/teachers/create.blade.php` | `password` + `password_confirmation` (Personal Information section) |

Every admin password field renders a **colored in-box strength meter** (weak →
fair → good → strong) via
`resources/views/components/ui/password-input.blade.php`.

Validation rules:

```
// StoreStudentRequest — admin add-student only. The public admission route
// (admission.store) makes both fields nullable and ignores anything sent.
'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
'password_confirmation' => ['required', 'same:password'],

// TeacherController@store
'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
'password_confirmation' => ['required', 'same:password'],
```

> `mixedCase` as a **string** rule is not a valid validator in this Laravel
> version (it throws `BadMethodCallException: validateMixedCase`). Always use
> the `Illuminate\Validation\Rules\Password` fluent rule object, which **does**
> expose `->mixedCase()`.

## The lifecycle (single source of truth: `admission_status`)

`students.admission_status` is one of `pending | approved | rejected` (defaults
to `pending`). `students.status` is a legacy/mirror column kept in sync:

| admission_status | status column | user.is_active | what the student sees |
|------------------|--------------|----------------|------------------------|
| `pending`   | `pending`  | `false` | Cannot log in. "Pending" badge everywhere. |
| `approved`  | `active`   | `true`  | Logs in with the **emailed** password and is forced to change it. "Admitted" badge everywhere. |
| `rejected`  | `rejected` | `false` | Cannot log in. "Rejected" badge everywhere. |

Login is blocked on `users.is_active` alone (`AuthController@login` passes
`is_active = true` into `Auth::attempt`). When the credentials are correct but
the account is inactive, the student gets a message that explains *why* —
pending, rejected, or finalising — rather than a generic failure.

## Who can approve?

Admins via the **Admission Applications** page
(`/dashboard/students/admission-form`). Each row has **Approve** and **Reject**
buttons that POST to:

```
POST /dashboard/students/{student}/admission-status   (named dashboard.students.admission-status)
```

This is handled by `StudentController@updateAdmissionStatus`.

### On approve (`admission_status = approved`)

1. `students.admission_status = 'approved'`
2. `students.status = 'active'`
3. `users.is_active = true` (enables login)
4. `StudentCredentialService@issueFor` runs, which:
   - generates a 16-character password (`Str::password(16, symbols: false)`),
   - sets `password` **and** `must_change_password = true`,
   - emails the login details via Brevo (`SendEmailJob` → `BrevoEmailService`),
   - records the outcome in `email_logs` so a failed send is visible rather than
     silently swallowed.

Approval is therefore the moment an applicant receives working credentials. It is
not a moment at which they already had some.

### On reject (`admission_status = rejected`)

1. `students.admission_status = 'rejected'`
2. `students.status = 'rejected'`
3. `users.is_active = false` (login stays blocked)
4. No email is sent.

## Every path that approves must also issue credentials

Approving through **any** route activates the account, so approving through any
route must hand out a password. All four write paths do this:

| Path | Issues credentials? |
|---|---|
| `AdmissionController@store` | n/a — this creates the *pending* application |
| `StudentController@updateAdmissionStatus` (Approve/Reject buttons) | yes |
| `StudentController@updateBatchAssignment` (single batch assign) | yes, only on the transition to approved |
| `StudentController@bulkBatchAssignment` (bulk batch assign) | yes, per student transitioning to approved |
| `PaymentController@approve` (payment verified) | yes, only on the transition to approved |

The batch-assign and payment paths deliberately issue credentials **only when the
status actually changes**. Approving a second payment, or re-assigning a batch,
must not silently reset a password the student has since changed.

## Re-sending credentials

If the approval email never arrived, an admin can reissue from the same
**Admission Applications** page — every approved row has a **Resend login**
button:

```
POST /dashboard/email/students/{student}/resend-credentials   (named dashboard.email.resend-credentials)
```

Handled by `Admin\EmailController@resendCredentials`. It regenerates the
password and reports honestly whether the email actually went out, based on the
resulting `email_logs` row. **This invalidates the student's current password**,
so the view asks for confirmation before submitting.

## How the sync is kept consistent (every write path)

All of these set `admission_status` **and** `status` together, and toggle
`users.is_active`:

- `AdmissionController@store` (public admission)
- `StudentController@store` (admin add-student)
- `StudentController@updateAdmissionStatus` (Approve/Reject buttons)
- `StudentController@updateBatchAssignment` (single batch assign)
- `StudentController@bulkBatchAssignment` (bulk batch assign)
- `StudentController@update` (edit form — when `batch_id` or `admission_status` changes)
- `PaymentController@approve` (payment approved → enrolled → student approved + active)

## Forced password change

`must_change_password` is set for **every** student who receives generated
credentials. `RequirePasswordChange` middleware is appended to the whole `web`
group, so such a user is redirected to `/change-password` after login and cannot
reach any other page until they set their own password.

Admins who create an account directly are expected to choose a password that
satisfies the strength rule, so their accounts start with
`must_change_password = false`.

## Password recovery (forgot-password / reset link)

Only **active** accounts receive a reset link (`AuthController@sendResetLink`
filters on `is_active = true`), so:

- A pending/rejected student never gets a link they cannot use, and
- the endpoint never discloses whether an email has an account (same message
  is shown either way).

`AuthController@resetPassword` also refuses to reset for inactive accounts
*before* consuming the token, and clears `must_change_password` on success.
Reset links use Laravel's built-in `Password` broker, which works with any
`MAIL_MAILER` (local `log`/`array`, or SMTP in production).

> Note: reset mail goes through Laravel's mailer, not Brevo. With
> `MAIL_MAILER=log` the link is written to `storage/logs/laravel.log` instead of
> being emailed.

## Admin reminder

- On the *Add Student* / *Add Teacher* forms, set a strong password — that
  student or teacher logs in with it directly once their account is active.
- The public admission form collects no password. Approving an application is
  what triggers the generated-password email, and **Resend login** reissues it.

## Test coverage

- `tests/Feature/StudentLoginCredentialFlowTest.php`
  - pending student cannot log in
  - approve activates the account and sends **no** reset link
  - reject keeps the account inactive + no reset link
  - public admission ignores any applicant-supplied password
  - approval generates and emails credentials, then forces a change
  - the generated password works for the first login and is then replaced
- `tests/Feature/CredentialEmailDeliveryTest.php`
  - the Brevo payload carries the credentials
  - a failed send is recorded in `email_logs` and visible to an admin
- `tests/Feature/AdmissionStatusConsistencyTest.php`
  - status is identical on All Students, Admission Applications and Batch Assignment
  - approve/reject/batch-assign sync `admission_status`, `status` and `is_active`
- `tests/Feature/AccountSecurityTest.php`
  - inactive account cannot sign in
  - `must_change_password` accounts are forced to change password
  - forgot-password does not disclose account existence