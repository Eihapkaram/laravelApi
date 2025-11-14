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

# بدون scripts لمنع أي errors
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .

# ❌ ممنوع dump-autoload — لأنه هو اللي بيبوظها
# RUN composer dump-autoload --optimize

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]