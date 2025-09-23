#!/bin/bash
set -e

echo "🚀 Starting Laravel build..."

# 1. Clear config and cache
php artisan config:clear
php artisan cache:clear

# 2. Generate application key (force to overwrite)
php artisan key:generate --force

# 3. Run migrations
php artisan migrate --force

# 4. Install Passport keys
php artisan passport:install --force

# 5. Set permissions
chmod -R 775 storage bootstrap/cache

# 6. Remove old storage links
rm -rf public/storage
rm -rf public/storagepublic

# 7. Create symbolic link
php artisan storage:link --force

echo "✅ Laravel build completed successfully!"
