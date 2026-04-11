#!/bin/bash
sudo chown -R itpossijatim:www-data /var/www/possi-nias-daftar/nias-app/storage
sudo chown -R itpossijatim:www-data /var/www/possi-nias-daftar/nias-app/bootstrap/cache
sudo chmod -R 775 /var/www/possi-nias-daftar/nias-app/storage
sudo chmod -R 775 /var/www/possi-nias-daftar/nias-app/bootstrap/cache
echo "✅ Permission fixed!"
