FROM php:8.4-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    libzip-dev \
    icu-dev \
    libpng-dev \
    postgresql-dev \
    oniguruma-dev

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    zip \
    gd \
    mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application source
COPY . .

# Ensure var/ exists with correct ownership for cache/logs
RUN mkdir -p var && chown -R www-data:www-data var

EXPOSE 9000

CMD ["php-fpm"]
