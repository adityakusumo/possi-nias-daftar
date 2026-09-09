#!/usr/bin/env bash
# Resolve cherry-pick conflicts (retry dengan mkdir) + push ke GitHub
set -e
GIT_DIR=/home/itpossijatim/Git/possi-nias-daftar

su -s /bin/bash itpossijatim -c "
set -e
cd $GIT_DIR

echo '=== 1) .gitignore (HEAD + block *.sql) ==='
git show HEAD:.gitignore > /tmp/gi_new
printf '\n# database backup files (PII) - kept out of git\n*.sql\n' >> /tmp/gi_new
cp /tmp/gi_new .gitignore

echo '=== 2) NIAS.sql: hapus dari repo, simpan file di disk (versi HEAD) ==='
git rm -f nias-app/database_backup/NIAS.sql >/dev/null 2>&1 || true
mkdir -p nias-app/database_backup
git show HEAD:nias-app/database_backup/NIAS.sql > nias-app/database_backup/NIAS.sql
ls -la nias-app/database_backup/NIAS.sql

echo '=== 3) stage + continue cherry-pick ==='
git add -A
GIT_EDITOR=true git cherry-pick --continue
git log --oneline -2

echo '=== 4) push ==='
git push origin main
echo '=== 5) verify ==='
git log --oneline origin/main -5
git status --short | head -5
"
echo "PUSH_DONE"
