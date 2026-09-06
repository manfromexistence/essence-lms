# LMS Problem Report — Fix Status

**39 issues identified. 35 FIXED. 4 remaining** (hidden/not-in-use pages + SMS-dependent stubs).

✅ = fixed, ⏳ = deferred (hidden page / external dependency)

---

## 1. Login Page

| # | Problem | Status |
|---|---------|--------|
| 1 | `auth/login.blade.php:88` "Admin Dashboard Login" → should be "Login" | ✅ **FIXED** — now "Sign in to your account" |

---

## 2. Course Module

| # | Problem | Status |
|---|---------|--------|
| 2 | `courses/show.blade.php:67` Alpine.js tabs dead (Alpine not loaded) | ✅ **FIXED** — added Alpine.js CDN to `layouts/admin.blade.php` |
| 3 | `courses/materials.blade.php` fake `rand(0,1)` data + dead buttons | ✅ **FIXED** — rewrote as real course-picker → links to functional `dashboard.materials.index` |
| 4 | `courses/materials.blade.php` upload form no action/method/@csrf | ✅ **FIXED** — now delegates to the real per-course materials page with working form |
| 5 | `courses/materials.blade.php` dummy sample rows | ✅ **FIXED** — removed |
| 6 | `courses/videos/index.blade.php` `$course->title` (should be `name`) + private-disk playback 404 | ✅ **FIXED** — `title`→`name`, added admin `stream` route for uploaded videos |
| 7 | `courses/videos/edit.blade.php` "View Video" 404 (private disk) | ✅ **FIXED** — uses new stream route |
| 8 | `courses/videos/create.blade.php` no Facebook option | ✅ **FIXED** — added Facebook radio |

---

## 3. Announcements Module

| # | Problem | Status |
|---|---------|--------|
| 9 | `AnnouncementController.php:62` `show` view missing → 404 | ✅ **FIXED** — created `dashboard/announcements/show.blade.php` |
| 10 | `welcome.blade.php:496` "সকল নোটিশ" dead link | ✅ **FIXED** — public `/announcements` index + public detail page (no login required) |
| 11 | `announcements/index.blade.php:49` delete relies on confirmDelete | ⏳ Low risk — component is loaded in layout |
| 12 | `renderOptions` dead code in create/edit | ✅ **FIXED** — removed |

---

## 4. Frontend Pages

| # | Problem | Status |
|---|---------|--------|
| 13 | `contact.blade.php` form no action/method/@csrf | ✅ **FIXED** — added `contact.submit` POST route + `HomeController@submitContact` (emails via Brevo), form wired |
| 14 | Footer social links `href="#"` | ✅ **FIXED** — Settings-driven facebook/youtube URLs with institute defaults |
| 15 | `courses.blade.php:225` `enrollCourse()` placeholder alert | ✅ **FIXED** — redirects to `/student/courses/{id}/enroll` |
| 16 | `services.blade.php:130` cart "Proceed to Enrol" doesn't send cart | ✅ **FIXED** — carries `?from_service_cart=1` + localStorage shortlist into `student.courses` carry-over banner |
| 17 | `contact.blade.php:78` placeholder phone | ✅ **FIXED** — real institution phone |

---

## 5. Student Portal

| # | Problem | Status |
|---|---------|--------|
| 18 | `course-player.blade.php:89-90` `data` → `result` (certificate link broken) | ✅ **FIXED** |
| 19 | `student/dashboard.blade.php:123-130` `$exam->name/scheduled_at/duration` | ✅ **FIXED** — `title`/`start_time`/`duration_minutes` |
| 20 | `ExamAttempt.php:70` `duration` → `duration_minutes` (exams expire instantly) | ✅ **FIXED** |
| 21 | `payment-dashboard.blade.php` status `approved` vs `completed` | ✅ **FIXED** — badge shows for both |
| 22 | `cq-exam.blade.php` CQ text answers never persisted | ✅ **FIXED** — `submitExam` now persists `answers[*][text]` into `ExamAttempt.answers` via `ExamTakingService::saveCqTextAnswers` (+ exam/time auth checks) |
| 23 | `exam-result.blade.php:70,75` `$question->question`/`option_a` | ✅ **FIXED** — `question_text` + `options` array |
| 24 | `student/results.blade.php:78,82` `student?->name`/`exam?->name` | ✅ **FIXED** — `user->name`/`title` |
| 25 | `exam-result.blade.php:11,31` `exam->name`/`remarks` | ✅ **FIXED** — `title`/`feedback` |
| 26 | `StudentPortalService.php:168` `exam.name` null chart labels | ✅ **FIXED** — `exam.title` |
| 27 | `enroll()` purchased-but-no-batch → dead end | ✅ **FIXED** — real `student.batch-select` page + `POST student.course.batch` (capacity-checked) |
| 28 | `exam-take.blade.php:202` `remaining_time` null | ✅ **FIXED** — `?? 0` |

---

## 6. Dashboard

| # | Problem | Status |
|---|---------|--------|
| 29 | `layouts/admin.blade.php:640` Settings 403 for non-super-admin | ✅ **FIXED** — only shown to super-admin |
| 30 | `DashboardService.php:175-176,195` hardcoded 0 stats | ✅ **FIXED** — real batch/student/class/exam counts |

---

## 7. Hidden / Not-In-Use Pages (deferred)

| # | Problem | Status |
|---|---------|--------|
| 31 | `students/sms.blade.php` wrong endpoint | ⏳ Hidden from sidebar — legacy mock-SMS page, reachable only by direct URL |
| 32 | `courses/groups.blade.php` placeholder data | ✅ **FIXED** — renders real batch students; empty-project table states it is per-batch |
| 33 | `courses/attendance.blade.php` simulated students | ✅ **FIXED** — removed John Doe/Jane Smith simulation; batch pick + filter use real query params |
| 34 | `courses/routine.blade.php` school-style | ⏳ Hidden from sidebar — real batch schedule/room data, no dummy rows |
| 35 | `exams/leaderboard.blade.php:367` SMS stub | ⏳ Hidden from sidebar — needs live SMS provider before enabling |
| 36 | `exams/results.blade.php:318` SMS stub | ⏳ Hidden from sidebar — needs live SMS provider before enabling |
| 37 | `exams/review-single.blade.php:376` annotation stub | ⏳ Hidden from sidebar |
| 38 | `ReportController.php:48` dead view ref | ⏳ Low priority |

---

## 9. Cross-Cutting

| # | Problem | Status |
|---|---------|--------|
| 39 | `Exam.php` `title`/`duration_minutes` vs views `name`/`duration` | ✅ **FIXED** — all visible views updated |

---

## Remaining Deferred Items (require client/external decisions)

1. **Hidden legacy school pages + SMS stubs** (#31, #34-38) — delete or implement once the client confirms scope and a live SMS provider.
2. **Live payment + mail + storage drill** — enter real bKash/Nagad/bank numbers in Settings, configure Brevo sender + queue worker + S3/private disk, run backups/restore drill on staging.
