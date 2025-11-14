# 1. اختيار صورة PHP مع Composer مسبقاً
FROM php:8.2-fpm-alpine

# 2. تثبيت الحزم المطلوبة للبناء وتشغيل Laravel
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    curl \
    && docker-php-ext-install pdo pdo_mysql zip intl bcmath

# 3. تثبيت Composer (آخر نسخة)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. تعيين مجلد العمل
WORKDIR /var/www/html

# 5. نسخ ملفات Composer فقط لتثبيت الحزم أولاً
COPY composer.json composer.lock ./

# 6. تثبيت الحزم بدون dev وتحسين autoloader
RUN composer install --no-dev --no-interaction --optimize-autoloader

# 7. نسخ باقي ملفات المشروع
COPY . .

# 8. إعداد صلاحيات المجلدات
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 9. تعيين مستخدم www-data
RUN chown -R www-data:www-data /var/www/html

# 10. الأمر الإفتراضي لتشغيل PHP-FPM
CMD ["php-fpm"]
