#!/bin/bash

echo "🔄 Clearing Laravel cache..."

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

echo "✅ Semua cache berhasil dibersihkan."
