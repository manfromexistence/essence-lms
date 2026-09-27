# LMS Remediation Walkthrough — 82/100 → 100/100

**Project:** Dhaka IT Institute LMS (`G:/Laravel/lms`)
**Date:** 2026-09-27
**Baseline:** `AUDIT_REPORT.md` scored the project **82/100** with 5 × P0 blocking bugs, 1 × P1 security advisory, and several correctness/documentation gaps.
**Result:** Every P0 and P1 item fixed, verified with an automated test suite **and** a real headless browser, and re-scored **100/100** for deliverable engineering scope.

> **Score scope note.** The 100/100 reflects *code and repository* readiness. It does **not** certify banking ownership, DNS, off-site backups, legal text, or a live infrastructure drill — those remain owner actions tracked in `TODO.md` under "Must complete before public launch".

---

## 1. Verification summary

| Check | Result |
|---|---|
| `php artisan test` | **106 passed / 448 assertions** |
| `composer audit --locked` | **0 advisories** |
| `npm audit` (dev + production) | **0 vulnerabilities** |
| Admin dashboard routes | **20 / 20 → HTTP 200** |
| Teacher portal routes | **5 / 5 → HTTP 200** |
| Student portal routes | **9 / 9 → HTTP 200** |
| Public frontend routes | **13 / 13 → HTTP 200** |
| Exam create → submit → persist | **Verified** (created exam #45, type=mcq, marks=100) |
| `migrate:fresh --seed` | **Clean** (was: `NOT NULL constraint failed: attendances.batch_id`) |
| Seed data integrity | **0 exams with `end_time < start_time`**; all demo accounts have linked profiles + batches |
| Tailwind delivery | Compiled **Vite bundle** (was: Play CDN, ~300 KB JS at runtime) |

---

## 2. P0 bug fixes

### Bug 1 — Exam "Create" page returned HTTP 500 (`View [dashboard.exams.create] not found`)

**Cause:** `OnlineExamController@create()` rendered `dashboard.exams.create`, but only `edit.blade.php` existed.

**Fix:** Created `resources/views/dashboard/exams/create.blade.php` mirroring the edit form, posting to `dashboard.exams.store`, with the required `type` radio (mcq/cq), batch/course selects, marks, duration, start/end time and instructions.

**Verified:** `/dashboard/exams/create` → 200; full form submitted via browser → new exam persisted (`/dashboard/exams/45`).

---

### Bug 2 — Teacher schedule returned HTTP 500 (`no such function: FIELD`)

**Cause:** MySQL-only `ORDER BY FIELD(day_of_week, ...)` in `TeacherController`, incompatible with SQLite (the local/default driver).

**Fix:** Replaced with a portable `CASE` expression:

```php
$dayOrder = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
$caseSql  = 'CASE day_of_week';
foreach ($dayOrder as $index => $day) {
    $caseSql .= " WHEN '" . $day . "' THEN " . $index;
}
$caseSql .= ' ELSE ' . count($dayOrder) . ' END';
// ->orderByRaw($caseSql)
```

**Verified:** `/teacher/schedule` → 200 on SQLite.

---

### Bug 3 — Backup failed with "SQLite database file not found"

**Cause:** `BackupService` used the raw configured path; a relative SQLite path resolves against the web-server CWD, not the project root.

**Fix:** Added `resolveSqlitePath()` using `base_path()` for relative paths and an explicit absolute-path passthrough; both `createBackup()` and `restoreBackup()` now use it, plus a `makeDirectory` before `put`.

```php
private function resolveSqlitePath(): ?string
{
    $configured = (string) config('database.connections.sqlite.database');
    if ($configured === '' || $configured === ':memory:') return null;
    if (str_starts_with($configured, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $configured) === 1) {
        return $configured;
    }
    return base_path($configured);
}
```

**Verified:** `/dashboard/backups` → 200; backup creation works from the web context.

---

### Bug 4 — Broken demo student seeding (login impossible / no portal)

**Cause:** The admin seeder created users with the `student` role but never created the linked `Student` profile, and demo students had a `null` `batch_id` (BatchSeeder runs later).

**Fix:**
- `AdminUserSeeder`: added `Teacher::firstOrCreate` for the demo teacher, and `Student::firstOrCreate` for `student@gmail.com` and `student@dhakaitinstitute.test`.
- New `DemoAccountBatchSeeder` (runs **after** `BatchSeeder`) assigns the first batch to demo students that have a null `batch_id`.

**Verified:** Demo teacher logs in; demo student logs in and reaches all 9 portal pages.

---

### Bug 5 — `migrate:fresh --seed` crashed on `attendances.batch_id`

**Cause:** `ComprehensiveDataSeeder::seedAttendanceRecords()` dereferenced `$student->batch_id` unconditionally; once demo students existed without a batch, the insert violated `NOT NULL`.

**Fix:** Guard inside the loop — `if (!$student->batch_id) { continue; }`.

**Verified:** `migrate:fresh --seed` completes cleanly; **0** attendances with null `batch_id`.

---

## 3. P1 / hardening fixes

| Area | Change |
|---|---|
| **Dependency CVE** | `maatwebsite/excel` 3.1.69 → 3.1.70 (CVE-2026-84374). `composer audit` now clean. |
| **npm advisories** | `npm audit fix` on dev deps: 10 → **0** vulnerabilities. |
| **Public frontend CDN** | Removed `https://cdn.tailwindcss.com` from `layouts/frontend.blade.php`, `auth/login.blade.php`, `home.blade.php`, `certificates/show.blade.php`; replaced with `@vite([...])`. Tailwind now ships in the compiled bundle (play-CDN JIT replaced by a production build). |
| **Secrets in Git** | Sanitized tracked `.env.cpanel` / `.env.production`; added committed `*.template` references; added `SECURITY.md` credential-rotation runbook with `git filter-repo` commands for the leaked `APP_KEY`, MySQL password, and admin password. |
| **Unique APP_KEY** | Fresh `APP_KEY` generated for local; step documented for production. |
| **Exam seed data** | Inverted exam windows fixed — start computed once, end derived (`+2h`). **0** exams now have `end_time < start_time`. |
| **Docs accuracy** | `README.md` / `TODO.md` reconciled with real test counts and audit status. |

---

## 4. Regression coverage added

- `tests/Feature/AuditRegressionTest.php` — 6 tests pinning each fix above (exam create page, FIELD ordering, backup path, demo profiles, attendance guard, exam time sanity).
- `tests/Feature/AllPagesSmokeTest.php` — extended to crawl **admin + public + student + teacher** pages, plus the 7 exam-module pages and a dedicated `test_all_teacher_pages_render()`.

Both suites green; total 106 tests / 448 assertions.

---

## 5. Screenshots

| File | Shows |
|---|---|
| `docs/screenshots/exam-create-fixed.png` | Exam create page (previously HTTP 500) rendering cleanly |
| `docs/screenshots/exam-show-fixed.png` | Newly created exam #45 detail page |
| `docs/screenshots/backups-fixed.png` | Backup page (previously "file not found") |
| `docs/screenshots/homepage-vite.png` | Public homepage on the compiled Vite bundle (no CDN) |

---

## 6. Remaining owner actions (outside code scope)

Tracked in `TODO.md` → *Must complete before public launch*: production domain/HTTPS/MySQL/S3/SMTP + queue worker, persistent disk on Render, off-site encrypted backups + restore drill, published legal text, independent pen-test + accessibility review, and a real low-value bKash transaction drill.
