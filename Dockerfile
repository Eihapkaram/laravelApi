FROM php:8.2-fpm

# Install system dependencies
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

# ---------- Install PHP Dependencies ----------
COPY composer.json composer.lock ./
COPY database ./database

# لازم تعمل install قبل copy باقي الملفات
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ---------- Copy application files ----------
COPY . .

# ---------- Laravel Optimizations ----------
RUN php artisan config:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true

# Build optimized caches
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# ---------- Set environment ----------
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8080

# ---------- Run PHP server ----------
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
