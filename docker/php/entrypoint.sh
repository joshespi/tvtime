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

app-bootstrap --boot

# Lets queue/scheduler containers (which share this volume) know it's safe to
# start — they wait on this instead of just vendor/autoload.php, since that
# exists before migrate has created tables like `cache` that they need. Also
# what the compose healthcheck (and so `up --wait` in script/deploy.sh) checks.
touch storage/framework/migrated

echo "[deploy] starting php-fpm"
exec php-fpm
