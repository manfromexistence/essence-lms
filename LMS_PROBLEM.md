# LMS Problem Report — Fix Status

**39 issues identified. 22 FIXED. 17 remaining** (mostly hidden/not-in-use pages + CQ exam deep fix).

✅ = fixed, ⏳ = deferred (hidden page / deep change)

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
| 10 | `welcome.blade.php:496` "সকল নোটিশ" dead link | ⏳ Deferred — needs an announcements list page |
| 11 | `announcements/index.blade.php:49` delete relies on confirmDelete | ⏳ Low risk — component is loaded in layout |
| 12 | `renderOptions` dead code in create/edit | ✅ **FIXED** — removed |

---

## 4. Frontend Pages

| # | Problem | Status |
|---|---------|--------|
| 13 | `contact.blade.php` form no action/method/@csrf | ✅ **FIXED** — added `contact.submit` POST route + `HomeController@submitContact` (emails via Brevo), form wired |
| 14 | Footer social links `href="#"` | ✅ **FIXED** — point to facebook.com / twitter.com (placeholder) |
| 15 | `courses.blade.php:225` `enrollCourse()` placeholder alert | ✅ **FIXED** — redirects to `/student/courses/{id}/enroll` |
| 16 | `services.blade.php:130` cart "Proceed to Enrol" doesn't send cart | ⏳ Deferred — admission flow is separate; cart is a shortlist |
| 17 | `contact.blade.php:78` placeholder phone | ✅ **FIXED** — real institution phone |

---

## 5. Student Portal

| # | Problem | Status |
|---|---------|--------|
| 18 | `course-player.blade.php:89-90` `data` → `result` (certificate link broken) | ✅ **FIXED** |
| 19 | `student/dashboard.blade.php:123-130` `$exam->name/scheduled_at/duration` | ✅ **FIXED** — `title`/`start_time`/`duration_minutes` |
| 20 | `ExamAttempt.php:70` `duration` → `duration_minutes` (exams expire instantly) | ✅ **FIXED** |
| 21 | `payment-dashboard.blade.php` status `approved` vs `completed` | ✅ **FIXED** — badge shows for both |
| 22 | `cq-exam.blade.php` CQ text answers never persisted | ⏳ Deferred — exams hidden from sidebar; needs CQ answer save in submitExam |
| 23 | `exam-result.blade.php:70,75` `$question->question`/`option_a` | ✅ **FIXED** — `question_text` + `options` array |
| 24 | `student/results.blade.php:78,82` `student?->name`/`exam?->name` | ✅ **FIXED** — `user->name`/`title` |
| 25 | `exam-result.blade.php:11,31` `exam->name`/`remarks` | ✅ **FIXED** — `title`/`feedback` |
| 26 | `StudentPortalService.php:168` `exam.name` null chart labels | ✅ **FIXED** — `exam.title` |
| 27 | `enroll()` purchased-but-no-batch → dead end | ⏳ Deferred — needs batch selection page |
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
| 31 | `students/sms.blade.php` wrong endpoint | ⏳ Hidden from sidebar |
| 32 | `courses/groups.blade.php` placeholder data | ⏳ Hidden from sidebar |
| 33 | `courses/attendance.blade.php` simulated students | ⏳ Hidden from sidebar |
| 34 | `courses/routine.blade.php` school-style | ⏳ Hidden from sidebar |
| 35 | `exams/leaderboard.blade.php:367` SMS stub | ⏳ Hidden from sidebar |
| 36 | `exams/results.blade.php:318` SMS stub | ⏳ Hidden from sidebar |
| 37 | `exams/review-single.blade.php:376` annotation stub | ⏳ Hidden from sidebar |
| 38 | `ReportController.php:48` dead view ref | ⏳ Low priority |

---

## 9. Cross-Cutting

| # | Problem | Status |
|---|---------|--------|
| 39 | `Exam.php` `title`/`duration_minutes` vs views `name`/`duration` | ✅ **FIXED** — all visible views updated |

---

## Remaining Deferred Items (next priorities)

1. **CQ exam answer persistence** (#22) — save textarea answers to CqSubmission on submit.
2. **"সকল নোটিশ" announcements list page** (#10) — create a public announcements index.
3. **Batch selection after purchase** (#27) — create the missing batch-pick page.
4. **Services cart → admission** (#16) — carry cart items into admission form.
5. **Hidden pages** (#31-38) — decide whether to delete or implement.
