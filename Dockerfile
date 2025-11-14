# استخدم PHP 8.2 CLI
FROM php:8.2-cli

# تثبيت المتطلبات الأساسية والحزم
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git curl libicu-dev pkg-config g++ zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip bcmath opcache intl \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# تعيين مجلد العمل
WORKDIR /app

# نسخ ملفات composer قبل باقي المشروع لزيادة استفادة Docker cache
COPY composer.json composer.lock ./

# حذف أي مجلد vendor قديم لتجنب مشاكل الملفات الناقصة
RUN rm -rf vendor/

# تثبيت الحزم بدون dev packages وبدون scripts لتجنب أخطاء أثناء build
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# نسخ باقي ملفات المشروع
COPY . .

# يمكنك تشغيل dump-autoload داخل container بعد build لتجنب أي مشاكل
# RUN composer dump-autoload -o

# بدء السيرفر
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
