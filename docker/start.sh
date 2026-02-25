#!/bin/bash
set -e

echo "Running migrations..."
php artisan migrate --force

echo "Seeding admin user..."
php artisan db:seed --class=AdminUserSeeder --force

echo "Caching config..."
php artisan optimize

echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
