# Changelog

All notable changes to the Dhaka IT Institute LMS are documented here.

## [Unreleased] - 2026-07-29

### Added

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
- Automated release suite: 13 tests and 32 assertions passing as of 2026-07-29.

### Fixed

- Fixed invalid compiled Blade PHP in the shared admin header that caused every authenticated Render dashboard page to return HTTP 500.
- Replaced every legacy Talent IT browser icon with a cache-versioned Dhaka IT Institute favicon, including ICO, 16px, 32px, and Apple touch variants.
- Made the database favicon setting and application fallback use the branded icon so a fresh deployment cannot restore the legacy favicon.

## Release policy

Production releases must run the test suite, build frontend assets, review `TODO.md`,
back up the production database, and use unique environment secrets.
