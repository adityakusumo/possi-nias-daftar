#!/usr/bin/env bash
# Compare Nias::$clubLookup keys vs distinct NAMACLUB in dbnias.NIAS
set -e
MODEL=/home/itpossijatim/Git/possi-nias-daftar/nias-app/app/Models/Nias.php

echo "=== lookup keys (from model) ==="
awk '/public static array \$clubLookup/,/^\];/' "$MODEL" | grep -oE "^[[:space:]]*'[^']+'" | sed -E "s/^[[:space:]]*'([^']+)'/\1/" | sort -u > /tmp/lookup_keys.txt
wc -l < /tmp/lookup_keys.txt

echo "=== db clubs ==="
mariadb -u root -N -e "SELECT DISTINCT NAMACLUB FROM dbnias.NIAS ORDER BY NAMACLUB;" > /tmp/db_clubs.txt
wc -l < /tmp/db_clubs.txt

echo "=== IN DB but NOT in lookup (MUST ADD) ==="
comm -23 /tmp/db_clubs.txt /tmp/lookup_keys.txt

echo "=== in lookup but NOT in DB (info only) ==="
comm -13 /tmp/db_clubs.txt /tmp/lookup_keys.txt
