web: vendor/bin/heroku-php-apache2 public/
release: php artisan migrate:fresh --force && php artisan key:generate --force && chmod -R 775 storage bootstrap/cache
