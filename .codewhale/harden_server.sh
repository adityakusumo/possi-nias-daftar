#!/usr/bin/env bash
# Server hardening part 1: ufw firewall + hide nginx version
set -e

echo "=== 1) Confirm SSH port ==="
ss -tlnp | grep sshd | head -3

echo "=== 2) ufw: allow SSH(22) + 80 + 443, then enable ==="
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
ufw status verbose | head -12

echo "=== 3) nginx: server_tokens off ==="
if ! grep -q 'server_tokens' /etc/nginx/nginx.conf; then
  sed -i 's/^http {/http {\n    server_tokens off;/' /etc/nginx/nginx.conf
fi
nginx -t
systemctl reload nginx
echo "--- header after: ---"
curl -sI https://possijatim.my.id/ | grep -i '^server:' || true
echo "HARDENING_DONE"
