release: php artisan key:generate --force && php artisan migrate:fresh --force && php artisan passport:install --force && chmod -R 775 storage bootstrap/cache
web: vendor/bin/heroku-php-apache2 public/
