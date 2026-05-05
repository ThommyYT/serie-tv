FROM php:8.2-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apt-get update && apt-get install -y \
    curl \
    libxml2-dev \
    unzip \
    git

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
