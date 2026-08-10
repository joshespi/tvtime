#!/bin/bash
set -e

cd /var/www/html

# Cleared up front so the healthcheck (which just checks for this file) can't
# read a marker left over from a prior successful boot and report healthy
# before this run's composer install/migrate have actually finished.
rm -f storage/framework/migrated

# The ./src bind mount always lands here root:root, no matter what the image
# sets up at build time — php-fpm's workers run as www-data (see
# zzz-laravel.conf), so without this every write under storage/ and
# bootstrap/cache/ (view cache, logs, framework cache) fails. queue/scheduler
# share this same host volume, so fixing it here covers them too.
echo "[deploy] fixing storage/bootstrap permissions for www-data"
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} +
find storage bootstrap/cache -type f -exec chmod 664 {} +

if [ "${APP_ENV}" = "production" ]; then
    echo "[deploy] composer install (production — no dev deps)"
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
else
    echo "[deploy] composer install (dev deps included — APP_ENV=${APP_ENV:-unset})"
    composer install --optimize-autoloader --no-interaction --quiet
fi

echo "[deploy] migrate"
php artisan migrate --force

echo "[deploy] optimize:clear"
php artisan optimize:clear

# Caching config/routes is a production-only optimisation, and actively harmful
# in dev: a cached config freezes .env changes until the next boot, and it wins
# over phpunit.xml's forced env vars — so APP_ENV stays "local" during
# `php artisan test` and every POST test fails with a confusing 419/CSRF error.
if [ "${APP_ENV}" = "production" ]; then
    echo "[deploy] optimize"
    php artisan optimize
else
    echo "[deploy] skipping optimize (APP_ENV=${APP_ENV:-unset}) — caches stay cleared for dev"
fi

# Lets queue/scheduler containers (which share this volume) know it's safe to
# start — they wait on this instead of just vendor/autoload.php, since that
# exists before migrate has created tables like `cache` that they need.
touch storage/framework/migrated

echo "[deploy] starting php-fpm"
exec php-fpm
