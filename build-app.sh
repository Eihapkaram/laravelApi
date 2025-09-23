#!/bin/bash
set -e

echo "Starting build-app.sh..."

# تنظيف الكاش
php artisan config:clear
php artisan cache:clear

# إزالة روابط قديمة للـ storage
rm -rf public/storage
rm -rf public/storagepublic

# إنشاء رابط جديد للـ storage
php artisan storage:link --force

# Passport install (يولد المفاتيح)
php artisan passport:install --force

echo "Build completed."
