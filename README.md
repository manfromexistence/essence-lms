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

Render runs this app as a **Docker web service**. The deployment artefacts are
`Dockerfile`, `docker-apache-config.conf` and `docker-entrypoint.sh`.

- **Dashboard:** <https://dashboard.render.com/web/srv-d9l05lfavr4c739q5nig>
- **Service ID:** `srv-d9l05lfavr4c739q5nig` · **name:** `dhaka-it-institute`
- **URL:** <https://dhaka-it-institute.onrender.com>
- **Repo:** `github.com/manfromexistence/essence-lms` · **branch:** `main`
- **Runtime:** `docker` (Dockerfile at repo root) · **plan:** `free` · **region:** `oregon`
- **Auto-deploy:** on, triggered by any push to `main`

Two builds run per deploy: Composer and npm run in a `build` stage, the runtime
image is `php:8.3-apache` with the compiled artefacts copied in. `ca-certificates`
is installed explicitly so outbound HTTPS (Brevo, and the media host) can verify
TLS.

### ⚠️ The database is on an ephemeral filesystem

**This service has no persistent disk attached.** Verified against the Render API:
`GET /v1/disks` returns `[]`.

The database is SQLite at `/var/www/html/database/database.sqlite`, which lives
inside the container. Render gives every deploy a brand-new container with a new
empty filesystem, so:

> **Every deploy destroys all data and rebuilds an empty database from the
> seeders.**

This has already happened on every deploy in this service's history. It is fine
for a demo, and it is *not* fine once real admissions exist.

Note that `docker-entrypoint.sh` only skips re-seeding when users already exist.
That check protects data *within one container's lifetime*; it cannot protect
data across deploys, because a new container has no users to find. Deploys on
this service are therefore not data-preserving.

**Before accepting real students, do one of these:**

1. **Attach a persistent disk** (recommended). Render dashboard → the service →
   *Disks* → add a disk mounted at `/var/www/html/database`, sized ≥ 1 GB. Set
   `DB_DATABASE=/var/www/html/database/database.sqlite`. Existing data will not
   migrate — it is already gone.
2. **Move to a managed database.** Create a Render PostgreSQL instance and set
   `DB_CONNECTION=pgsql` plus the `DB_*` values it gives you. This is the option
   to take if the institute expects more than light use.

### Media storage

Images, course video, exam screenshots, payment proofs and report exports are
hosted on **Catbox** (`catbox.moe`) rather than on the container filesystem — see
`config/media.php` and `app/Storage/`. This is what makes large lecture video
practical on a shared host, and it also means uploads survive a redeploy.

Two consequences to be aware of before real admissions:

- Every hosted object is **world-readable and permanent**. A URL that leaks stays
  public. There is no signed-URL equivalent on this host.
- Files **cannot be deleted**. Setting `CATBOX_USERHASH` to a Catbox account key
  is what would enable real deletion; without it, removing a record only stops
  the portal pointing at the object.

If uploads must expire — payment proofs in particular — they need hosting the
application controls, not a public anonymous host.

### Environment variables

Set these in the dashboard under **Environment**. Only keys that exist on the
live service are listed as such; the rest come from `render.yaml`.

| Key | Value | Notes |
|---|---|---|
| `APP_KEY` | generated | Render generates it. **Rotating it logs everyone out.** |
| `APP_URL` | `https://dhaka-it-institute.onrender.com` | |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | |
| `DB_CONNECTION` | `sqlite` | Change with the database move above |
| `DB_DATABASE` | `/var/www/html/database/database.sqlite` | Point at the disk once attached |
| `SESSION_DRIVER` | `file` | |
| `CACHE_STORE` | `file` | |
| `QUEUE_CONNECTION` | `sync` | Jobs run in-request; see below |
| `LOG_CHANNEL` | `stderr` | Logs surface in the Render dashboard |
| `MAIL_MAILER` | `log` | Reset links land in the log, not an inbox |
| `TRUSTED_PROXIES` | `*` | **Required.** Without it Laravel emits `http://` asset URLs and the dashboard renders unstyled |
| `INITIAL_ADMIN_EMAIL` / `INITIAL_ADMIN_PASSWORD` | *your values* | Set these; do not rely on the demo logins |
| `BREVO_API_KEY` | *secret* | Transactional email. **See the warning below** |
| `BREVO_SENDER_EMAIL` / `BREVO_SENDER_NAME` | sender identity | Must be a verified Brevo sender |

Additional keys introduced with the Catbox media host:

| Key | Default | Notes |
|---|---|---|
| `FILESYSTEM_DISK` / `PRIVATE_FILESYSTEM_DISK` | `catbox` | |
| `CATBOX_API_URL` | `https://catbox.moe/user/api.php` | |
| `CATBOX_BASE_URL` | `https://files.catbox.moe` | |
| `CATBOX_USERHASH` | *(empty)* | Empty = anonymous. A Catbox account key enables deletes |
| `CATBOX_USER_AGENT` | `Laravel-LMS` | |
| `CATBOX_TIMEOUT` | `300` | Must cover a full-size video upload; the host cannot resume a partial transfer |
| `CATBOX_CONNECT_TIMEOUT` | `15` | |
| `CATBOX_READ_TIMEOUT` | `300` | |
| `MEDIA_MAX_UPLOAD_KB` | `204800` | Catbox's ceiling is 200 MB |

> **Queue caveat.** `QUEUE_CONNECTION=sync` means every queued job runs inside the
> web request. `GenerateReportExportJob` alone can take minutes, which will hit
> the request timeout on a large report. Use a Render background worker plus
> `QUEUE_CONNECTION=database` before relying on scheduled exports.

### 🔴 Secrets currently in git

`config/mail.php` contains a **live Brevo API key** as a literal fallback value,
committed in `ca58f33`. Anything in git is in the deploy history and in every
clone of the repository.

- Rotate that key in the Brevo dashboard now.
- Replace the literal with `env('BREVO_API_KEY')` and set the real value as a
  Render environment variable.
- Never commit the Render API key either; use it from your shell only.

The `render.yaml` comment claiming the key is "deliberately NOT stored here"
became untrue when the fallback was added — `config/mail.php` and
`render.yaml` must be read together.

### `render.yaml` is reference, not the source of truth

`render.yaml` describes the intended configuration, but **the live service was
not created from it**, so Render is not applying it. Two concrete pieces of
drift, verified via the API:

- `render.yaml` declares `healthCheckPath: /up`; the live service has
  `healthCheckPath: ""` (**no health check**). `/up` does return `200`, so set
  it in the dashboard — it makes a bad deploy fail fast instead of looking
  healthy.
- `render.yaml` declares `BREVO_API_KEY`, `BREVO_SENDER_EMAIL` and
  `BREVO_SENDER_NAME`; **none of the three exist on the live service**, which is
  why the hardcoded fallback in `config/mail.php` had to be added to make email
  work at all.

Either reconcile the two, or delete `render.yaml` so it cannot mislead the next
reader.

### Boot sequence and why it is ordered that way

`docker-entrypoint.sh` starts **Apache first**, then runs migrations and
seeding. This is deliberate and must not be reordered. Render fails a deploy that
has not opened a port within ~60s; migrations on a fresh database cost ~30s and
the full seed ~20s, so starting the web server last reliably timed out the port
scan. Render then kept serving the *previous* build, which looks exactly like
"my push never deployed".

Because `/up` does not touch the database, the health check passes as soon as the
port is bound. During the remaining boot window, database-backed pages may error
briefly — a much better trade than a deploy that never lands.

### Rebuilding the seeded database

On the ephemeral filesystem the database is already empty at each boot, so the
entrypoint seeds automatically. To force a rebuild mid-life (for example after
changing seed data):

1. Render dashboard → the service → **Environment** → add `FORCE_RESEED=true`.
2. **Manual Deploy → Deploy latest commit**.
3. When healthy, **delete `FORCE_RESEED`** so a later boot does not wipe it.

`FORCE_RESEED` is ignored unless `DB_CONNECTION=sqlite`, so it cannot destroy a
managed database by accident.

### Demo logins after a fresh seed

Seeded on every boot while the service is diskless. **Change these before the
service is reachable by anyone but you.**

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@gmail.com` | `password` |
| Teacher | `teacher@gmail.com` | `password` |
| Student | `student@gmail.com` | `password` |

### Deploy checklist

```bash
php artisan test
vendor/bin/pint --test app/Storage app/Services/QrCodeService.php   # or accept the repo-wide backlog
git push origin main          # auto-deploys
```

Then in the dashboard: confirm the deploy reaches *Live*, check `/up` returns
`200`, and remember that **student and admission data will not survive it**.

### Troubleshooting

- **Dashboard unstyled / mixed content** → `TRUSTED_PROXIES=*` is missing.
- **Deploy "succeeded" but the old build is still serving** → the container was
  killed at the port scan. Check the deploy log for "Port scan timeout"; this is
  the boot-order problem described above.
- **`Port scan timeout reached, no open ports detected`** → Apache is starting
  last. Restore the entrypoint ordering.
- **Uploads fail with "Could not reach the media host"** → outbound HTTPS from
  the container. Confirm `ca-certificates` is installed in the Dockerfile and
  raise `CATBOX_TIMEOUT` for large files.
- **Every video upload fails** → Catbox's ceiling is 200 MB. `MEDIA_MAX_UPLOAD_KB`
  must not exceed `204800`.
- **Emails never arrive** → check `BREVO_API_KEY` is set as an environment
  variable and that `BREVO_SENDER_EMAIL` is a verified sender. Reset-password
  mail bypasses Brevo entirely and follows `MAIL_MAILER`.
- **"The defined install dir does not exist"** → cPanel only; see the cPanel
  section above.

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
