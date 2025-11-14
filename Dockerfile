# استخدام نسخة PHP CLI رسمية
FROM php:8.2-cli

# تثبيت مكتبات النظام المطلوبة لبناء امتدادات PHP
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git curl libicu-dev pkg-config g++ zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip bcmath opcache intl \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# تعيين مجلد العمل داخل الحاوية
WORKDIR /app

# نسخ ملفات Composer فقط لتثبيت الحزم
COPY composer.json composer.lock ./

# تثبيت الحزم بدون dev packages وتحسين autoloader
RUN composer install --no-dev --no-interaction --optimize-autoloader

# نسخ باقي ملفات المشروع
COPY . .

# إنشاء مجلدات التخزين وإعطاء الصلاحيات
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# إعداد متغيرات بيئة لتفادي مشاكل التخزين
ENV SESSION_DRIVER=array
ENV VIEW_COMPILED_PATH=/tmp
ENV CACHE_DRIVER=array

# فتح المنفذ 8080
EXPOSE 8080

# تشغيل Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
