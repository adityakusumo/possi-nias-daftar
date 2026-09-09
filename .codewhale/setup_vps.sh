#!/usr/bin/env bash
# VPS setup per Setup_Guide.md (Langkah 1.1 - 1.6)
set -e
export DEBIAN_FRONTEND=noninteractive

echo "=== 1.2 SWAP (2GB) ==="
if ! swapon --show | grep -q swapfile; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
free -h

echo "=== 1.1 UPDATE/UPGRADE ==="
apt-get update -qq
apt-get upgrade -y -qq

echo "=== 1.3/1.4 NGINX + MARIADB + software-properties ==="
apt-get install -y -qq nginx mariadb-server software-properties-common

echo "=== 1.5 PHP 8.4 (ondrej PPA) ==="
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
apt-get update -qq
apt-get install -y -qq php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd php8.4-sqlite3
update-alternatives --set php /usr/bin/php8.4 || true

echo "=== 1.6 COMPOSER ==="
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet

echo "=== VERSIONS ==="
php -v | head -1
nginx -v 2>&1
mariadb --version
composer --version
swapon --show
echo "SETUP_SCRIPT_DONE"
