#!/usr/bin/env bash
# Langkah 6/7: restore dump + migrate --force; Langkah 8: permissions
set -e
cd /var/www/possi-nias-daftar/nias-app
export MYSQL_PWD="$(cat /root/.dbnias_pass)"

echo "=== RESTORE 20260429_dbnias.sql ==="
mariadb -u itpossi dbnias < database_backup/20260429_dbnias.sql
echo "RESTORE_OK"
mariadb -u itpossi -e "SHOW TABLES;" dbnias
mariadb -u itpossi -e "SELECT COUNT(*) AS users FROM users;" dbnias

echo "=== MIGRATE (as itpossijatim) ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan migrate --force'

echo "=== LANGKAH 8: PERMISSIONS ==="
chown -R itpossijatim:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
ls -ld storage bootstrap/cache
echo "RESTORE_MIGRATE_DONE"
