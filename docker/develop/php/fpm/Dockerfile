FROM php:8.3-fpm-alpine

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/install-php-extensions

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions intl pdo_pgsql zip redis amqp sysvsem bcmath opcache xdebug

WORKDIR /app
