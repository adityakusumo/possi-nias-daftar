#!/usr/bin/env bash
# Purge history: hapus temp.txt + semua *.sql dari SELURUH history, force-push, re-sync clone
set -e
PURGE=/home/itpossijatim/Git/purge-tmp
GIT_DIR=/home/itpossijatim/Git/possi-nias-daftar
WS=/home/itpossijatim/possi-nias-daftar

echo "=== 0) install git-filter-repo ==="
export DEBIAN_FRONTEND=noninteractive
apt-get install -y -qq git-filter-repo 2>/dev/null || pip3 install --break-system-packages git-filter-repo 2>/dev/null || pip install --break-system-packages git-filter-repo 2>/dev/null
command -v git-filter-repo || { echo "FILTER_REPO_MISSING"; exit 1; }
echo "git-filter-repo: $(git-filter-repo --version 2>/dev/null || echo OK)"

echo "=== 1) fresh clone untuk purge ==="
rm -rf "$PURGE"
su -s /bin/bash itpossijatim -c "git clone git@github.com:adityakusumo/possi-nias-daftar.git $PURGE"

echo "=== 2) filter-repo: invert-paths temp.txt + *.sql ==="
su -s /bin/bash itpossijatim -c "cd $PURGE && git filter-repo --invert-paths --path temp.txt --path-glob '*.sql' --force"
su -s /bin/bash itpossijatim -c "cd $PURGE && git remote add origin git@github.com:adityakusumo/possi-nias-daftar.git"

echo "=== 3) verifikasi history bersih ==="
su -s /bin/bash itpossijatim -c "cd $PURGE && echo -n 'temp.txt refs: ' && git log --all --oneline -- temp.txt | wc -l && echo -n '*.sql refs: ' && git log --all --oneline -- '*.sql' | wc -l && git log --oneline -3"

echo "=== 4) force-push (rewrite history) ==="
su -s /bin/bash itpossijatim -c "cd $PURGE && git push --force origin main"

echo "=== 5) re-clone ~/Git (jaga NIAS.sql untuk workflow update) ==="
cp "$GIT_DIR/nias-app/database_backup/NIAS.sql" /tmp/NIAS.sql.bak
rm -rf "$GIT_DIR"
su -s /bin/bash itpossijatim -c "git clone git@github.com:adityakusumo/possi-nias-daftar.git $GIT_DIR"
mkdir -p "$GIT_DIR/nias-app/database_backup"
cp /tmp/NIAS.sql.bak "$GIT_DIR/nias-app/database_backup/NIAS.sql"
chown -R itpossijatim:itpossijatim "$GIT_DIR"
su -s /bin/bash itpossijatim -c "cd $GIT_DIR && git log --oneline -1 && ls -la nias-app/database_backup/NIAS.sql"

echo "=== 6) re-sync workspace (fetch + reset --hard) ==="
git -C "$WS" fetch origin 2>&1 | tail -1
git -C "$WS" reset --hard origin/main 2>&1 | tail -1
git -C "$WS" log --oneline -1
git -C "$WS" status --short | head -5

echo "=== 7) cleanup ==="
rm -rf "$PURGE" /tmp/NIAS.sql.bak
echo "PURGE_DONE"
