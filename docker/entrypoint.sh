#!/usr/bin/env bash
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan filament:upgrade

exec /usr/bin/supervisord -c /etc/supervisord.conf