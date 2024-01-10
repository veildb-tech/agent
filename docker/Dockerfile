#syntax=docker/dockerfile:1.4

# Versions
FROM composer/composer:2-bin AS composer_upstream


# BaseFrankenPHP Dockerfile
FROM php:8.3-apache AS server_base

ARG APP_ENV
ARG GID
ARG UID

RUN groupadd -f -g $GID app \
    && useradd -g $GID -u $UID -s /bin/bash app

# persistent / runtime deps
# hadolint ignore=DL3018
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libssh2-1-dev \
    libssh2-1 \
    libicu-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    unzip \
    curl \
    cron \
    libapache2-mod-xsendfile \
    libapache2-mod-fcgid \
    libmariadb3 \
    libmariadb-dev \
    libpq-dev \
    mariadb-client \
    postgresql-client \
    sshpass

RUN a2enmod ssl && a2enmod rewrite && a2enmod proxy && a2enmod proxy_http
RUN mkdir -p /etc/apache2/certs && mkdir -p /app && mkdir -p /home/app/.composer/cache
RUN chmod 1777 /home/app/.composer/cache

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install \
        intl \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip

# Install APCu
RUN pecl install apcu \
  && docker-php-ext-enable apcu

# Install ssh2
RUN pecl install ssh2 \
  && docker-php-ext-enable ssh2

ENV APP_ENV=$APP_ENV

COPY --link server/conf.d/app.ini "$PHP_INI_DIR/php.ini"
COPY --link --chmod=755 server/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

COPY --from=composer_upstream --link /composer /usr/bin/composer


# Prod Dockerfile
FROM server_base AS server_prod

COPY server/000-default.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /app

EXPOSE 443 80

USER app:app

ENTRYPOINT ["docker-entrypoint"]

CMD ["apache2ctl", "-D", "FOREGROUND"]


# Cron Dockerfile
FROM server_base AS server_cron

RUN chmod u+s /usr/sbin/cron

WORKDIR /app

USER app:app

CMD ["cron", "-f"]