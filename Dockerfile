FROM php:8.2-fpm-alpine

# تثبيت dependencies اللازمة
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    curl \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip intl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# نسخ ملفات المشروع
COPY . .

# حذف أي مجلد vendor و lock قديم
RUN rm -rf vendor composer.lock

# تثبيت الحزم بدون dev وتحسين autoloader
RUN composer install --no-dev --no-interaction --optimize-autoloader

# إعداد صلاحيات المجلدات
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

CMD ["php-fpm"]
