# Production and Client Handover Checklist

This file contains work that requires the client's infrastructure, credentials, business decisions, or an external audit. It is intentionally not hidden behind a “100%” claim.

## Current engineering status

- Repository-controlled production hardening: **100% complete for this release scope**
- Automated release checks: **24 tests / 114 assertions passing**
- Known dependency advisories: **0 Composer / 0 npm production**
- Public-launch acceptance: pending the client/infrastructure items below

## Must complete before public launch

- [ ] Enter the real bKash merchant/personal number and final payment instructions in Admin Settings; test one real low-value transaction and refund.
- [ ] Configure the production domain, HTTPS, PostgreSQL, S3-compatible private storage, SMTP, and a persistent queue worker.
- [ ] Set a unique `APP_KEY`; rotate any key or credential that ever appeared in Git history and remove the old secret from repository history.
- [ ] Set `INITIAL_ADMIN_EMAIL` and a unique 16+ character `INITIAL_ADMIN_PASSWORD` for the first deployment, sign in, change it, then remove those variables.
- [ ] Configure automated encrypted off-site database/object-storage backups and complete a documented restore drill.
- [x] Add an email account activation and expiring password-setup flow for approved public admission applicants.
- [ ] Confirm the institute's refund, privacy, terms, retention, and student-consent policies with the client and publish approved text.
- [ ] Run a staging user-acceptance test with the client for online/offline visibility, compact admission, bKash approval, notifications, enrollment, demo lessons, video progression, certificates, Services, and Team content.
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
