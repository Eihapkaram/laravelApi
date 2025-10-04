release: php artisan key:generate --force && php artisan migrate --force && php artisan passport:install --force && chmod -R 775 storage bootstrap/cache && php artisan storage:link && php artisan config:clear && php artisan cache:clear

web: php artisan serve --host=0.0.0.0 --port=$PORT
