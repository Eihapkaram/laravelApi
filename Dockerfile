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

# نسخ جميع ملفات المشروع
COPY . .

# تنظيف أي ملفات قديمة
RUN rm -rf vendor composer.lock && composer clear-cache

# تثبيت الحزم بدون dev packages وتحسين autoloader
RUN composer install --no-dev --no-interaction --optimize-autoloader

# صلاحيات مجلدات التخزين والcache
RUN mkdir -p storage/framework/cache storage/logs && chmod -R 775 storage bootstrap/cache

# ✅ تمت الإضافة: تأكيد إنشاء باقي مجلدات Laravel المطلوبة
RUN mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/framework/cache \
    && chmod -R 775 storage bootstrap/cache

# ✅ تمت الإضافة: إعداد متغيرات بيئة لتفادي مشاكل التخزين في منصات مثل Railway
ENV SESSION_DRIVER=array
ENV VIEW_COMPILED_PATH=/tmp
ENV CACHE_DRIVER=array

# ✅ تمت الإضافة: تنظيف الكاش قبل التشغيل لضمان عمل التطبيق
RUN php artisan config:clear && php artisan cache:clear && php artisan view:clear

# فتح المنفذ 8080
EXPOSE 8080

# تشغيل Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
