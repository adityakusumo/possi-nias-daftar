<?php
// One-off: email the VPS setup & troubleshooting document
$content = file_get_contents('/home/itpossijatim/possi-nias-daftar/VPS_SETUP_AND_TROUBLESHOOTING.md');
Mail::raw($content, function ($m) {
    $m->to('it.possijatim@gmail.com')->subject('NIAS POSSI Jatim - VPS Setup & Troubleshooting Log (' . date('Y-m-d H:i') . ')');
});
echo "DOC_EMAIL_SENT\n";
