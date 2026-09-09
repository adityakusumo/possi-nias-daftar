<?php
// One-off: email the current .env content to the admin address
$content = file_get_contents('/var/www/possi-nias-daftar/nias-app/.env');
Mail::raw($content, function ($m) {
    $m->to('it.possijatim@gmail.com')->subject('NIAS POSSI Jatim - .env backup copy (' . date('Y-m-d H:i') . ')');
});
echo "EMAIL_SENT_TO: it.possijatim@gmail.com\n";
