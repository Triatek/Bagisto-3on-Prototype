FROM php:8.2.29-cli

# Set working directory
WORKDIR /var/www

# Copy composer.lock and composer.json
COPY composer.lock composer.json /var/www/

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update \
    && apt-get install -y --no-install-recommends openssl libssl-dev libcurl4-openssl-dev \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions gd xdebug

RUN apt-get update

# Install useful tools
RUN apt-get -y install apt-utils nano wget dialog vim

# Install important libraries
RUN apt-get -y install --fix-missing \
    apt-utils \
    build-essential \
    git \
    curl \
    libcurl4 \
    libcurl4-openssl-dev \
    zlib1g-dev \
    libzip-dev \
    zip \
    libbz2-dev \
    locales \
    libmcrypt-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev

RUN docker-php-ext-install \
    exif \
    pcntl \
    bcmath \
    ctype \
    curl \
    iconv \
    xml \
    soap \
    pcntl \
    mbstring \
    bz2 \
    zip \
    intl \
    calendar

# Copy local.ini explicitly
COPY ./php/local.ini /usr/local/etc/php/php.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

# Expose port 9000 and start php-fpm server
# EXPOSE 9000

# CMD [ "php", "artisan", "serve", "--host=0.0.0.0" ]
CMD bash -c "composer install -n --optimize-autoloader && php artisan serve --host=0.0.0.0"