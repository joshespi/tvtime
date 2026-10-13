FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    mariadb-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node lives in this image (not a separate one-shot container) so assets build
# with `docker exec tv-tracker-app npm run build`, same as artisan/composer.
# trixie-slim matches php:8.5-fpm's Debian base, so the glibc node binary and
# the native vite/rolldown packages npm installs run here unchanged.
COPY --from=node:24.18-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24.18-trixie-slim /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Our own files last — they change most often, so edits don't invalidate the
# layers above.
COPY docker/php/zzz-laravel.conf /usr/local/etc/php-fpm.d/zzz-laravel.conf
COPY docker/php/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/app-bootstrap.sh /usr/local/bin/app-bootstrap
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh /usr/local/bin/app-bootstrap

WORKDIR /var/www/html

ENTRYPOINT ["/entrypoint.sh"]
