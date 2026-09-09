#!/usr/bin/env bash
# Langkah 9: Nginx site config + enable
set -e
cat > /etc/nginx/sites-available/nias-app <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name possijatim.my.id www.possijatim.my.id;

    root /var/www/possi-nias-daftar/nias-app/public;
    index index.php;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/nias-app /etc/nginx/sites-enabled/nias-app
nginx -t
systemctl enable php8.4-fpm
systemctl restart php8.4-fpm nginx
systemctl status nginx --no-pager -l | head -5
echo "NGINX_DONE"
