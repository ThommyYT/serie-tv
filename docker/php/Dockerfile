FROM php:8.2-fpm

# PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# tools base
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libzip-dev \
    nodejs \
    npm

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# entrypoint custom
COPY ./entrypoint.sh /entrypoint.sh
# Rimuove i caratteri \r (CRLF) e li trasforma in LF durante la build
RUN sed -i 's/\r$//' /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

