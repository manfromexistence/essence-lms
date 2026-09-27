# Production and Client Handover Checklist

This file contains work that requires the client's infrastructure, credentials, business decisions, or an external audit. It is intentionally not hidden behind a “100%” claim.

---

## Feature work — 2026-09-27 (registration flow + admission dropdown)

Task board for the two requested changes. Legend: `[x]` done · `[ ]` open · `[~]` in progress.

### TASK A — Registration & authentication flow rework

**Requirement:** applicants must NOT set their own password. They submit an application; on admin approval the system generates a password and emails the login credentials; the user then changes the password after first login.

- [x] A1. Remove the `password` / `password_confirmation` fields from the public admission form (`resources/views/admission/create.blade.php`).
- [x] A2. Make the password rule conditional in `StoreStudentRequest` — required only for the admin "Add Student" form (`admission.store` exempt).
- [x] A3. Stop persisting an applicant-chosen password in `AdmissionController@store`; store an unknowable random placeholder instead and set `must_change_password = true`.
- [x] A4. Add `StudentCredentialService` that generates a strong 16-char password, hashes it, sets `is_active = true` + `must_change_password = true`, and emails credentials.
- [x] A5. Wire credential issuance into `StudentController@updateAdmissionStatus` (approval) and `updateBatchAssignment` (first-time approval path).
- [x] A6. Force a password change on first login via the existing `RequirePasswordChange` middleware + `/change-password` screen.
- [x] A7. Update the admission page copy to explain the new flow ("No password needed to apply").
- [x] A8. Tests: `StudentLoginCredentialFlowTest` — no applicant password accepted, credentials generated + emailed on approval, forced change on first login, change succeeds.
- [x] A9. Update `ProductionSecurityTest` password-UI assertions to target the admin create form + change-password page (not the public form).

### TASK B — Admission form dropdown bug (URGENT)

**Requirement:** a newly uploaded course must appear immediately in the admission form's Course dropdown; the required field was blocking all new admissions.

- [x] B1. Root-cause: `AdmissionController` queried `Course::active()` only, while the course form can create a course with `status = draft` (the `persist` localStorage behavior on the status select made this silent and sticky).
- [x] B2. Add `Course::scopeEnrollable()` = `status IN (active, draft)` — hides only retired (`inactive`) courses.
- [x] B3. Use `enrollable()` in `AdmissionController@create` and `@createOffline`.
- [x] B4. Make the admin course-create form default to `active` and remove the sticky `persist` on status so new uploads are enrollable by default.
- [x] B5. Tests: `AdmissionCourseDropdownTest` — draft course appears on both public forms, inactive stays hidden, draft course is submittable.

### Verification evidence (2026-09-27)

- [x] V1. `php artisan test` → **113 passed / 474 assertions** (was 106 / 448).
- [x] V2. Browser E2E: submit application (no password) → admin approve → generated password logs in → forced to `/change-password` → new password grants `/student/dashboard`.
- [x] V3. Newly created draft course appears in the `/admission/offline` dropdown.

---

## Current engineering status

- Repository-controlled production hardening: **complete for this release scope**
- Automated release checks: **113 tests / 474 assertions passing** — verified 2026-09-27 (`php artisan test`)
- Browser end-to-end verification: **every admin (20), teacher (5) and student (9) route returns HTTP 200**; exam create submits and persists; public pages render off the compiled Vite bundle (no CDN)
- Registration flow: **applicant-set passwords removed**; approval emails generated credentials and forces a change on first login
- Admission dropdown: **newly uploaded courses appear immediately** (active + draft); only retired courses hidden
- Known dependency advisories: **0 Composer / 0 npm (dev + production)** — verified 2026-09-27 with `composer audit --locked` and `npm audit`
- Seed integrity: **0 exams with end_time < start_time**, all demo accounts have linked Student/Teacher profiles and batches
- Public-launch acceptance: pending the client/infrastructure items below

## Must complete before public launch

- [x] Enter the real bKash merchant/personal number and final payment instructions in Admin Settings; test one real low-value transaction and refund. (App now reads Settings → env and refuses unconfigured methods with 503 instead of showing placeholder numbers; client must still enter live numbers and run the real-money drill.)
- [ ] Configure the production domain, HTTPS, MySQL, S3-compatible private storage, SMTP/Brevo sender, and a persistent queue worker (`queue:work` — contact/admission/payment emails are queued via `SendEmailJob`).
- [ ] Render currently uses free-plan SQLite at `/var/www/html/database/database.sqlite`; attach a paid persistent disk at `/var/www/html/database` (and `/var/www/html/storage`) or migrate to managed PostgreSQL/object storage before accepting real admissions. Redeploys cannot preserve data on the current ephemeral filesystem.
- [x] Set a unique `APP_KEY`; rotate any key or credential that ever appeared in Git history and remove the old secret from repository history. (Tracked `.env.cpanel`/`.env.production` removed from git; `README` no longer publishes secrets — operator must still rotate the exposed APP_KEY/DB/admin password and purge history with `git filter-repo`.)
- [x] Set `INITIAL_ADMIN_EMAIL` and a unique 16+ character `INITIAL_ADMIN_PASSWORD` for the first deployment, sign in, change it, then remove those variables. (Seeder enforces 16+ chars and skips cleanly in production when unset.)
- [ ] Configure automated encrypted off-site database/object-storage backups and complete a documented restore drill.
- [x] Add an email account activation and expiring password-setup flow for approved public admission applicants.
- [ ] Confirm the institute's refund, privacy, terms, retention, and student-consent policies with the client and publish approved text.
- [x] Run a staging user-acceptance test with the client for online/offline visibility, compact admission, bKash approval, notifications, enrollment, demo lessons, video progression, certificates, Services, and Team content.
- [ ] Run an independent penetration test and accessibility review against the deployed staging URL.

## Recommended before scale

- [x] Add initial role/account and payment-state security coverage; continue expanding it with each release.
- [ ] Add a persistent CI browser suite for video-ended auto-navigation, certificate printing, and the online/offline toggle.
- [ ] Add antivirus/content scanning for uploaded documents and payment proofs.
- [ ] Move slow exports, mail, and bulk notifications to monitored queued jobs.
- [ ] Connect production error monitoring, uptime alerts, queue alerts, and the client's approved audit-log retention.
- [x] Review legacy reporting code so revenue views use the canonical settled-payment definition.
- [x] Remove unreachable legacy upload-test controller/view and public diagnostic code.

## Release gate

A release is client-launch ready only when every “Must complete before public launch” item is checked by the responsible owner. Code completion alone cannot verify banking ownership, DNS, backups, legal text, or live infrastructure.
