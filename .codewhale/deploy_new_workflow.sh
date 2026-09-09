#!/usr/bin/env bash
# NEW WORKFLOW: git lives in ~/Git only; /var/www is git-free; deploy = rsync of nias-app
set -e
REPO_URL=https://github.com/adityakusumo/possi-nias-daftar.git
GIT_DIR=/home/itpossijatim/Git/possi-nias-daftar
WWW=/var/www/possi-nias-daftar

echo "=== 1) Create ~/Git + clone + pull (as itpossijatim) ==="
mkdir -p /home/itpossijatim/Git
chown itpossijatim:itpossijatim /home/itpossijatim/Git
if [ ! -d "$GIT_DIR/.git" ]; then
  su -s /bin/bash itpossijatim -c "git clone $REPO_URL $GIT_DIR"
fi
su -s /bin/bash itpossijatim -c "cd $GIT_DIR && git pull origin main"
echo "Git HEAD: $(su -s /bin/bash itpossijatim -c "cd $GIT_DIR && git log --oneline -1")"

echo "=== 2) Remove ALL git trace from /var/www ==="
rm -rf "$WWW/.git"
echo "remaining git artifacts in /var/www: $(find "$WWW" -name '.git' -o -name '.gitignore' 2>/dev/null | wc -l) (note: .gitignore app files are kept)"

echo "=== 3) Rsync nias-app -> /var/www (exclude .env, vendor, storage, database_backup) ==="
rsync -a --delete \
  --exclude '.env' \
  --exclude 'vendor/' \
  --exclude 'storage/' \
  --exclude 'database_backup/' \
  --exclude '.git/' \
  "$GIT_DIR/nias-app/" "$WWW/nias-app/"

echo "=== 4) Remove leftover PII dumps from /var/www ==="
rm -rf "$WWW/nias-app/database_backup"
echo "database_backup removed from /var/www (still in ~/Git)"

echo "=== 5) composer check (rebuild vendor only if composer files changed) ==="
if ! cmp -s "$GIT_DIR/nias-app/composer.json" "$WWW/nias-app/composer.json" || ! cmp -s "$GIT_DIR/nias-app/composer.lock" "$WWW/nias-app/composer.lock"; then
  echo "composer files changed -> running composer install"
  su -s /bin/bash itpossijatim -c "cd $WWW/nias-app && composer install --no-dev --optimize-autoloader --no-interaction" >/dev/null 2>&1
else
  echo "composer files unchanged -> vendor kept"
fi

echo "=== 6) Ownership + permissions ==="
chown -R itpossijatim:www-data "$WWW"
chmod -R 775 "$WWW/nias-app/storage" "$WWW/nias-app/bootstrap/cache"

echo "=== 7) storage:link + caches + restart ==="
su -s /bin/bash itpossijatim -c "cd $WWW/nias-app && php artisan storage:link" >/dev/null 2>&1 || true
su -s /bin/bash itpossijatim -c "cd $WWW/nias-app && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache" >/dev/null 2>&1
systemctl restart php8.4-fpm nginx

echo "=== 8) Verify ==="
grep -c '^DB_PASSWORD=' "$WWW/nias-app/.env" | xargs echo ".env DB_PASSWORD present:"
ls "$WWW/nias-app/vendor/autoload.php" >/dev/null 2>&1 && echo "vendor intact"
ls -la "$WWW/nias-app/public/storage" 2>/dev/null | head -1
ls "$WWW/nias-app/storage/app/private/nias/" 2>/dev/null | head -3
curl -s -o /dev/null -w "https://possijatim.my.id -> HTTP %{http_code}\n" --max-time 20 https://possijatim.my.id/
echo "DEPLOY_DONE"
