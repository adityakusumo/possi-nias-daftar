<?php
// Test: SettingController::saveNias menyimpan max_accounts[club] (bug fix verification)
$before = AppSetting::get('nias_max_accounts_per_club', '{}');

$req = Illuminate\Http\Request::create('/settings/nias', 'POST', [
    'max_accounts'     => ['PAS SC' => '3'],
    'nias_open_date'   => AppSetting::get('nias_open_date'),
    'nias_close_date'  => AppSetting::get('nias_close_date'),
]);

$c = new App\Http\Controllers\SettingController();
$c->saveNias($req);

echo "PAS SC limit setelah simpan: " . AppSetting::getMaxAccountsForClub('PAS SC') . "\n";

// Restore state awal
AppSetting::set('nias_max_accounts_per_club', $before);
echo "RESTORED\n";
