#!/usr/bin/env bash
# Fix 413: raise Nginx + PHP-FPM upload limits to fit app's 5MB/file (up to 5 files)
set -e

echo "=== 1) Nginx: client_max_body_size 30m ==="
NGINX_SITE=/etc/nginx/sites-available/nias-app
if ! grep -q 'client_max_body_size' "$NGINX_SITE"; then
  sed -i '/server_name possijatim.my.id www.possijatim.my.id;/a\    client_max_body_size 30m;' "$NGINX_SITE"
fi
nginx -t
systemctl reload nginx
grep -n 'client_max_body_size' "$NGINX_SITE"

echo "=== 2) PHP-FPM: upload_max_filesize 25M, post_max_size 30M ==="
PHPINI=/etc/php/8.4/fpm/php.ini
sed -i -E 's/^(upload_max_filesize)[[:space:]]*=.*/\1 = 25M/' "$PHPINI"
sed -i -E 's/^(post_max_size)[[:space:]]*=.*/\1 = 30M/' "$PHPINI"
grep -nE '^(upload_max_filesize|post_max_size)' "$PHPINI"
systemctl restart php8.4-fpm

echo "=== 3) Sanity: site still up ==="
curl -s -o /dev/null -w "https://possijatim.my.id -> HTTP %{http_code}\n" --max-time 20 https://possijatim.my.id/
echo "UPLOAD_FIX_DONE"
