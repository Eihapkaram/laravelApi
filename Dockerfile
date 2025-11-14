FROM php:8.2-cli

# تثبيت الإضافات المطلوبة
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git curl libicu-dev pkg-config g++ zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip bcmath opcache intl \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# انسخ ملفات composer فقط
COPY composer.json composer.lock ./

# تثبيت الحزم بدون تشغيل أي scripts
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# انسخ بقية المشروع
COPY . .

# إعداد صلاحيات المجلدات
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs \
    && chmod -R 775 storage bootstrap/cache

# استخدم PHP built-in server بدل artisan serve لتجنب .env
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]