# Changelog

## 2026-09-28 (e) — CRITICAL: admission form unusable, no course selectable

Reported by the client: on `/admission` both the **Learning Mode** and **Course**
dropdowns showed only "Select Option" with nothing in them, so applicants could
not choose a course and could not submit at all.

Reproduced on the live site: both custom dropdowns rendered **0 items**.

### Root cause 1 — a duplicate `id` shadowed the real `<select>`

`components/ui/select.blade.php` merged the caller's `$attributes` into **both**
the wrapper `<div>` and the native `<select>`, while also hard-coding an id on
each. That produced two `id` attributes per tag, and HTML keeps the **first**:

```html
<div  ... id="course_id" id="select-group-course_id" ...>   <- div keeps course_id
<select name="course_id" id="course_id" ... id="course_id">  <- duplicated
```

Since the wrapper comes first in document order,
`document.getElementById('course_id')` returned the **div**, not the select.
`initCustomSelect()` then read `.options` off a div, **threw, and aborted its
`forEach` loop** — so every custom dropdown on the page stayed empty. That is why
*Learning Mode* was broken too, even though it had nothing to do with courses.

Fixed by excluding `id` from the merged attributes on both elements, so the
wrapper owns `select-group-{name}` and the `<select>` owns `{name}`. The init
loop is now also wrapped in try/catch and `renderOptions()` guards against a
non-select, so one bad control can never blank the rest again.

### Root cause 2 — the mode filter hid every course on load

```js
option.hidden = option.dataset.mode !== mode.value;
```

With no learning mode selected yet, `mode.value` is `''`, so **every** course
failed the comparison and was hidden. Now courses are only narrowed once a mode
is actually chosen. The same defect was present in the admin student create and
edit forms and is fixed there too.

### Verified

- `/admission` dropdowns populate: Learning Mode 2 items, Course 4 items.
- Mode filtering still works: Offline → 3 offline courses; Online → 2 online;
  switching back restores offline.
- New `CustomSelectComponentTest` (5 tests) asserts no duplicate ids, that the
  plain id sits on the `<select>` and not the wrapper, and that the filter only
  hides when a mode is chosen.

Suite: **133 tests / 529 assertions**.

## 2026-09-28 (d) — Deploys were failing: Apache started too late

Pushes appeared not to deploy. The Render build log showed the real cause:

```
==> Deploying...
==> Setting WEB_CONCURRENCY=1 by default, based on available CPUs
==> Timed Out
==> Port scan timeout reached, no open ports detected.
```

The Docker build **succeeded** — the container just never opened a port in time.
`docker-entrypoint.sh` started `apache2-foreground` as its **last** step, after
all the database work. Measured on a fast local machine:

| Boot step | Time |
|---|---|
| `migrate --force` on a fresh DB (85 migrations) | ~30s |
| full demo seed | ~20s |
| Role + DefaultRoleAccount seeders | ~2s |
| `artisan optimize` (view:cache) | ~13s |
| **before Apache started** | **~65s** |

Render's port-scan timeout is ~60s, and a free-tier CPU is slower still — so the
deploy was killed before Apache ever bound port 80, Render marked it failed, and
**kept serving the previous build**. That is indistinguishable from "my push
never deployed", which is why it looked like GitHub was not connected.

### Fixed

`docker-entrypoint.sh` now starts Apache **first**, before any database work.
Laravel's `/up` health route never touches the database, so Render's health check
passes as soon as the port is open; migrations and seeding then run while the
port is already bound.

Verified with a stubbed `apache2-foreground` on a fresh database:

| | Before | After |
|---|---|---|
| Port bound at | ~34–65s | **~2s** |

Also in this file:
- the "is the database seeded?" check no longer shells out to
  `artisan tinker` — that boots PsySH (~4s) and can hang with no TTY attached.
  It is now a direct query via `php -r`.
- `TERM`/`INT` are trapped and forwarded to Apache so the container shuts down
  cleanly instead of being SIGKILLed.

## 2026-09-28 (c) — Properly themed checkboxes (login looked native)

The earlier checkbox fix only set `accent-color`, which merely tints the
OS-drawn tick: the control kept its native square, had no hover state, no themed
border and no control over size or radius. On the login page it still read as a
raw browser checkbox.

### Changed

- `resources/css/app.css` now styles checkboxes **in full**: `appearance: none`,
  18px box, 5px radius, themed border, a brand-tinted hover halo, a brand fill
  with a white tick when checked, a brand focus ring, and a disabled state.
  Indeterminate (used by "select all") gets its own dash glyph.
- The rules live **outside `@layer base` on purpose**. Tailwind utilities are
  layered, and an unlayered rule always wins the cascade — so the themed look
  beats the `h-4 w-4 border border-gray-300 bg-white` classes that ~20 views
  hand-write on their own `<input>`, with none of those views edited. Verified in
  the browser: a checkbox carrying `h-4 w-4` still computes to 18px.
- `components/ui/checkbox.blade.php` no longer duplicates the visual styling; it
  keeps only layout utilities, so there is one source of truth.
- Accent resolution falls back through every layout's variable name
  (`--color-primary` → `--color-primary-rgb` → `--rgb-primary` → default brand
  blue), because the auth pages, the frontend layout and the admin layout each
  expose a different set.

### Guarded

- The rules **exclude `.sr-only`**. `components/ui/switch.blade.php` renders a
  visually-hidden checkbox and paints the switch itself — styling that input
  would make it visible and silently break every toggle. Verified in the browser
  that an `sr-only` checkbox still computes to 1px/absolute/native, and a new
  test asserts the exclusion can't be dropped in a refactor.

Suite: **127 tests / 513 assertions**.

## 2026-09-28 (b) — Write-route authorization sweep + 2 more bugs

The first hardening pass probed all **GET** routes but never the **127 write
routes** (POST/PUT/PATCH/DELETE). Probing those found two more defects and, more
importantly, produced evidence that authorization actually holds.

### Fixed — `POST /dashboard/accounts/export-pdf` → 500

`AccountController@exportPdf` rendered `exports.pdf.financial-report`, a template
that did not exist. It slipped past the first pass because that check only
matched `view('...')` and this call is `PDF::loadView('...')`. The template is
now written, and the static check covers `loadView`, `View::make` and
`Mail::markdown` as well.

### Fixed — `DELETE /dashboard/users/{user}` → 500

Several tables reference `users` with a *restricting* foreign key
(`inventory_transactions.created_by`, `certificates.issued_by`), so deleting a
user who has that history raised a raw `QueryException` and surfaced as a 500.
The delete now runs in a transaction and reports a clear message pointing the
operator at deactivation instead.

### Verified — write routes are properly guarded

127 write routes probed per role, with CSRF satisfied (a real session token) so
requests reach the controller, auth/authorization middleware fully active, and
the whole run wrapped in a rolled-back transaction:

| Actor | Reachable with an empty payload | Unintended 5xx |
|---|---|---|
| guest | **0 / 127** | 0 |
| student | **0 / 127** | 0 |
| teacher | **0 / 127** | 0 |

The only non-2xx "error" seen is a deliberate `503` from
`POST /student/payment/submit`, which refuses online payment when no payment
method is configured rather than showing placeholder account numbers.

Rate limiting also proved to be active (a `429` appears once the probe exceeds
the throttle).

### Added — write-route authorization regression test

`ViewAndRouteIntegrityTest` now also asserts that no write route answers `200`
to an **empty** payload for a guest, student or teacher — every one of them must
require input (302/422), be forbidden (403) or not exist (404). This is the
assertion that would have caught an unguarded action.

### Note — code style

Pint reports style drift in **243 files** and is not run in CI. The files
touched by this work are Pint-clean, but a repo-wide `./vendor/bin/pint` sweep
was deliberately **not** applied, because mixing ~1000 lines of mechanical
reformatting into these security fixes would make them unreviewable. It is
available as a standalone change.

Suite: **127 tests / 513 assertions**.

## 2026-09-28 — Hardening pass: 13 missing views, 6 broken routes, zero CDN

A systematic sweep (every `view()` reference statically checked; all 612
route × role combinations probed) uncovered a cluster of routes that returned
HTTP 500 in production. All are fixed and now guarded by regression tests.

### Fixed — 13 `view()` references pointed at Blade files that never existed

Every one of these produced `InvalidArgumentException: View [x] not found`:

| Missing view | Route affected |
|---|---|
| `dashboard.users.create` | **`/dashboard/users/create`** — reachable from the "Add User" button |
| `dashboard.users.edit` | `/dashboard/users/{user}/edit` |
| `dashboard.users.show` | `/dashboard/users/{user}` |
| `dashboard.salaries.create` | `/dashboard/salaries/create` |
| `dashboard.salaries.edit` | `/dashboard/salaries/{salary}/edit` |
| `dashboard.inventory.create` | `/dashboard/inventory/create` |
| `dashboard.inventory.edit` | `/dashboard/inventory/{item}/edit` |
| `dashboard.materials.create` | `/dashboard/courses/{course}/materials/create` |
| `dashboard.materials.edit` | `/dashboard/courses/{course}/materials/{material}/edit` |
| `dashboard.roles.show` | **`/dashboard/roles/{role}`** — reachable from the Roles list |
| `dashboard.roles.edit` | `/dashboard/roles/{role}/edit` |
| `dashboard.reports.index` | report hub (controller action existed, no route) |
| `student.cq-submission` | **`/student/cq-submission/{submission}`** — reachable after uploading a CQ answer |

### Fixed — resource routes whose controller methods were never written

`Route::resource(...)` registers `show`/`edit` automatically, but three
controllers never implemented `show`, so those URLs died with
`Call to undefined method`:

- `SalaryController::show()` — added, plus a detail view.
- `MaterialController::show()` — added; streams the file from the **private**
  disk (files are not publicly reachable) and redirects for external links.
- `ScheduleController::show()` — added, plus a detail view.

### Fixed — `/dashboard/exams/download-template` returned 404

`Route::resource('exams', …)` registers `GET exams/{exam}`, and it was declared
**before** the literal `exams/download-template` route — so "download-template"
was bound as an exam ID, route-model binding failed and the request 404'd.
The three "download template" links on the import page were all dead. Fixed by
registering literal `exams/...` routes before the resource route.

### Fixed — other broken routes

- **`/dashboard/exams/{exam}/review`** → 500 `Collection::total does not exist`
  (controller passed `->get()`, view called `->total()`/`->links()`) and
  `Undefined variable $pendingCount`. Now paginated, with the pending /
  reviewed / average-score tiles computed and passed.
- **`/student/payment/dashboard`** → 500 `Attempt to read property "name" on
  null`. 39 of 59 seeded payments have a `NULL course_id`, so the grouped
  "course" was null and the view dereferenced it. Guarded the view and the
  payment-date formatting.
- **`/dashboard/payments/invoices/{invoice}`** → 500 `count(): Argument #1 must
  be of type Countable|array, string given`. `Invoice::items` is array-cast but
  three seeders wrapped the value in `json_encode()`, **double-encoding** it so
  it hydrated as a string. Seeders fixed; the view also normalises legacy rows.
- **`/student/payments/{payment}/receipt`** → 500 `View [pdf.receipt] not
  found`. The receipt PDF template was missing entirely; added.
- **`/student/materials/{material}/download`** → 500 `Flysystem::has():
  Argument #1 must be of type string, null given` when a material has no file.

### Fixed — `Exam::name` alias

`Exam` stores `title`, but several views and PDF exports read `$exam->name`.
An undefined attribute returns null, so those headings silently rendered blank.
Added a `getNameAttribute()` accessor — one change fixes every call site.

### Changed — removed all external CDN dependencies

The public site, login and certificate pages previously hard-depended on
`cdn.tailwindcss.com`, `cdn.jsdelivr.net` (Alpine, Chart.js, Sortable) and
`cdnjs.cloudflare.com` (Font Awesome, Fabric). A CDN outage broke the UI, and
every visitor's IP leaked to third parties. Now:

- `resources/js/app.js` — Alpine + Font Awesome, loaded on every page.
- `resources/js/admin.js` — Chart.js, Sortable, Fabric, loaded only by the
  admin layout so the public site does not pay for ~500 KB it never uses.
- Fabric migrated **v5 → v7.4.0** (`setBackgroundImage` removed; `fromURL` and
  `loadFromJSON` now return Promises; `getPointer` → `getScenePoint`).
- An `overrides.tar` entry pins a patched `tar`, clearing the advisory chain
  pulled in by Fabric's optional `canvas` dependency.

### Added — integrity regression tests

`tests/Feature/ViewAndRouteIntegrityTest.php`:
- every `view()` reference in `app/` + `routes/` must resolve to a Blade file;
- every GET route must point at a controller method that exists;
- every parameterless GET route must return non-5xx for a signed-in admin;
- `Invoice::items` must round-trip as an array (not a JSON string);
- `Exam::name` must alias `title`.

Both guards were verified to fail when a view is deliberately removed.

### Verified

- `php artisan test` → **126 passed / 511 assertions**
- `composer audit --locked` → **0 advisories**; `npm audit` → **0 vulnerabilities**
- **612 route × role combinations probed → 0 server errors**
- `migrate:fresh --seed` → clean; invoice `items` and exam `answers` hydrate as arrays

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
