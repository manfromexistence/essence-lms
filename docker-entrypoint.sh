#!/bin/sh
set -e

# Ensure the SQLite file exists and is writable by the web server
mkdir -p database
touch database/database.sqlite
chown -R www-data:www-data database 2>/dev/null || true

# Create the public/storage symlink so uploaded files (course images, logos,
# favicons) stored on the "public" disk are reachable at /storage/...
# Idempotent: artisan fails harmlessly if the link already exists.
php artisan storage:link || true

# Apply migrations (fast when already applied)
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

# Seed only when the database is empty (first boot / fresh deploy)
# Use Laravel's configured connection instead of opening a hard-coded SQLite
# database. This works with SQLite, MySQL, and PostgreSQL alike.
COUNT=$(php artisan tinker --execute='echo (int) \App\Models\User::count();' 2>/dev/null || echo 0)

if [ "$COUNT" = "0" ]; then
    echo "Fresh database - running seeders..."
    php artisan db:seed --force
else
    echo "Database already seeded - skipping db:seed"
fi

# Keep portal verification accounts present on every boot without changing
# passwords or touching user-created students/admissions.
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=DefaultRoleAccountsSeeder --force

php artisan optimize

exec apache2-foreground
