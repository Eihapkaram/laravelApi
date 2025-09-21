web: vendor/bin/heroku-php-apache2 public/
release: php artisan key:generate --force && php artisan migrate:refresh --force && chmod -R 775 storage bootstrap/cache
