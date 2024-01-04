#syntax=docker/dockerfile:1.4

# Versions
FROM dunglas/frankenphp:latest-alpine AS frankenphp_upstream
FROM composer/composer:2-bin AS composer_upstream


# BaseFrankenPHP Dockerfile
FROM frankenphp_upstream AS frankenphp_base

ARG APP_ENV

# persistent / runtime deps
# hadolint ignore=DL3018
RUN apk add --no-cache \
    acl \
    file \
    gettext \
    nss-tools \
    openssl \
    libssh2-dev \
    mariadb-client \
    mariadb-connector-c \
    postgresql-client \
    sshpass

RUN set -eux; \
    install-php-extensions \
        apcu \
        intl \
        ssh2 \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        zip

###> recipes ###
###< recipes ###

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV APP_ENV=$APP_ENV

COPY --link frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer_upstream --link /composer /usr/bin/composer
COPY --link frankenphp/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

WORKDIR /app


# Prod Dockerfile
FROM frankenphp_base AS frankenphp_prod

ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--resume", "--environ"]

WORKDIR /app


# Cron Dockerfile
FROM frankenphp_base AS frankenphp_cron

RUN apk add --no-cache \
    logrotate

COPY --link frankenphp/logrotate.conf /etc/logrotate.d/

WORKDIR /app

CMD ["crond", "-l", "0", "-d", "0", "-f"]