#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html
php artisan migrate --force
php artisan storage:link --force
php artisan config:cache
php artisan view:cache

# Render's free web service has no free cron service. Run Laravel's scheduler
# while this web container is awake; free-instance spin-down pauses this loop.
php artisan schedule:work --no-interaction >> storage/logs/scheduler.log 2>&1 &

exec "$@"
