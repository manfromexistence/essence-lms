# Dhaka IT Institute LMS

Production Laravel 12 LMS for **Dhaka IT Institute** — practical IT & freelancing training (Mirpur-10, Dhaka). Supports online/offline courses, admissions, batches, teachers, students, attendance, exams (MCQ/CQ), video progress, bKash-verified enrollments, invoices, certificates, forums, and admin CMS for homepage content.

Live: **https://portal.dhakaitinstitute.com** (cPanel subdomain `portal.dhakaitinstitute.com` → `/home/dhakaiti/portal.dhakaitinstitute.com`)

---

## Tech Stack

- **Backend:** Laravel 12, PHP **8.2+** (local 8.5.8, cPanel 8.5.9), Composer 2.10
- **Frontend:** Blade + Vite, Tailwind. Alpine.js, Chart.js, SortableJS, Fabric.js and Font Awesome are **bundled from npm** (no external CDN at runtime); Chart/Sortable/Fabric ship in a separate `admin.js` entry so the public site does not download them.
- **DB:** SQLite locally (`database/database.sqlite`), **MySQL/MariaDB** on cPanel (`dhakaiti_portal`)
- **Storage:** `public/images/` for public assets (no `storage:link` needed for homepage), `storage/app/private` for S3/private disks
- **Tooling:** Node 24, npm, Pest/PHPUnit, Pint

## What Changed Recently (2026-09)

- **Local images:** All Unsplash URLs removed. 35 `imgi_*` WordPress slugs renamed to clean names (`team-*.jpg`, `course-*.jpg`, `slide-*.png`, `homepage-banner.png`, `page-banner.png` etc.) under `public/images/`. Verified 0 Unsplash references in DB/views/tests.
- **Image helper:** Global `media_url($path)` in `app/Support/helpers.php` (autoloaded via `composer.json`) replaces `asset('storage/' . $x)` and `Str::startsWith($x,'http')` checks. Used in `Course`, `Service`, `Teacher` models and ~14 dashboard/teacher blades.
- **Header:** Slimmed to `py-2` (`components/header.blade.php:8`), nav `py-1.5 text-sm`, buttons `h-8`. Logo `h-16 md:h-20` (`header.blade.php:18`).
- **Footer:** Logo enlarged to `h-16 md:h-20` (`components/footer.blade.php:11`).
- **Hero carousel:** 3 image-only slides (no text overlays) — `page-banner.png`, `slide-classroom.png`, `slide-learning.jpg` (`welcome.blade.php:46-66`). Controls: dots + arrows, 5s autoplay, keyboard, pointer drag/swipe (crossfade `fadeIn 0.55s`).
- **About section:** Image now full-width stacked above text (`welcome.blade.php:426-431`, `p-6 grid gap-6`, `h-auto`) instead of narrow 50% column.
- **Htaccess:** `public/.htaccess` Windows `upload_tmp_dir "C:\..."` block removed; root `.htaccess` rewrites to `public/` for subdomain docroot. PHP limits now via cPanel MultiPHP INI Editor.
- **Deploy artifacts:** `portal-dhakaitinstitute-com-fast.zip` (128.6 MB, 20k files, `vendor/` included) and `portal-dhakaitinstitute-com.tar.zst` (117 MB, zstd) in `G:\Temp\UserTemp\opencode\`.

---

## Requirements

- PHP 8.2+ with `fileinfo`, `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `curl`
- Composer 2, Node + npm
- MySQL 5.7+/MariaDB 10+ (production) or SQLite (local dev)
- SMTP (optional), S3-compatible disk for private files (optional)

## Local Setup

```bash
composer install
npm install
copy .env.example .env   # or copy .env.cpanel for MySQL local
php artisan key:generate
# SQLite (default .env: DB_CONNECTION=sqlite)
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Admin: set `INITIAL_ADMIN_EMAIL` + a 16+ character `INITIAL_ADMIN_PASSWORD` in `.env` before `migrate --seed`, sign in, then rotate it. Never commit real credentials.

**MySQL locally:** set in `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms
DB_USERNAME=root
DB_PASSWORD=
```

Quick checks:
```bash
php artisan test
composer audit --locked
npm run build
php artisan optimize
```

## cPanel Deployment (portal.dhakaitinstitute.com)

Subdomain docroot: `/home/dhakaiti/portal.dhakaitinstitute.com` (Laravel root, `.htaccess` → `public/`). PHP 8.5 via **MultiPHP Manager**.

1. **DB:** cPanel → MySQL Databases → create `dhakaiti_portal` + user (ALL PRIVILEGES). Use a fresh strong password — never reuse the one from git history.
2. **Upload:** File Manager → `portal.dhakaitinstitute.com` → Upload `portal-dhakaitinstitute-com-fast.zip` (with `vendor/`) → **Extract** → delete zip. (Alternative `portal.tar.zst`: `tar --zstd -xf portal.tar.zst --strip-components=1`)
3. **Env:** create `.env` on the server (never commit it). Template:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://portal.dhakaitinstitute.com
   APP_KEY=<generate: php artisan key:generate --show>
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=dhakaiti_portal
   DB_USERNAME=dhakaiti_portal
   DB_PASSWORD="<strong unique password>"
   ```
   Ensure **no `DB_URL`** line (it overrides `DB_CONNECTION` to `pgsql` → `could not find driver`).
4. **Extensions:** MultiPHP INI Editor → `portal.dhakaitinstitute.com` → Editor Mode:
   ```ini
   extension=fileinfo.so
   extension=pdo_mysql.so
   upload_max_filesize = 30M
   post_max_size = 32M
   memory_limit = 256M
   max_execution_time = 300
   max_input_vars = 3000
   ```
   Also **Select PHP Version** → `8.5` → tick `fileinfo`, `pdo_mysql` → Save. Verify `php -m | grep -E "fileinfo|pdo_mysql"`.
5. **Composer (if needed):** Terminal at docroot:
   ```bash
   mkdir -p $HOME/bin
   curl -sS https://getcomposer.org/installer -o composer-setup.php
   php composer-setup.php --install-dir=$HOME/bin --filename=composer
   ~/bin/composer --version  # or php ~/bin/composer
   # fix alias if `composer` points to /usr/local/bin/composer:
   unalias composer; alias composer="$HOME/bin/composer"
   ```
   With the fast zip you can **skip** `composer install` (vendor included). If reinstalling:
   ```bash
   php ~/bin/composer install --no-dev --optimize-autoloader --no-interaction
   # if fileinfo still disabled: --ignore-platform-req=ext-fileinfo
   ```
6. **Migrate & optimize:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   php artisan optimize:clear; php artisan config:clear
   php artisan migrate --seed --force
   php artisan optimize
   php artisan migrate:status
   ```
   `php artisan storage:link` is optional (homepage uses `public/images/`, error `public/storage link already exists` is harmless).

## Project Structure Highlights

```
public/images/          # all local images (page-banner.png, slide-*.png/jpg, team-*, course-*)
public/build/           # Vite compiled assets (manifest.json)
app/Support/helpers.php # media_url() helper
resources/views/welcome.blade.php  # hero (3 slides), banner, courses, about
resources/views/components/header.blade.php  # slim header + logo
resources/views/components/footer.blade.php  # enlarged logo
database/seeders/       # PageSeeder, CourseSeeder, TeamAndServicesSeeder etc.
.htaccess               # → public/
public/.htaccess        # Laravel front-controller only (no Windows paths)
```

## Verification

```bash
php artisan test
composer audit --locked
npm audit --omit=dev
npm run build
php artisan optimize
# cPanel after deploy:
curl -I https://portal.dhakaitinstitute.com
php artisan migrate:status
```

## Render deployment

The service is defined in `render.yaml` (Docker runtime, health check `/up`).
A push to `main` auto-deploys. The container seeds the database only when it is
empty (see `docker-entrypoint.sh`), so redeploys never overwrite live data.

### Reset the seeded (SQLite) database

On the free plan the SQLite file is on an ephemeral filesystem, and the
entrypoint skips seeding once users exist. To rebuild it from the current
seeders after a change to the seed data:

1. Render dashboard → the web service → **Environment**.
2. Add `FORCE_RESEED` = `true`.
3. Trigger **Manual Deploy → Deploy latest commit** (or push a commit).
4. Once the deploy is healthy, **delete the `FORCE_RESEED` variable** so the
   next boot does not wipe the database again.

`FORCE_RESEED` is intentionally ignored unless `DB_CONNECTION=sqlite`, so it can
never destroy a managed MySQL/PostgreSQL database.

### Demo logins after a fresh seed

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@gmail.com` | `password` |
| Teacher | `teacher@gmail.com` | `password` |
| Student | `student@gmail.com` | `password` |

For a real deployment, set `INITIAL_ADMIN_EMAIL` + a 16+ character
`INITIAL_ADMIN_PASSWORD` (and, optionally, the `DEFAULT_*_EMAIL` /
`DEFAULT_*_PASSWORD` pairs) instead of relying on the demo accounts above.

## Troubleshooting

- `ext-fileinfo missing` / `league/flysystem requires ext-fileinfo` → enable `fileinfo` in Select PHP Version + `extension=fileinfo.so` in php.ini.
- `could not find driver (Connection: pgsql, ...)` → `DB_CONNECTION` is `pgsql` or `DB_URL` overrides it. `sed -i 's/^DB_URL=/#DB_URL=/' .env` then `php artisan optimize:clear; php artisan config:clear`.
- `bash: /usr/local/bin/composer: No such file` → `hash -r; unalias composer; ~/bin/composer --version`.
- `The defined install dir (/home/dhakaiti/bin) does not exist` → `mkdir -p $HOME/bin` before install.
- `public/storage link already exists` → ignore (not used for `public/images`).

## Release Governance

- `CHANGELOG.md` — shipped work
- `TODO.md` — client launch gate
- `SECURITY.md` — vulnerability reporting

No release is launch-ready until `TODO.md` items have named owners and evidence.
