# Passwords & Email (Login Credentials)

This document describes how student login credentials are created, approved and
communicated in the LMS. It is kept in sync with `AdmissionController`,
`Admin\StudentController` and `AuthController`.

## Who sets the password?

The **creator of the student record** (either the public applicant themselves on
the admission form, or an admin on the *Add Student* page) may supply the
student's login password directly.

- If a password is supplied → the student logs in with **email + that password**
  after approval. **No reset email is needed** and **Brevo is not required**.
- If the password field is left blank → a random throwaway password is generated
  internally (it is **never emailed anywhere**), and a **signed Laravel
  password-reset link** is sent on approval instead.

## Where the password is collected

| Form | File | Field |
|------|------|-------|
| Public admission form | `resources/views/admission/create.blade.php` | `password` + `password_confirmation` (Login credentials section) |
| Admin add-student | `resources/views/dashboard/students/create.blade.php` | `password` + `password_confirmation` (Student Information section) |

Validation rule (in `StoreStudentRequest`):

```
'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
'password_confirmation' => ['nullable', 'same:password'],
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
| `approved`  | `active`   | `true`  | Can log in immediately (if they know their password) or via reset link. "Admitted" badge everywhere. |
| `rejected`  | `rejected` | `false` | Cannot log in. "Rejected" badge everywhere. |

## Who can approve?

Admins via the **Admission Applications** page (`/dashboard/students/admission-form`).
Each row has **Approve** and **Reject** buttons that POST to:

```
POST /dashboard/students/{student}/admission-status   (named dashboard.students.admission-status)
```

This is handled by `StudentController@updateAdmissionStatus`.

### On approve (`admission_status = approved`)

1. `students.admission_status = 'approved'`
2. `students.status = 'active'`
3. `users.is_active = true`  (enables login — `AuthController@login` requires `is_active = true`)
4. If the student does **not** already have a usable password
   (`users.must_change_password = true`): send a **signed password-reset link**
   via Laravel's password broker. This works with **any** mail driver
   (local `log`/`array`, or SMTP/Brevo in production) — it does **not** depend
   on the Brevo service.
5. Send an **Admission Approved** email via Brevo (`BrevoEmailService::send`)
   so the student is notified.

### On reject (`admission_status = rejected`)

1. `students.admission_status = 'rejected'`
2. `students.status = 'rejected'`
3. `users.is_active = false`  (login stays blocked)
4. No reset email is sent.

## How the sync is kept consistent (every write path)

All of these now set `admission_status` **and** `status` together, and toggle
`users.is_active`:

- `AdmissionController@store` (public admission)
- `StudentController@store` (admin add-student)
- `StudentController@updateAdmissionStatus` (Approve/Reject buttons)
- `StudentController@updateBatchAssignment` (single batch assign)
- `StudentController@bulkBatchAssignment` (bulk batch assign)
- `StudentController@update` (edit form — when `batch_id` or `admission_status` changes)
- `PaymentController@approve` (payment approved → enrolled → student approved + active)

## Reset-link vs. own-password: the Brevo dependency

The **password-reset link** uses Laravel's built-in `Password` broker
(`Illuminate\Support\Facades\Password`), **not** Brevo. That means even if
Brevo is not configured a student can still receive a reset link (using
whichever `MAIL_MAILER` / SMTP is set in `.env`).

The **Admission Approved** notification email *does* use Brevo
(`BrevoEmailService`). It is wrapped in `try/catch` and failures are logged to
`storage/logs/laravel.log` — they do **not** block the approval itself.

## How students actually log in

1. Get the **Admission Approved** email (or, if you set a password for them,
   they already know it).
2. If the email contains a reset link → click it, set a password, then log in
   at `/login` with your **email + the password you just set**.
   If you set the password yourself → just log in with **email + your password**.
3. First login may redirect to `/change-password` if `users.must_change_password`
   is still `true` (only possible for the legacy/reset-link path).

## Admin reminder

- To create a student who can log in **immediately** (no email dependency),
  pick a password on the *Add Student* form and leave the student's
  `admission_status = approved` + assign a batch — or use the **Approve** button
  on the Admission Applications page.
- Leaving the password blank is fine too; the student will receive a reset link
  instead (Laravel broker, not Brevo).

## Test coverage

- `tests/Feature/StudentLoginCredentialFlowTest.php`
  - pending student cannot log in
  - approve sends reset link + activates
  - reject keeps inactive + no reset link
  - applicant sets own password → logs in directly after approval (no reset email)
- `tests/Feature/AdmissionStatusConsistencyTest.php`
  - status is identical on All Students, Admission Applications and Batch Assignment
  - approve/reject/batch-assign sync `admission_status`, `status` and `is_active`
