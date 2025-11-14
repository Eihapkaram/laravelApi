FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev zip unzip git curl \
    libicu-dev g++ \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# ----------------------------------------------------
# 1) Copy composer files ONLY
# ----------------------------------------------------
COPY composer.json composer.lock ./

# ----------------------------------------------------
# 2) Install vendor WITHOUT running scripts
#    (من غير ما يشغّل artisan package:discover)
# ----------------------------------------------------
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-interaction

# ----------------------------------------------------
# 3) Copy the rest of the application
# ----------------------------------------------------
COPY . .

# ----------------------------------------------------
# 4) Run Laravel optimization scripts AFTER code exists
# ----------------------------------------------------
RUN php artisan config:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true

RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
