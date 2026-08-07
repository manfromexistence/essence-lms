#!/bin/sh
set -e

# Ensure the SQLite file exists and is writable by the web server
mkdir -p database
touch database/database.sqlite
chown -R www-data:www-data database 2>/dev/null || true

# Apply migrations (fast when already applied)
php artisan migrate --force

# Seed only when the database is empty (first boot / fresh deploy)
COUNT=$(php -r 'require "vendor/autoload.php"; $db = new PDO("sqlite:" . (getenv("DB_DATABASE") ?: "database/database.sqlite")); echo (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();')

if [ "$COUNT" = "0" ]; then
    echo "Fresh database - running seeders..."
    php artisan db:seed --force
else
    echo "Database already seeded - skipping db:seed"
fi

php artisan optimize

exec apache2-foreground
