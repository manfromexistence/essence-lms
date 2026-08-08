# Passwords & Email (Login Credentials)

This document describes how login credentials are created, approved and
communicated in the LMS. It is kept in sync with `AdmissionController`,
`Admin\StudentController`, `Admin\TeacherController` and `AuthController`.

## Who sets the password?

The **creator of the account** (either the public applicant themselves on the
admission form, or an admin on the *Add Student* / *Add Teacher* page) **must**
supply the login password directly. There is no blank/random-password path:

- The password is a **required** field on every account-creation form.
- The stored user always has `must_change_password = false` — they log in with
  **email + the password that was set** as soon as their account is active.
- No random throwaway password is ever generated, and no password-reset link is
  emailed on approval.

## Where the password is collected

| Form | File | Fields |
|------|------|--------|
| Public admission form | `resources/views/admission/create.blade.php` | `password` + `password_confirmation` (Login credentials section) |
| Admin add-student | `resources/views/dashboard/students/create.blade.php` | `password` + `password_confirmation` (Student Information section) |
| Admin add-teacher | `resources/views/dashboard/teachers/create.blade.php` | `password` + `password_confirmation` (Personal Information section) |

Every password field renders a **colored in-box strength meter** (weak → fair →
good → strong) via `resources/views/components/ui/password-input.blade.php`.

Validation rules:

```
// StoreStudentRequest (admission + add-student)
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
| `approved`  | `active`   | `true`  | Can log in immediately with the password set at creation. "Admitted" badge everywhere. |
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
4. An **Admission Approved** email is sent via Brevo (`BrevoEmailService::send`).
   No reset link is sent — the student already has a known password.

### On reject (`admission_status = rejected`)

1. `students.admission_status = 'rejected'`
2. `students.status = 'rejected'`
3. `users.is_active = false`  (login stays blocked)
4. No email is sent.

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

## Password recovery (forgot-password / reset link)

Only **active** accounts receive a reset link (`AuthController@sendResetLink`
filters on `is_active = true`), so:

- A pending/rejected student never gets a link they cannot use, and
- the endpoint never discloses whether an email has an account (same message
  is shown either way).

`AuthController@resetPassword` also refuses to reset for inactive accounts
*before* consuming the token. Reset links use Laravel's built-in `Password`
broker, which works with any `MAIL_MAILER` (local `log`/`array`, or SMTP/Brevo
in production).

## Legacy note (`must_change_password`)

Pre-existing accounts created before the "password required" rule may still have
`must_change_password = true`. Those users are redirected to `/change-password`
after login until they set a new password. New accounts are always created with
`must_change_password = false`.

## Admin reminder

- On the *Add Student* / *Add Teacher* forms, **always set a password** — the
  student/teacher logs in with it immediately once their account is active.
- The public admission form requires the applicant to set their own password;
  it is delivered in person on the login page after approval.

## Test coverage

- `tests/Feature/StudentLoginCredentialFlowTest.php`
  - pending student cannot log in
  - approve activates account **without** a reset link
  - reject keeps inactive + no reset link
  - applicant sets own password → logs in directly after approval
- `tests/Feature/AdmissionStatusConsistencyTest.php`
  - status is identical on All Students, Admission Applications and Batch Assignment
  - approve/reject/batch-assign sync `admission_status`, `status` and `is_active`
- `tests/Feature/AccountSecurityTest.php`
  - inactive account cannot sign in
  - `must_change_password` legacy accounts are forced to change password
  - forgot-password does not disclose account existence
