web: vendor/bin/heroku-php-apache2 public/
release: php artisan key:generate --force && php artisan migrate --force && chmod -R 775 storage bootstrap/cache
