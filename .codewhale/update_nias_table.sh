#!/usr/bin/env bash
# Update NIAS table from ~/Git dump (target: 3344 rows), safety dump first
set -e
export MYSQL_PWD="$(cat /root/.dbnias_pass)"
SRC=/home/itpossijatim/Git/possi-nias-daftar/nias-app/database_backup/NIAS.sql

echo "=== 1) Safety dump of current NIAS table ==="
TS=$(date +%Y%m%d_%H%M%S)
mysqldump -u itpossi dbnias NIAS > /root/nias_pre_update_${TS}.sql
ls -lh /root/nias_pre_update_${TS}.sql

echo "=== 2) Verify dump row count == 3344 ==="
ROWS=$(grep -cE '^\(' "$SRC")
echo "dump rows: $ROWS"
[ "$ROWS" -eq 3344 ] || { echo "ABORT: dump row count is not 3344"; exit 1; }

echo "=== 3) Import (DROP + CREATE + INSERT) ==="
mariadb -u itpossi dbnias < "$SRC"
echo "import done"

echo "=== 4) Verify ==="
mariadb -u itpossi -e "SELECT COUNT(*) AS nias_count FROM dbnias.NIAS;"
mariadb -u itpossi -e "SELECT NONIAS, NAMA, NAMACLUB, TGLLAHIR, STATUS FROM dbnias.NIAS ORDER BY ID DESC LIMIT 3;"
echo "NIAS_UPDATE_DONE"
