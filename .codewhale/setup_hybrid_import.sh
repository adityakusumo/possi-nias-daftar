#!/usr/bin/env bash
# Hybrid restore: legacy tables (Apr14) + NIAS_STRUCT (Apr29, column-mapped)
set -e
cd /var/www/possi-nias-daftar/nias-app/database_backup
export MYSQL_PWD="$(cat /root/.dbnias_pass)"

echo "=== 1) Import 19 legacy tables from 20260414 (FK off) ==="
awk '
/^-- Table structure for table `(A3|Atlet|Kompetisi|MSTKOTA|MstBiayaExtra|MstDenda|MstDeposit|MstEvent|MstGaya|MstKU|MstPeserta|MstTarif|PesertaEmail|PilihanPesertaKotaKab|PilihanPesertaKotaKabJatim|rKwtDaftarDeposit|tSyaratPrestasi|app_settings|kontingens)`/ {w=1}
w {print}
w && /^UNLOCK TABLES/ {w=0; print ""}
' 20260414_dbnias_backup.sql > /tmp/legacy_tables.sql
wc -l /tmp/legacy_tables.sql
mariadb -u itpossi --init-command="SET FOREIGN_KEY_CHECKS=0" dbnias < /tmp/legacy_tables.sql
echo "LEGACY_IMPORT_OK"

echo "=== 2) Re-import NIAS_STRUCT rows from 20260429 (column-mapped) ==="
COLS='ID,user_id,NONIAS,NAMA,GENDER,TGLLAHIR,TEMPATLAHIR,NIK,EMAIL,NAMACLUB,KDCLUB,KDJENIS,JENIS,KDKOTA,NAMAKOTA,KDJENISDOM,JENISDOM,KDPROPDOM,NAMAPROPDOM,KDKOTADOM,NAMAKOTADOM,STATUS,is_update,is_sent,sent_at,tipe_update,mutasi_luar_jatim,TGLDAFTAR_UPDATE,TGLDAFTAR,EXPIRED,LASTMUTASI,MUTASI,file_kk,file_foto,file_akte,file_ijazah,file_sk_mutasi,created_at,updated_at'
awk '/Dumping data for table `NIAS_STRUCT`/{w=1} w{print} w && /^UNLOCK TABLES/ {w=0; exit}' 20260429_dbnias.sql \
  | sed 's/^INSERT INTO `NIAS_STRUCT` VALUES/INSERT INTO `NIAS_STRUCT` ('"$COLS"') VALUES/' \
  > /tmp/nias_struct_import.sql
head -4 /tmp/nias_struct_import.sql | cut -c1-110
echo "..."
tail -1 /tmp/nias_struct_import.sql | cut -c1-110
mariadb -u itpossi --init-command="SET FOREIGN_KEY_CHECKS=0" dbnias < /tmp/nias_struct_import.sql
echo "NIAS_STRUCT_IMPORT_OK"

echo "=== 3) Verify counts ==="
mariadb -u itpossi -e "SELECT (SELECT COUNT(*) FROM dbnias.users) AS users, (SELECT COUNT(*) FROM dbnias.NIAS) AS nias, (SELECT COUNT(*) FROM dbnias.NIAS_STRUCT) AS nias_struct, (SELECT COUNT(*) FROM dbnias.MstPeserta) AS mstpeserta, (SELECT COUNT(*) FROM dbnias.app_settings) AS app_settings, (SELECT COUNT(*) FROM dbnias.kontingens) AS kontingens;"
echo "HYBRID_IMPORT_DONE"
