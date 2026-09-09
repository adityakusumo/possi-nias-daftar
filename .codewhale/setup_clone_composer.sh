#!/usr/bin/env bash
# Langkah 3-4 (adapted per user): copy nias-app ke /var/www + composer install
set -e
mkdir -p /var/www/possi-nias-daftar
if [ ! -d /var/www/possi-nias-daftar/nias-app ]; then
  cp -a /home/itpossijatim/possi-nias-daftar/nias-app /var/www/possi-nias-daftar/nias-app
fi
chown -R itpossijatim:www-data /var/www/possi-nias-daftar
chmod -R 755 /var/www/possi-nias-daftar
cd /var/www/possi-nias-daftar/nias-app
composer install --no-dev --optimize-autoloader --no-interaction
chown -R itpossijatim:www-data /var/www/possi-nias-daftar
ls -la /var/www/possi-nias-daftar/nias-app | head -8
echo "COPY_COMPOSER_DONE"
