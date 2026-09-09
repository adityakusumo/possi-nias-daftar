#!/usr/bin/env bash
# SSL: certbot for possijatim.my.id + www, APP_URL -> https
set -e
export DEBIAN_FRONTEND=noninteractive

echo "=== 1) Install certbot ==="
apt-get install -y -qq certbot python3-certbot-nginx

echo "=== 2) Request certificate (nginx plugin, redirect HTTP->HTTPS) ==="
certbot --nginx -d possijatim.my.id -d www.possijatim.my.id \
  --non-interactive --agree-tos -m it.possijatim@gmail.com --redirect

echo "=== 3) APP_URL -> https ==="
cd /var/www/possi-nias-daftar/nias-app
sed -i 's#^APP_URL=http://possijatim.my.id#APP_URL=https://possijatim.my.id#' .env
grep '^APP_URL' .env
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan config:cache'

echo "=== 4) Verify ==="
curl -s -o /dev/null -w "https://possijatim.my.id -> HTTP %{http_code}\n" --max-time 20 https://possijatim.my.id/ || true
curl -s -o /dev/null -w "http://possijatim.my.id -> HTTP %{http_code}\n" --max-time 20 http://possijatim.my.id/ || true
certbot certificates 2>/dev/null | grep -E 'Certificate Name|Domains|Expiry' | head -4
echo "SSL_DONE"
