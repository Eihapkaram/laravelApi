FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git curl libicu-dev pkg-config g++ zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip bcmath opcache intl \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN rm -rf vendor/

RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .

# Laravel production variables
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APP_KEY=base64:dummyKeyWillBeReplacedByRailway

# Serve the app
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
