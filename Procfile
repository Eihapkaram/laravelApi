release: php artisan key:generate --force && php artisan migrate:fresh --force && php artisan passport:install --force &&composer require symfony/sendgrid-mailer&& chmod -R 775 storage bootstrap/cache
web: php artisan serve --host=0.0.0.0 --port=8080

