FROM php:8.2-fpm

WORKDIR /var/www

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    zip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libbz2-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# GD extension
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install -j"$(nproc)" \
    bcmath \
    bz2 \
    calendar \
    exif \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo_mysql \
    soap \
    zip

# Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Production PHP configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY ./php/local.ini /usr/local/etc/php/conf.d/99-local.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy application source
COPY . .

# Laravel writable directories
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]