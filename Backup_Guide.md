# Panduan Backup database


## Backup entire dbnias
mariadb-dump -u itpossi -p dbnias > dbnias.sql

## Restore databse dbnias
mariadb -u itpossi -p dbnias < dbnias.sql

## Backup tabel NIAS
mariadb-dump --replace -u itpossi -p dbnias NIAS > NIAS.sql

## Restore tabel NIAS dari file dump .sql
mariadb -u itpossi -p dbnias < NIAS.sql
