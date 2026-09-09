#!/usr/bin/env bash
# Untrack all .sql backup files + ignore them; LOCAL COMMIT ONLY (no push)
set -e
cd /home/itpossijatim/possi-nias-daftar

echo "=== 1) List tracked .sql files ==="
git ls-files '*.sql' | tee /tmp/sql_list.txt
echo "total: $(wc -l < /tmp/sql_list.txt)"

echo "=== 2) Untrack (keep files on disk) ==="
git rm --cached $(cat /tmp/sql_list.txt) > /dev/null
echo "untracked"

echo "=== 3) Add *.sql to .gitignore ==="
grep -q '^# database backup files' .gitignore || printf '\n# database backup files (PII) - kept out of git\n*.sql\n' >> .gitignore
git add .gitignore

echo "=== 4) Local commit (NOT pushed) ==="
git commit -m "Remove database backup .sql files from repo (PII) - local commit, NOT pushed"
git log --oneline -1

echo "=== 5) Status ==="
git status --short | head -20
echo "REPO_CLEANUP_DONE"
