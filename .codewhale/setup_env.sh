#!/usr/bin/env bash
# Langkah 5: .env dari .env.example + konfigurasi production
set -e
cd /var/www/possi-nias-daftar/nias-app
PASS=$(cat /root/.dbnias_pass)

cp .env.example .env

sed -i 's/^APP_NAME=Laravel/APP_NAME="NIAS POSSI Jawa Timur"/' .env
sed -i 's/^APP_ENV=local/APP_ENV=production/' .env
sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i 's#^APP_URL=http://localhost#APP_URL=http://possijatim.my.id#' .env
sed -i 's/^DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/^# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
sed -i 's/^# DB_PORT=3306/DB_PORT=3306/' .env
sed -i 's/^# DB_DATABASE=laravel/DB_DATABASE=dbnias/' .env
sed -i 's/^# DB_USERNAME=root/DB_USERNAME=itpossi/' .env
sed -i "s/^# DB_PASSWORD=$/DB_PASSWORD=${PASS}/" .env

chown itpossijatim:www-data .env
chmod 640 .env

echo "=== .env (sensitive values masked) ==="
grep -vE 'PASSWORD|KEY' .env

echo "=== key:generate (as itpossijatim) ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan key:generate'
echo "ENV_DONE"
