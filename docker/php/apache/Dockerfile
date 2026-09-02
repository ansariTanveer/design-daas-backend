ARG PHP_VERSION
ARG COMPOSER_VERSION

FROM composer:${COMPOSER_VERSION} as composer

FROM php:${PHP_VERSION} as base

ARG YACRON_VERSION

#
# Common environment setup
#
RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get -y install \
        python3 \
        python3-pip \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
        libxslt-dev \
        libpng-dev \
        libc-client-dev \
        libkrb5-dev \
        libldap2-dev \
        libgmp-dev \
        $PHPIZE_DEPS && \
    apt-get clean && \
    find /var/lib/apt/lists/ -type f -delete && \
    docker-php-ext-configure \
        imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        intl \
        zip \
        soap \
        xsl \
        gd \
        imap \
        ldap \
        bcmath \
        gmp  \
        pcntl && \
    pip --no-input --no-cache-dir install --break-system-packages \
        yacron==${YACRON_VERSION} && \
    mkdir /project

RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'display_errors = Off' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'log_errors = On' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'default_mimetype =' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'default_charset = UTF-8' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'input_encoding = UTF-8' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'output_encoding = UTF-8' >> /usr/local/etc/php/conf.d/local.ini && \
    echo 'internal_encoding = UTF-8' >> /usr/local/etc/php/conf.d/local.ini

ENV HOME=/
WORKDIR /project

#
# Apache configuration
#
RUN sed -ri -e 's!/var/www/html!/project/public!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!/project/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    a2enmod rewrite

#
# Command scripts
#
COPY ./docker/php/apache/docker-application \
    ./docker/php/apache/docker-doctrine \
    ./docker/php/apache/docker-migrations \
    ./docker/php/apache/docker-cron \
    ./docker/php/apache/docker-mail-queue \
    ./docker/php/apache/docker-entrypoint \
    /usr/local/bin/
RUN chmod +x \
    /usr/local/bin/docker-application \
    /usr/local/bin/docker-doctrine \
    /usr/local/bin/docker-migrations \
    /usr/local/bin/docker-cron \
    /usr/local/bin/docker-mail-queue \
    /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]

#
# Development setup
#
FROM base as development

ARG XDEBUG_VERSION

ENV COMPOSER_CACHE_DIR=/dev/shm/composer-cache/
COPY --from=composer /usr/bin/composer /usr/local/bin/

RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get -y install \
        unzip \
        git && \
    apt-get clean && \
    find /var/lib/apt/lists/ -type f -delete && \
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    pecl install \
        xdebug-${XDEBUG_VERSION} && \
    ln -s /project/var/generated-configs/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

#
# Release setup
#
FROM development as build

COPY ./ /project/
RUN composer install --optimize-autoloader --classmap-authoritative --no-interaction --no-dev
RUN php ./scripts/build-cache.php
RUN php ./scripts/build-version-details.php

FROM base as release

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# original files
COPY --from=build /project/assets/ /project/assets/
COPY --from=build /project/config/ /project/config/
COPY --from=build /project/migrations/ /project/migrations/
COPY --from=build /project/public/ /project/public/
COPY --from=build /project/src/ /project/src/
COPY --from=build /project/application.php /project/application.php
COPY --from=build /project/doctrine.php /project/doctrine.php

# files generated during build
COPY --from=build /project/vendor/ /project/vendor/
COPY --from=build /project/cache.build/ /project/cache/
COPY --from=build /project/version_details.build /project/config/version_details.json
COPY --from=build /project/version_tag.build /project/version_tag.txt
