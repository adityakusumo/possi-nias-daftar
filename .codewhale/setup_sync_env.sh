#!/usr/bin/env bash
# Sync DB_PASSWORD + MAIL_* from temp.txt into live .env + MariaDB (only those keys)
set -e
SRC=/var/www/possi-nias-daftar/temp.txt
ENV=/var/www/possi-nias-daftar/nias-app/.env
DBPW="$(grep -E '^DB_PASSWORD=' "$SRC" | cut -d= -f2-)"

echo "=== validate DB password (reject only quote/control chars) ==="
if echo "$DBPW" | grep -qE "['[:cntrl:]]"; then
  echo "ERROR: DB_PASSWORD in temp.txt contains quote/control characters — refusing. Check temp.txt."
  exit 1
fi
echo "DB password format OK (validated, not printed)"

echo "=== sync keys into .env (python, verbatim values) ==="
python3 - "$SRC" "$ENV" <<'PY'
import sys
src_path, env_path = sys.argv[1:3]
def read_kv(path):
    vals = {}
    with open(path, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            k, v = line.split('=', 1)
            vals[k] = v
    return vals
src = read_kv(src_path)
keys = ['DB_PASSWORD','MAIL_MAILER','MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_ENCRYPTION','MAIL_FROM_ADDRESS','MAIL_FROM_NAME']
with open(env_path, encoding='utf-8') as f:
    lines = f.readlines()
out, done = [], set()
for line in lines:
    s = line.strip()
    if s and not s.startswith('#') and '=' in s:
        k = s.split('=', 1)[0]
        if k in keys and k in src:
            out.append(f"{k}={src[k]}\n")
            done.add(k)
            continue
    out.append(line)
for k in keys:
    if k not in done and k in src:
        out.append(f"{k}={src[k]}\n")
        done.add(k)
with open(env_path, 'w', encoding='utf-8') as f:
    f.writelines(out)
print("SYNCED_KEYS:", sorted(done))
PY

chown itpossijatim:www-data "$ENV"
chmod 640 "$ENV"

echo "=== MariaDB: set itpossi password ==="
mariadb -u root -e "ALTER USER 'itpossi'@'localhost' IDENTIFIED BY '${DBPW}'; FLUSH PRIVILEGES;"
echo "$DBPW" > /root/.dbnias_pass
chmod 600 /root/.dbnias_pass
echo "password updated in MariaDB + /root/.dbnias_pass"

echo "=== verify DB login with new password ==="
mariadb -u itpossi -p"$DBPW" -e "SELECT 1 AS ok;" dbnias
echo "DB_LOGIN_OK"

echo "=== rebuild config cache ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan config:cache'
echo "CONFIG_CACHE_OK"

echo "=== test mail send (SMTP via Gmail) ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan tinker --execute="try { Mail::raw(\"Test email from possijatim.my.id (setup sync)\", function(\$m){ \$m->to(\"it.possijatim@gmail.com\")->subject(\"Test mail - POSSI Jatim\"); }); echo \"MAIL_SEND_OK\"; } catch (\Exception \$e) { echo \"MAIL_SEND_FAIL: \" . \$e->getMessage(); }"' 2>&1 | tail -5 || echo "MAIL_TEST_COMMAND_FAILED"

echo "=== site check ==="
curl -s -o /dev/null -w "https://possijatim.my.id -> HTTP %{http_code}\n" --max-time 20 https://possijatim.my.id/
echo "SYNC_DONE"
