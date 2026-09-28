#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Boot order matters here — do not move Apache to the end again.
#
# Render fails a deploy that has not opened a port within its port-scan
# timeout (~60s). The boot work below costs roughly:
#
#     migrate --force (fresh DB)  ~30s   (85 migrations, fsync-bound)
#     full demo seed              ~20s
#     Role/DefaultRole seeders     ~2s
#     artisan optimize            ~13s
#     ------------------------------------
#     total                       ~65s   on a fast local machine
#
# A free-tier CPU is slower still. With Apache started last the container was
# killed at the port scan ("Port scan timeout reached, no open ports
# detected"), the deploy was marked failed, and Render kept serving the
# PREVIOUS build — which looks exactly like "my push never deployed".
#
# So the web server is started FIRST, before any database work. Laravel's /up
# health route never touches the database, so Render's health check passes as
# soon as the port is open. Migrations and seeding then run while the port is
# already bound; the only cost is that pages needing the database can briefly
# error during that window, which is far better than a deploy that never lands.
# ---------------------------------------------------------------------------

# Ensure the SQLite file exists and is writable by the web server
mkdir -p database
touch database/database.sqlite
chown -R www-data:www-data database 2>/dev/null || true

# Create the public/storage symlink so uploaded files (course images, logos,
# favicons) stored on the "public" disk are reachable at /storage/...
# Idempotent: artisan fails harmlessly if the link already exists.
php artisan storage:link || true

# ---------------------------------------------------------------------------
# Bind the port before the slow work, so the port scan can never time out.
# ---------------------------------------------------------------------------
apache2-foreground &
APACHE_PID=$!

# Forward termination signals so the container shuts down cleanly instead of
# being SIGKILLed after Render's grace period.
trap 'kill -TERM "$APACHE_PID" 2>/dev/null || true' TERM INT

echo "Apache started (pid $APACHE_PID) - continuing boot tasks"

# Apply migrations (slow on a fresh database, hence the ordering above)
php artisan migrate --force

# Optional one-shot reset for ephemeral/demo deploys.
# Set FORCE_RESEED=true on the service to wipe the local SQLite database and
# rebuild it from the seeders on the next boot. This is intentionally opt-in
# (and only ever touches a SQLite file) so a managed MySQL/Postgres database
# can never be destroyed by accident. Remember to unset it afterwards.
if [ "$FORCE_RESEED" = "true" ] && [ "$DB_CONNECTION" = "sqlite" ]; then
    echo "FORCE_RESEED=true - rebuilding SQLite database from seeders..."
    php artisan migrate:fresh --seed --force
fi

# Seed only when the database is empty (first boot / fresh deploy).
# Use a direct query rather than `artisan tinker`: tinker boots PsySH, which
# costs ~4s and can hang when there is no TTY attached.
COUNT=$(php -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo (int) Illuminate\Support\Facades\DB::table("users")->count();
' 2>/dev/null || echo 0)

if [ "$COUNT" = "0" ]; then
    echo "Fresh database - running seeders..."
    php artisan db:seed --force
else
    echo "Database already seeded ($COUNT users) - skipping db:seed"
fi

# Keep portal verification accounts present on every boot without changing
# passwords or touching user-created students/admissions.
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=DefaultRoleAccountsSeeder --force

php artisan optimize

echo "Boot complete - Apache is serving on port 80 (pid $APACHE_PID)"
wait "$APACHE_PID"
