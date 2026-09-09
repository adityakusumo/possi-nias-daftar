<?php
// Test: exportExisting TANPA parameter format -> harus default xlsx
Auth::login(App\Models\User::where('email', 'it.possijatim@gmail.com')->first());
$c = new App\Http\Controllers\NiasController();
$req = Illuminate\Http\Request::create('/nias/existing/export', 'GET', []);
$resp = $c->exportExisting($req);
$file = $resp->getFile()->getPathname();
$magic = bin2hex(file_get_contents($file, false, null, 0, 4));
echo "default: magic=$magic type=" . $resp->headers->get('content-type') . "\n";
echo "default: disposition=" . $resp->headers->get('content-disposition') . "\n";
echo "DEFAULT_TEST_DONE\n";
