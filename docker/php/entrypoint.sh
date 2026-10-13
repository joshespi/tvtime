#!/bin/bash
set -e

cd /var/www/html

# depends_on/healthcheck ordering only applies to `docker compose up` — after a
# host reboot, Docker's own restart manager brings every container back
# independently and ignores it, so app can start racing a db that's still
# doing InnoDB crash recovery from an unclean shutdown. Wait here instead of
# letting migrate fail and relying on Docker's (slow, backing-off) container
# restart to eventually retry the whole entrypoint.
echo "[deploy] waiting for db..."
until mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    sleep 2
done

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

app-bootstrap --boot

# Lets queue/scheduler containers (which share this volume) know it's safe to
# start — they wait on this instead of just vendor/autoload.php, since that
# exists before migrate has created tables like `cache` that they need. Also
# what the compose healthcheck (and so `up --wait` in script/deploy.sh) checks.
touch storage/framework/migrated

echo "[deploy] starting php-fpm"
exec php-fpm
