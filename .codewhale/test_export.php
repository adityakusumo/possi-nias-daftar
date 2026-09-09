<?php
// Test exportExisting: format xlsx & csv
Auth::login(App\Models\User::where('email', 'it.possijatim@gmail.com')->first());
$c = new App\Http\Controllers\NiasController();

foreach (['xlsx', 'csv'] as $fmt) {
    $req = Illuminate\Http\Request::create('/nias/existing/export', 'GET', ['format' => $fmt]);
    $resp = $c->exportExisting($req);
    $file = $resp->getFile()->getPathname();
    $magic = bin2hex(file_get_contents($file, false, null, 0, 4));
    $ct = $resp->headers->get('content-type');
    $cd = $resp->headers->get('content-disposition');
    echo "[$fmt] size=" . filesize($file) . " magic=$magic\n";
    echo "[$fmt] content-type=$ct\n";
    echo "[$fmt] disposition=$cd\n";
}
echo "TEST_DONE\n";
