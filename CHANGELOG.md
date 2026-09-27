# Changelog

## 2026-09-27 — HOTFIX #2: admission 500 caused by a wedged ID sequence

### Fixed — `StudentIdGenerator` could never recover from an occupied sequence

- **Symptom (live):** after the overflow hotfix landed, the *first* admission
  succeeded but **every subsequent one 500ed**, deterministically, for both
  online and offline courses:
  `RuntimeException: Unable to generate unique student ID after 10 attempts`
  (`StudentIdGenerator.php:234`).
- **Root cause:** `getNextSequence()` looked at a **single row — the last one by
  `id`** — and incremented its trailing digits. That is not a safe source of
  truth: whenever the most recent row's tail already equalled the value being
  generated, `generateUnique()`'s ten retries each recomputed the *same*
  colliding ID and the loop exhausted itself. Proven live with a temporary
  diagnostic route that dumped the student table and the exact would-be ID.
- **Fix:**
  - `getNextSequence()` now scans **every registration number issued this year**
    and returns `highest trailing sequence + 1`. The result is idempotent and
    independent of insertion order, so retries make real progress.
  - `generateUnique()` now passes an escalating `$sequenceOffset` on each retry,
    so even a stubbornly occupied sequence can never wedge an admission.
- Verified live: **6 sequential online admissions all return `302`** (they all
  500ed before), and the earlier four-course matrix now passes.
- Regression coverage: `StudentIdGeneratorTest` grew from 4 → 7 tests, including
  `test_admission_succeeds_when_the_latest_row_collides_with_the_next_sequence`
  and `test_next_sequence_uses_the_highest_number_not_the_last_row`. The latter
  **fails on the old logic** and passes on the fix.

### Added — test coverage

- Suite is now **121 tests / 501 assertions** (was 118 / 495).

## 2026-09-27 — HOTFIX: admission 500 caused by an integer overflow in the ID generator

### Fixed — `StudentIdGenerator::getNextSequence()` returned a float

- **Symptom (live):** from the **second** admission onwards, every
  `POST /admission` returned `500 Server Error` (the first ever admission
  succeeded, then all subsequent ones failed).
- **Root cause:** `getNextSequence()` computed `(int) $matches[1] + 1`. When a
  student's `registration_no` ends in a digit run longer than `PHP_INT_MAX`,
  the `(int)` cast saturates to `PHP_INT_MAX`, and `+ 1` **overflows into a
  float**. The method is declared `: int`, so PHP 8.3 throws
  `TypeError: Return value must be of type int, float returned` — 500ing the
  request. Reproduced byte-for-byte against the live container.
- **Fix:** added a private `safeInt()` helper that clamps any parsed value to
  `PHP_INT_MAX` and never lets arithmetic overflow. `getNextSequence()` now
  always returns an `int`; absurd/legacy sequence values fall back to the
  configured start number instead of crashing. `Student::creating()` also
  casts its `str_pad()` argument explicitly.
- Regression coverage: `tests/Feature/StudentIdGeneratorTest.php` (4 tests),
  including an end-to-end double-admission test and a legacy-row test. Removing
  the guard reproduces the exact live `TypeError`.

### Fixed — notification email could kill a successful admission

- **Symptom (live):** every `POST /admission` on the Render deployment returned
  `500 Server Error`, so no applicant could apply.
- **Root cause:** `QUEUE_CONNECTION=sync` on Render runs `SendEmailJob` inline
  inside the web request. `AdmissionController@store` called
  `SendEmailJob::dispatch()` unwrapped *after* the student row was already
  committed, so any mail/transport failure (the sync driver re-throws)
  propagated out of the controller and produced a 500 — even though the
  application had been saved successfully.
- **Fix:** the email dispatch is now wrapped in `try { … } catch (\Throwable)`
  in `AdmissionController@store`, `StudentCredentialService@emailCredentials`
  and `Admin\CertificateController@send`; failures are logged instead of
  failing the request. A broad `\Throwable` catch is used because the inline
  sync queue re-throws `Error`/`TypeError`, which `catch (\Exception)` misses.
- **Note:** `MAIL_MAILER=log` on Render means no real email is delivered; configure
  a Brevo API key in Settings (or a real mailer) for delivery to work.
- Regression test `test_admission_still_succeeds_when_the_notification_email_throws`
  fails with `received 500` if the guard is removed.

### Added — test coverage

- Suite is now **118 tests / 495 assertions** (was 113 / 474).

## 2026-09-27 — Registration credential flow + admission course dropdown


### Changed — applicants no longer set their own password

- The public admission form no longer collects a password. Applicants submit an
  application only ("No password needed to apply").
- On approval, a new `StudentCredentialService` generates a strong 16-character
  password, activates the account, forces a password change on first login, and
  emails the login credentials (email + temporary password).
- `StoreStudentRequest` now requires a password only for the admin "Add Student"
  form; the public `admission.store` route no longer accepts or stores one
  (an unknowable random placeholder is persisted until approval).
- Credential issuance is applied on both approval paths: admission-status update
  and first-time batch assignment.
- Verified: submit → approve → generated password logs in → redirected to
  `/change-password` → new password grants `/student/dashboard`.

### Fixed — newly uploaded courses now appear on the admission form

- Root cause: the admission form queried `Course::active()` only, while the
  course form can create a course with `status = draft`; a required Course field
  with no matching option blocked every new admission.
- Added `Course::scopeEnrollable()` (`status IN (active, draft)`); only retired
  (`inactive`) courses are hidden. Used by both public admission forms.
- The admin course-create form now defaults to `active` and no longer persists
  a stale status in `localStorage`.

### Tests

- Added `AdmissionCourseDropdownTest` (5 tests) and rewrote
  `StudentLoginCredentialFlowTest` for the new credential flow (6 tests).
- Updated `ProductionSecurityTest` password-UI assertions to the admin
  create-student form and the change-password screen.
- Suite: **113 passed / 474 assertions** (was 106 / 448).

## 2026-08-10

- Added idempotent default accounts for super-admin, admin, teacher, student, and parent roles; startup seeding now preserves changed passwords and never overwrites admissions.
- Fixed Render startup user counting to use Laravel's configured database connection instead of a hard-coded SQLite PDO.

All notable changes to the Dhaka IT Institute LMS are documented here.

## [Unreleased] - 2026-08-05

### Production hardening — 2026-08-09

- Fixed admission approval emails using an undefined course name; emails now resolve the student's enrolled course safely.
- Added a deterministic encryption key to the testing environment so CI and local test runs work without manual overrides.
- Upgraded `league/commonmark` to 2.9.0 and verified Composer security advisories are clear.
- Revalidated the complete LMS suite (65 tests, 315 assertions), production asset build, and dependency audits.

### Added

- Automatic, verifiable course-completion certificates with student access, printable certificate layout, public verification, and admin issuance/revocation management.
- Public Services and Team pages with responsive professional cards and course-business content.
- Public course demo-class pages for videos marked as free previews, including authorized streaming for uploaded preview videos.
- Dedicated offline student admission form and a validated offline application submission workflow.
- Separate “All Students” and “Add New Student” dashboard navigation entries.
- Student search by name, phone/registration number, batch, present/permanent area, and blood group.
- Online/offline student dropdown filtering, batch filtering, mode totals, and mode/area/blood-group table columns.
- Mouse-drag, touch-swipe, keyboard navigation, pause-on-interaction, and scroll-safe gestures for the homepage hero carousel.
- Lightweight spring-style reveal animations across public pages with `prefers-reduced-motion` accessibility support.
- Professional IT-themed Unsplash fallbacks for courses without uploaded artwork, including modal previews and broken-image recovery.
- Dhaka IT Institute branding with the green and black visual theme.
- Online/offline course mode controls and mode-aware student course browsing.
- Public admission form matching the institute's paper admission workflow.
- Student course enrollment records with database uniqueness constraints.
- Manual bKash payment submission, transaction reference, private proof upload, admin review, approval/rejection notifications, and automatic enrollment.
- Sequential video learning: completing a lesson records progress and opens the next lesson automatically.
- Private authorized routes for paid videos, materials, and payment proofs.
- Active-account and forced-password-change account flags.
- Login throttling and production deployment templates for Render, Koyeb, Docker, PostgreSQL, S3, queues, and SMTP.
- Initial production security regression tests.
- Verified Dhaka IT Institute profile content: Mirpur-10 address, phone, email, website, training mission, services, and online/offline delivery.
- Secure, expiring password setup/reset for approved applicants and staff-created accounts.
- Forced password replacement for accounts issued with temporary credentials.
- Render health checks and a persistent queue-worker service definition.
- Production restore kill switch and backup filename traversal protection.
- Security policy and safe deployment/runbook documentation.

### Changed

- Replaced the active school-style dashboard navigation with a focused course-selling menu; legacy classes, academic exams, attendance, routines, parents, inventory, and unrelated modules remain hidden from navigation.
- Shortened the public admission form to essential contact, address, learning-mode, and course fields.
- Replaced school-oriented frontend navigation with Services, Team, and certificate verification links.
- Corrected the Dhaka IT Institute monogram across the header logo and every favicon: the complete “d” (curve and right stem) is black and the lower-right panel is white.
- Cache-versioned both logo and favicon assets so deployed browsers refresh the corrected branding immediately.
- Reworked admin student registration so online/offline mode filters institute courses directly; school class and immediate batch assignment are optional.
- Added the admin role to student-management navigation while keeping student records restricted to admin and super-admin routes.
- Updated Laravel and all Composer dependencies to supported patched versions.
- Unified successful course payments on `completed`, while retaining legacy `approved` compatibility.
- Limited user/role management and system configuration to super administrators.
- Restricted teacher and student portals by role.
- Added parent-child ownership checks to nested course videos/materials and teacher attendance operations.
- Disabled automatic demo credential seeding in production.
- Prevented every demo/content seeder from running through `DatabaseSeeder` in production.
- Production startup now seeds only required roles, permissions, and the configured initial owner after migrations.
- Disabled debug mode and removed secrets from committed environment templates.
- Removed unsafe public upload-test routes and GET logout.
- Restricted uploaded branding and course-material file types.
- Replaced fictional school/Alphainno frontend content and academic Class 1–12 seed courses with Dhaka IT Institute training content.
- Added Microsoft Office, professional web design, Facebook marketing/ecommerce, and full-stack development seed courses with changeable fee/schedule notice.
- Removed legacy public diagnostic scripts and dead upload-test code.
- Normalized settled-payment calculations throughout portals, invoices, exports, and reports.

### Security

- Composer audit: zero known advisories as of 2026-07-29.
- npm production audit: zero known advisories as of 2026-07-29.
- Payment evidence and premium learning assets are no longer public URLs.
- Automated release suite: 24 tests and 114 assertions passing as of 2026-08-05.

### Fixed

- Fixed dashboard custom-select hover styles dimming the entire option, which made option text difficult to read.
- Fixed the missing Carbon import in the student ID generator that caused automatic registration-number generation and admission submission to return HTTP 500.
- Fixed dynamically hidden online/offline course options appearing inside the reusable custom select menu.
- Centered the Admission header action on desktop and added the same prominent action to mobile navigation.
- Corrected malformed course-page section markup that could cause inconsistent browser layouts.
- Fixed invalid compiled Blade PHP in the shared admin header that caused every authenticated Render dashboard page to return HTTP 500.
- Replaced every legacy Talent IT browser icon with a cache-versioned Dhaka IT Institute favicon, including ICO, 16px, 32px, and Apple touch variants.
- Made the database favicon setting and application fallback use the branded icon so a fresh deployment cannot restore the legacy favicon.

## Release policy

Production releases must run the test suite, build frontend assets, review `TODO.md`,
back up the production database, and use unique environment secrets.
