#!/usr/bin/env bash
# Deploy update: /var/www as git checkout of origin/main + Langkah 11 pipeline
set -e
cd /var/www/possi-nias-daftar

echo "=== 1) Init git repo ==="
git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/adityakusumo/possi-nias-daftar.git

echo "=== 2) Fetch + align with origin/main ==="
git fetch origin
git reset --hard origin/main
git pull origin main
git log --oneline -3
echo "=== top level ==="
ls

echo "=== 3) composer install ==="
cd nias-app
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -4

echo "=== 4) chown ==="
chown -R itpossijatim:www-data /var/www/possi-nias-daftar

echo "=== 5) migrate --force ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan migrate --force'

echo "=== 6) rebuild caches ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache'

echo "=== 7) restart services ==="
systemctl restart php8.4-fpm nginx

echo "=== 8) verify ==="
curl -s -o /dev/null -w "localhost -> HTTP %{http_code}\n" http://localhost/
curl -s -o /dev/null -w "domain   -> HTTP %{http_code}\n" --max-time 15 http://possijatim.my.id/
echo "PULL_DEPLOY_DONE"
