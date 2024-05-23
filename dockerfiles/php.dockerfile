FROM php:8.3.7-fpm-alpine

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Instalando extension php
RUN apk add --no-cache mysql-client msmtp perl wget procps shadow libzip libpng libjpeg-turbo libwebp freetype icu

RUN apk add --no-cache --virtual build-essentials \
    icu-dev icu-libs zlib-dev g++ make automake autoconf libzip-dev \
    libpng-dev libwebp-dev libjpeg-turbo-dev freetype-dev && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd && \
    docker-php-ext-install mysqli && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-install intl && \
    docker-php-ext-install bcmath && \
    docker-php-ext-install opcache && \
    docker-php-ext-install exif && \
    docker-php-ext-install zip && \
    apk del build-essentials && rm -rf /usr/src/php*

# enabel extension
RUN docker-php-ext-enable gd && \
    docker-php-ext-enable mysqli && \
    docker-php-ext-enable pdo_mysql && \
    docker-php-ext-enable intl && \
    docker-php-ext-enable bcmath && \
    docker-php-ext-enable opcache && \
    docker-php-ext-enable exif && \
    docker-php-ext-enable zip 

# install docker-cli
RUN apk add --no-cache docker-cli

# install git
RUN apk add --no-cache git

RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

RUN chown -R laravel /var/www

# copy nginx cli
COPY ./dockerfiles/nginx-cli.sh /usr/local/bin/nginx

RUN chmod a+x /usr/local/bin/nginx

RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet

RUN mv composer.phar /usr/local/bin/composer

USER laravel