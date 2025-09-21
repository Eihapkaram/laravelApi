web: vendor/bin/heroku-php-apache2 public/
release: php artisan migrate --force && php artisan key:generate --no-interaction && php artisan config:cache && php artisan route:cache && php artisan view:cache && chmod -R 775 storage bootstrap/cache

