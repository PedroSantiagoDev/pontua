#!/bin/bash
set -e

echo "Running migrations..."
php artisan migrate --force

echo "Seeding admin user..."
php artisan db:seed --class=AdminUserSeeder --force

echo "Caching config..."
php artisan optimize

echo "Starting FrankenPHP server..."
export SERVER_NAME=":${PORT:-8080}"
frankenphp run --config /etc/caddy/Caddyfile
