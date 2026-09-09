#!/usr/bin/env bash
# Langkah 2: MariaDB secure + create dbnias / itpossi
set -e
PASS=$(cat /root/.dbnias_pass)

echo "=== SECURE INSTALL (non-interactive equivalents) ==="
mariadb -u root <<'SQL'
DELETE FROM mysql.global_priv WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';
FLUSH PRIVILEGES;
SQL

echo "=== CREATE DB + USER ==="
mariadb -u root <<SQL
CREATE DATABASE IF NOT EXISTS dbnias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'itpossi'@'localhost' IDENTIFIED BY '${PASS}';
GRANT ALL PRIVILEGES ON dbnias.* TO 'itpossi'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "=== VERIFY ==="
mariadb -u root -e "SHOW DATABASES;"
mariadb -u root -e "SELECT User, Host FROM mysql.user WHERE User='itpossi';"
mariadb -u itpossi -p"${PASS}" -e "SELECT 1 AS ok;" dbnias
echo "MARIADB_SETUP_DONE"
