#!/usr/bin/env bash
# Fix SSH client untuk deploy key GitHub + uji autentikasi
set -e
KEY=/home/itpossijatim/.ssh/id_ed25519_github

echo "=== 1) perms key ==="
ls -la /home/itpossijatim/.ssh/

echo "=== 2) tambah ~/.ssh/config (IdentityFile deploy key) ==="
su -s /bin/bash itpossijatim -c '
mkdir -p ~/.ssh && chmod 700 ~/.ssh
touch ~/.ssh/config && chmod 600 ~/.ssh/config
if ! grep -q "id_ed25519_github" ~/.ssh/config; then
cat >> ~/.ssh/config << EOF
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_github
    IdentitiesOnly yes
EOF
fi
cat ~/.ssh/config
'

echo "=== 3) uji autentikasi ==="
su -s /bin/bash itpossijatim -c 'ssh -o StrictHostKeyChecking=accept-new -T git@github.com 2>&1 | head -3' || true

echo "=== 4) fingerprint key lokal (bandingkan dengan yang dipaste di GitHub) ==="
ssh-keygen -lf "$KEY.pub"
echo "AUTH_TEST_DONE"
