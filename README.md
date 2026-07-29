# Dhaka IT Institute LMS

Production-oriented Laravel LMS for Dhaka IT Institute. It supports online and
offline courses, admissions, batches, teachers, attendance, exams, reports,
private course content, sequential video progress, and manually verified bKash
course enrollment.

## Requirements

- PHP 8.3 with the extensions used by the Dockerfile
- Composer 2
- Node.js and npm
- PostgreSQL or MySQL in production
- SMTP and S3-compatible private object storage
- A persistent Laravel queue worker

## Local setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Use `php artisan migrate:fresh` only for disposable test data. Never use it
against a development, staging, or production database containing records.

## Verification

```bash
php artisan test
composer audit --locked
npm audit --omit=dev
npm run build
php artisan optimize
```

## Production deployment

1. Provision PostgreSQL, private S3 storage, SMTP, HTTPS, and a queue worker.
2. Copy `.env.production` into the platform's secret manager and supply every
   empty value. Never commit the resulting environment file.
3. Set the initial owner credentials, deploy, then remove those variables after
   the owner changes their password.
4. Run `php artisan migrate --force` and `php artisan optimize`.
5. Verify `/up`, queue processing, mail delivery, private downloads, a real
   low-value bKash approval, and a backup restore in staging.

The Docker, Render, and Koyeb templates are starting points; platform secrets
must be configured by the deployment owner.

## Release governance

- [CHANGELOG.md](CHANGELOG.md) records shipped work.
- [TODO.md](TODO.md) is the client launch gate.
- [SECURITY.md](SECURITY.md) defines vulnerability reporting and operational controls.

No release should be presented as publicly launch-ready until the external
acceptance items in `TODO.md` have named owners and evidence.
