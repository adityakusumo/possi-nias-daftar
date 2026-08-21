<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NiasController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\LombaController;
use App\Http\Controllers\LombaAuthController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// ── Public Landing ──────────────────────────────────────────────
Route::get('/', [WelcomeController::class, 'show'])->name('home');

// ── NIAS Auth (password-based) ──────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login.show');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register.show');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

// ── Lomba Auth (email + password OR token for new users) ────────
// Jika fitur lomba dinonaktifkan (config/lomba.php → enabled=false),
// semua URL /lomba/* dialihkan ke halaman utama. Catch-all ini harus
// didaftarkan PALING ATAS agar menangkap semua route lomba di bawahnya.
if (!config('lomba.enabled')) {
    Route::any('/lomba/{any?}', function () {
        return redirect()->route('home')->with('lomba_coming_soon', true);
    })->where('any', '.*');
}
Route::get('/lomba/login', [LombaAuthController::class, 'showLogin'])->name('lomba.login');
Route::post('/lomba/check-email', [LombaAuthController::class, 'checkEmail'])->name('lomba.check-email');
Route::post('/lomba/login-password', [LombaAuthController::class, 'loginWithPassword'])->name('lomba.login-password');
Route::post('/lomba/request-token', [LombaAuthController::class, 'requestToken'])->name('lomba.request-token');
Route::get('/lomba/verify', [LombaAuthController::class, 'showVerify'])->name('lomba.verify');
Route::post('/lomba/verify-token', [LombaAuthController::class, 'verifyToken'])->name('lomba.verify-token');
Route::post('/lomba/resend-token', [LombaAuthController::class, 'resendToken'])->name('lomba.resend-token');
Route::get('/lomba/register', [LombaAuthController::class, 'showRegister'])->name('lomba.register');
Route::post('/lomba/register', [LombaAuthController::class, 'register'])->name('lomba.register.save');
Route::get('/lomba/account', [LombaAuthController::class, 'showAccountSettings'])->name('lomba.account');
Route::post('/lomba/account/password', [LombaAuthController::class, 'updatePassword'])->name('lomba.account.password');
Route::post('/lomba/logout', [LombaAuthController::class, 'logout'])->name('lomba.logout');

// ── Forgot / Reset Password ─────────────────────────────────────
Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendLink'])->name('password.send');
Route::get('/reset-password/{token}', [ResetPasswordController::class,  'showForm'])->name('password.reset');
Route::post('/reset-password',        [ResetPasswordController::class,  'reset'])->name('password.update');

// ── Daftar Lomba (lomba auth OR nias auth) ──────────────────────
Route::middleware('lomba')->group(function () {
    Route::get('/lomba', [LombaController::class, 'index'])->name('lomba.index');

    // Form A1 — Kontingen & Atlet
    Route::get('/lomba/form-a1', [LombaController::class, 'formA1'])->name('lomba.form_a1');
    Route::post('/lomba/form-a1', [LombaController::class, 'saveKontingen'])->name('form_a1.saveKontingen');
    Route::get('/lomba/form-a1-atlet', [LombaController::class, 'formA1NamaAtlet'])->name('lomba.form_a1_namaatlet');
    Route::post('/lomba/atlet', [LombaController::class, 'addAtlet'])->name('lomba.atlet.store');
    Route::put('/lomba/atlet/{id}', [LombaController::class, 'updateAtlet'])->name('lomba.atlet.update');
    Route::delete('/lomba/atlet/{id}', [LombaController::class, 'deleteAtlet'])->name('lomba.atlet.delete');
    Route::get('/lomba/api/atlet', [LombaController::class, 'apiAtletList'])->name('lomba.api.atlet');

    // Form A3 — Perorangan
    Route::get('/lomba/form-a3-perorangan', [LombaController::class, 'formA3Perorangan'])->name('lomba.form_a3_perorangan');
    Route::post('/lomba/form-a3-perorangan', [LombaController::class, 'saveA3Perorangan'])->name('lomba.save_a3_perorangan');
    Route::delete('/lomba/a3/{id}', [LombaController::class, 'deleteA3Entry'])->name('lomba.a3.delete');

    // Form A3 — Estafet
    Route::get('/lomba/form-a3-estafet', [LombaController::class, 'formA3Estafet'])->name('lomba.form_a3_estafet');
    Route::post('/lomba/form-a3-estafet', [LombaController::class, 'saveA3Estafet'])->name('lomba.save_a3_estafet');

    // Proses Form A3
    Route::get('/lomba/proses', [LombaController::class, 'prosesFormA3'])->name('lomba.proses');
    Route::post('/lomba/proses', [LombaController::class, 'runProsesFormA3'])->name('lomba.run_proses');

    // Hitung Biaya
    Route::get('/lomba/biaya', [LombaController::class, 'hitungBiaya'])->name('lomba.biaya');
    Route::post('/lomba/biaya', [LombaController::class, 'runHitungBiaya'])->name('lomba.run_biaya');
});

// ── Protected: NIAS Auth ────────────────────────────────────────
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/welcome', [WelcomeController::class, 'show'])->name('welcome');
    Route::post('/welcome/choice', [WelcomeController::class, 'saveChoice'])->name('welcome.saveChoice');
    Route::get('/welcome/reset', [WelcomeController::class, 'reset'])->name('welcome.reset');
    Route::get('/account/setting',           [UserSettingController::class, 'index'])->name('user.setting');
    Route::post('/account/setting/password', [UserSettingController::class, 'updatePassword'])->name('user.setting.password');
});

Route::middleware('auth')->group(function () {
    // ── Setting (admin only) ─────────────────────────────────────
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::post('/settings/nias', [SettingController::class, 'saveNias'])->name('settings.nias.save');
        Route::post('/settings/reset-nias-schedule', [SettingController::class, 'resetNiasSchedule'])->name('settings.nias.reset');
        Route::post('/settings/tarif-nias',           [SettingController::class, 'saveTarifNias'])->name('settings.tarif.save');
        Route::post('/settings/users/{user}/reset-password', [SettingController::class, 'resetUserPassword'])->name('settings.resetPassword');
        Route::delete('/settings/users/{user}/delete',    [SettingController::class, 'deleteUser'])->name('settings.deleteUser');
        Route::get('/settings/akun/{user}',                [SettingController::class, 'showAkun'])->name('settings.akun.show');
        Route::delete('/settings/akun/selected',           [SettingController::class, 'destroySelectedAkun'])->name('settings.akun.destroySelected');
        Route::delete('/settings/akun/all',                [SettingController::class, 'destroyAllAkun'])->name('settings.akun.destroyAll');

        // ── Lomba settings ───────────────────────────────────────
        Route::get('/settings/lomba/users/{id}/edit', [SettingController::class, 'editLombaKontingen'])->name('settings.lomba.edit');
        Route::put('/settings/lomba/users/{id}', [SettingController::class, 'updateLombaKontingen'])->name('settings.lomba.update');
        Route::delete('/settings/lomba/users/{id}', [SettingController::class, 'deleteLombaUser'])->name('settings.lomba.delete-user');
        Route::post('/settings/lomba',           [SettingController::class, 'saveLomba'])->name('settings.lomba.save');
        Route::post('/settings/lomba/tarif',     [SettingController::class, 'saveLombaTarif'])->name('settings.lomba.tarif.save');
        Route::post('/settings/lomba/deposit',   [SettingController::class, 'saveLombaDeposit'])->name('settings.lomba.deposit.save');
        Route::post('/settings/lomba/denda',     [SettingController::class, 'saveLombaDenda'])->name('settings.lomba.denda.save');
        Route::post('/settings/lomba/biaya-extra', [SettingController::class, 'saveBiayaExtra'])->name('settings.lomba.biayaextra.save');
    });

    // ── NIAS CRUD ────────────────────────────────────────────────
    Route::get('/nias/clubinfo', function () {
        $club = request('club');
        $info = \App\Models\Nias::$clubLookup[$club] ?? null;
        if (!$info) return response()->json(['found' => false]);
        return response()->json([
            'found' => true,
            'kdjenis' => $info[0],
            'jenis' => $info[1],
            'kdkota' => $info[2],
            'namakota' => $info[3],
        ]);
    })->name('nias.clubinfo');

    Route::get('/nias/export', [NiasController::class, 'export'])->name('nias.export');

    Route::get('/nias/update-data', function () {
        if (auth()->user()->role !== 'admin' && !\App\Models\AppSetting::isNiasOpen()) {
            return redirect()->route('nias.index')->with('nias_closed', true);
        }
        return app(\App\Http\Controllers\NiasController::class)->showUpdateForm();
    })->name('nias.update-data');
    Route::get('/nias/existing', [NiasController::class, 'existing'])->name('nias.existing');
    Route::get('/nias/existing/export', [NiasController::class, 'exportExisting'])->name('nias.existing.export');
    Route::get('/nias/{id}/file/{col}', [NiasController::class, 'serveFile'])->name('nias.file');

    // ── Tutorial PDF (harus didaftarkan sebelum /nias/{id}) ────────
    Route::get('/nias/tutorial', function () {
        $path = public_path('tutorial/Tutorial_Pengoperasian_Website_NIAS_Jatim.pdf');
        if (!is_file($path)) {
            abort(404, 'Tutorial PDF tidak ditemukan.');
        }
        return response()->download($path, 'Tutorial_Pengoperasian_Website_NIAS_Jatim.pdf');
    })->name('nias.tutorial');

    Route::get('/nias', [NiasController::class, 'index'])->name('nias.index');
    Route::get('/nias/create', function () {
        if (auth()->user()->role !== 'admin' && !\App\Models\AppSetting::isNiasOpen()) {
            return redirect()->route('nias.index')->with('nias_closed', true);
        }
        return app(\App\Http\Controllers\NiasController::class)->create();
    })->name('nias.create');
    Route::post('/nias', [NiasController::class, 'store'])->name('nias.store');
    Route::get('/nias/{id}', [NiasController::class, 'show'])->name('nias.show');
    Route::get('/nias/{id}/edit', [NiasController::class, 'edit'])->name('nias.edit');
    Route::put('/nias/{id}', [NiasController::class, 'update'])->name('nias.update');
    Route::delete('/nias/{id}', [NiasController::class, 'destroy'])->name('nias.destroy');
    Route::delete('/nias-selected', [NiasController::class, 'destroySelected'])->name('nias.destroy-selected');
    Route::delete('/nias-all',          [NiasController::class, 'destroyAll'])->name('nias.destroy-all');
    Route::delete('/nias-sent-selected',[NiasController::class, 'destroySentSelected'])->name('nias.destroy-sent-selected');
    Route::delete('/nias-sent-all',     [NiasController::class, 'destroySentAll'])->name('nias.destroy-sent-all');
    Route::post('/nias/send-email', [NiasController::class, 'sendEmail'])->name('nias.send-email');
    Route::post('/nias/{id}/acc',         [NiasController::class, 'acc'])->name('nias.acc');
    Route::post('/nias/{id}/reject',      [NiasController::class, 'reject'])->name('nias.reject');
    Route::post('/nias/bukti-transfer',   [NiasController::class, 'uploadBuktiTransfer'])->name('nias.bukti-transfer');
    Route::get('/nias/bukti-transfer/{userId}', [NiasController::class, 'serveBuktiTransfer'])->name('nias.serve-bukti');
});
