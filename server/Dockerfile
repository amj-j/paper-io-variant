FROM php:8.2-cli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

RUN apt-get update && apt-get install -y \
procps

WORKDIR /var/www/html

COPY ./src /var/www/html
COPY composer.json composer.lock /var/www/html/
RUN composer install

RUN docker-php-ext-install pcntl