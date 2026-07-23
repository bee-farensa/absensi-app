#!/usr/bin/env bash
set -e

# Render inject $PORT secara dinamis, default 10000 kalau lokal
PORT="${PORT:-10000}"
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/nginx.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan filament:upgrade

exec /usr/bin/supervisord -c /etc/supervisord.conf