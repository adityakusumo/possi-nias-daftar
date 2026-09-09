#!/usr/bin/env bash
# Restore 20260811_dbnias_backup.sql (full, today's data) with safety dump first
set -e
cd /var/www/possi-nias-daftar/nias-app
export MYSQL_PWD="$(cat /root/.dbnias_pass)"

echo "=== 1) Safety dump of current live DB ==="
TS=$(date +%Y%m%d_%H%M%S)
mysqldump -u itpossi dbnias > /root/dbnias_pre_20260811_restore_${TS}.sql
ls -lh /root/dbnias_pre_20260811_restore_${TS}.sql

echo "=== 2) Drop + recreate dbnias ==="
mariadb -u itpossi -e "DROP DATABASE dbnias; CREATE DATABASE dbnias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "=== 3) Import 20260811_dbnias_backup.sql ==="
mariadb -u itpossi dbnias < database_backup/20260811_dbnias_backup.sql
echo "IMPORT_OK"

echo "=== 4) Verify data ==="
mariadb -u itpossi -e "SELECT COUNT(*) AS tables_count FROM information_schema.TABLES WHERE TABLE_SCHEMA='dbnias';"
mariadb -u itpossi -e "SELECT (SELECT COUNT(*) FROM dbnias.users) AS users, (SELECT COUNT(*) FROM dbnias.NIAS) AS nias, (SELECT COUNT(*) FROM dbnias.NIAS_STRUCT) AS nias_struct, (SELECT COUNT(*) FROM dbnias.MstTarifNias) AS mst_tarif, (SELECT COUNT(*) FROM dbnias.kontingens) AS kontingens;"
mariadb -u itpossi -e "SELECT id, nama, email, role FROM dbnias.users WHERE role='admin';"
mariadb -u itpossi -e "SELECT COUNT(*) AS recorded_migrations FROM dbnias.migrations;"
echo "repo migration files: $(ls database/migrations/*.php | wc -l)"

echo "=== 5) migrate --force (expect: nothing pending) ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan migrate --force'

echo "=== 6) storage:link + caches ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan storage:link || true'
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache'

echo "=== 7) Uploads dir state ==="
ls -la storage/app/public/ 2>/dev/null | head -8
echo "RESTORE_DONE"
