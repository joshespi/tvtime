#!/bin/bash
# Brings the mounted ./src up to date with what the code expects: PHP deps,
# schema, front-end assets, framework caches. Idempotent and cheap when nothing
# changed. Runs from entrypoint.sh on every container start, and from
# script/deploy.sh (via `docker compose exec app app-bootstrap`) when a deploy
# doesn't recreate the container.
set -e

cd /var/www/html

if [ "${APP_ENV}" = "production" ]; then
    echo "[deploy] composer install (production — no dev deps)"
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
else
    echo "[deploy] composer install (dev deps included — APP_ENV=${APP_ENV:-unset})"
    composer install --optimize-autoloader --no-interaction --quiet
fi

echo "[deploy] migrate"
php artisan migrate --force

# public/build is gitignored, so the server compiles its own — on every run,
# because Tailwind only emits classes it finds in the Blade views and any view
# change can add one. node_modules is only reinstalled when the lockfile or the
# image's Node changes (a different Node can need different native
# vite/rolldown binaries than the ones already installed).
#
# Vite empties its outDir before writing, and a deploy runs this against a live
# php-fpm — building in place would 500 every page (no manifest) for the length
# of the build. So it builds beside the live copy and swaps it in at the end.
#
# Every step carries its own `|| return 1`: set -e doesn't apply inside a
# function called as an `if` condition.
build_assets() {
    local deps_stamp
    deps_stamp="$(node -v) $(sha1sum package-lock.json | cut -d' ' -f1)" || return 1
    if [ "$(cat node_modules/.deps-stamp 2>/dev/null)" != "$deps_stamp" ]; then
        echo "[deploy] npm ci"
        npm ci --no-audit --no-fund --loglevel=error || return 1
        echo "$deps_stamp" > node_modules/.deps-stamp || return 1
    fi

    echo "[deploy] npm run build"
    rm -rf public/build.next public/build.prev
    npm run build --silent -- --outDir public/build.next || return 1
    if [ -d public/build ]; then mv public/build public/build.prev || return 1; fi
    mv public/build.next public/build || return 1
    rm -rf public/build.prev
}

# On boot (entrypoint.sh passes --boot) a failed build — say the npm registry is
# unreachable after a host reboot — keeps the previous build rather than taking
# the whole site down over assets it already has. A deploy still fails loudly.
if ! build_assets; then
    if [ "${1:-}" = "--boot" ] && [ -f public/build/manifest.json ]; then
        echo "[deploy] WARNING: asset build failed — serving the previous build"
    else
        exit 1
    fi
fi

# In production `optimize` re-caches config/routes/events/views over whatever
# is there. Dev clears them instead: a cached config freezes .env changes until
# the next boot, and it wins over phpunit.xml's forced env vars — so APP_ENV
# stays "local" during `php artisan test` and every POST test fails with a
# confusing 419/CSRF error. Production deliberately skips optimize:clear: its
# cache:clear would also wipe the cached TMDB responses on every deploy.
if [ "${APP_ENV}" = "production" ]; then
    echo "[deploy] optimize"
    php artisan optimize
else
    echo "[deploy] optimize:clear (APP_ENV=${APP_ENV:-unset})"
    php artisan optimize:clear
fi
