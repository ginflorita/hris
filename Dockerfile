# Containerized alternative to the bare-VPS deploy/ configs (deploy/
# nginx, deploy/php-fpm, deploy/supervisor) -- same PHP 8.3 + Nginx +
# PHP-FPM + Supervisor stack CLAUDE.md's own Stack section already
# commits to, just packaged as one image for platforms that deploy
# containers instead of a hand-provisioned server. Nothing here changes
# what the app itself requires; MySQL 8+ and Redis are still expected
# as separate services (a managed database/cache addon, or sibling
# containers), never baked into this image -- CLAUDE.md's own "no
# starter kit magic, no fallback pretending to be production" stance
# extends to this file too.
#
# Three build stages: build frontend assets, vendor PHP dependencies,
# then assemble the runtime image from both without carrying Node or
# Composer themselves into it.
#
# Not build-verified in the session that wrote it -- same honesty
# DEPLOYMENT.md already applies to deploy/nginx and deploy/php-fpm
# ("no nginx/php-fpm binary in the environment this was written in").
# Here the gap is narrower and the cause is known: this session's
# sandbox has Docker installed and its daemon runs, but the egress
# proxy denies Docker Hub itself (confirmed via the proxy's own status
# endpoint -- an explicit 403 policy denial on
# production.cloudfront.docker.com, not a fixable client/cert issue),
# so `docker build` gets as far as resolving all three FROM images in
# parallel and no further. That much did confirm the Dockerfile parses
# and stages correctly. The real, unblocked build-and-run test happens
# the first time .github/workflows/docker-publish.yml runs on GitHub's
# own infrastructure -- watch that run before trusting this image.

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY routes/ routes/
COPY artisan ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

FROM php:8.3-fpm-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip pcntl bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove libzip-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/hris

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY docker/nginx.conf /etc/nginx/conf.d/hris.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/hris.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN rm -f /etc/nginx/sites-enabled/default \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/hris

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["web"]
