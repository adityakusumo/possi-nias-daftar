#!/usr/bin/env bash
# Patch live DB: import MstTarifNias + add users.bukti_transfer_path (from 20260811 backup)
set -e
cd /var/www/possi-nias-daftar/nias-app/database_backup
export MYSQL_PWD="$(cat /root/.dbnias_pass)"

echo "=== 1) Import MstTarifNias (structure + data) ==="
awk '/Table structure for table `MstTarifNias`/{w=1} w{print} w&&/^UNLOCK TABLES/{w=0; print ""}' 20260811_dbnias_backup.sql > /tmp/msttarifnias.sql
mariadb -u itpossi dbnias < /tmp/msttarifnias.sql
echo "MstTarifNias imported"

echo "=== 2) Add users.bukti_transfer_path ==="
mariadb -u itpossi -e "ALTER TABLE dbnias.users ADD COLUMN bukti_transfer_path varchar(255) DEFAULT NULL;"
echo "column added"

echo "=== 3) Verify ==="
mariadb -u itpossi -e "SELECT COUNT(*) AS tarif_rows FROM dbnias.MstTarifNias;"
mariadb -u itpossi -e "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='dbnias' AND TABLE_NAME='users' AND COLUMN_NAME LIKE 'bukti%';"
echo "PATCH_DONE"
