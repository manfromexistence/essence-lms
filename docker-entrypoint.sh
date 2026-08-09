#!/bin/sh
set -e

# Ensure the SQLite file exists and is writable by the web server
mkdir -p database
touch database/database.sqlite
chown -R www-data:www-data database 2>/dev/null || true

# Apply migrations (fast when already applied)
php artisan migrate --force

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
