#!/usr/bin/env bash
# Update MAIL_PASSWORD with the new Gmail app password + verify with a test mail
set -e
ENV=/var/www/possi-nias-daftar/nias-app/.env

echo "=== 1) Update MAIL_PASSWORD ==="
sed -i 's/^MAIL_PASSWORD=.*/MAIL_PASSWORD=svpjytgaoardoqgo/' "$ENV"
chown itpossijatim:www-data "$ENV"
chmod 640 "$ENV"
echo "MAIL_PASSWORD updated (value not printed)"

echo "=== 2) Rebuild config cache ==="
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan config:cache'

echo "=== 3) Test mail with new password ==="
cat > /tmp/test_mail.php <<'PHP'
<?php
Mail::raw("Test email - new SMTP password applied (" . date('Y-m-d H:i') . ")", function ($m) {
    $m->to('it.possijatim@gmail.com')->subject('SMTP password rotation test - POSSI Jatim');
});
echo "TEST_MAIL_SENT_OK\n";
PHP
chown itpossijatim:itpossijatim /tmp/test_mail.php
su -s /bin/bash itpossijatim -c 'cd /var/www/possi-nias-daftar/nias-app && php artisan tinker --execute="require \"/tmp/test_mail.php\";"' 2>&1 | tail -3
rm -f /tmp/test_mail.php
echo "MAIL_UPDATE_DONE"
