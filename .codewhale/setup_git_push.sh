#!/usr/bin/env bash
# Switch ~/Git origin to SSH (deploy key) + cherry-pick sql cleanup + push
set -e
GIT_DIR=/home/itpossijatim/Git/possi-nias-daftar
WS=/home/itpossijatim/possi-nias-daftar

su -s /bin/bash itpossijatim -c "
set -e
cd $GIT_DIR
echo '=== 1) origin -> SSH ==='
git remote set-url origin git@github.com:adityakusumo/possi-nias-daftar.git
git remote -v | head -1
echo '=== 2) uji autentikasi deploy key ==='
ssh -o StrictHostKeyChecking=accept-new -T git@github.com 2>&1 | head -2 || true
echo '=== 3) fetch origin + workspace ==='
git fetch origin
git fetch $WS main
echo '=== 4) cherry-pick commit pembersihan .sql ==='
git cherry-pick FETCH_HEAD
echo '=== 5) push ==='
git push origin main
echo '=== 6) hasil ==='
git log --oneline -6
"

echo "PUSH_DONE"
