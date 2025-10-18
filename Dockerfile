# استخدم نسخة PHP CLI رسمية مع Composer
FROM php:8.2-cli

# تثبيت مكتبات النظام وامتدادات PHP الضرورية
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# تعيين مجلد العمل
WORKDIR /app

# نسخ ملفات المشروع
COPY . .

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# تثبيت حزم PHP بدون dev packages لتجنب مشاكل phpunit
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# فتح المنفذ 8080 (Railway يستخدمه)
EXPOSE 8080

# تشغيل تطبيق Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
