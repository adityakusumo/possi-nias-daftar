<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NiasController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\LombaController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('auth.login.show'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login.show');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register.show');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

// ── Forgot / Reset Password ───────────────────────────────────────
Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendLink'])->name('password.send');
Route::get('/reset-password/{token}', [ResetPasswordController::class,  'showForm'])->name('password.reset');
Route::post('/reset-password',        [ResetPasswordController::class,  'reset'])->name('password.update');

// ── Protected ────────────────────────────────────────────────────
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('auth');

// ── Halaman pilihan aplikasi ──────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/welcome', [WelcomeController::class, 'show'])->name('welcome');
    Route::post('/welcome/choice', [WelcomeController::class, 'saveChoice'])->name('welcome.saveChoice');
    Route::get('/welcome/reset', [WelcomeController::class, 'reset'])->name('welcome.reset');

    // ── Setting Akun User ─────────────────────────────────────────
    Route::get('/account/setting',           [UserSettingController::class, 'index'])->name('user.setting');
    Route::post('/account/setting/password', [UserSettingController::class, 'updatePassword'])->name('user.setting.password');
});

Route::middleware('auth')->group(function () {

    // ── Setting (admin only) ─────────────────────────────────────
    Route::middleware(['auth', 'admin'])->group(function () {
        // Tampilkan halaman utama settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');

        // Pastikan nama di bawah ini adalah 'settings.nias.save' agar sesuai dengan Blade
        Route::post('/settings/nias', [SettingController::class, 'saveNias'])->name('settings.nias.save');

        // Sesuaikan juga yang lainnya agar konsisten
        Route::post('/settings/reset-nias-schedule', [SettingController::class, 'resetNiasSchedule'])->name('settings.nias.reset');
        Route::post('/settings/users/{user}/reset-password', [SettingController::class, 'resetUserPassword'])->name('settings.resetPassword');
        Route::delete('/settings/users/{user}/delete',    [SettingController::class, 'deleteUser'])->name('settings.deleteUser');
        Route::get('/settings/akun/{user}',                [SettingController::class, 'showAkun'])->name('settings.akun.show');
        Route::delete('/settings/akun/selected',           [SettingController::class, 'destroySelectedAkun'])->name('settings.akun.destroySelected');
        Route::delete('/settings/akun/all',                [SettingController::class, 'destroyAllAkun'])->name('settings.akun.destroyAll');
    });

    // Club info helper
    Route::get('/nias/clubinfo', function () {
        $club = request('club');
        $info = \App\Models\Nias::$clubLookup[$club] ?? null;
        if (!$info)
            return response()->json(['found' => false]);
        return response()->json([
            'found' => true,
            'kdjenis' => $info[0],
            'jenis' => $info[1],
            'kdkota' => $info[2],
            'namakota' => $info[3],
        ]);
    })->name('nias.clubinfo');

    // Export CSV
    Route::get('/nias/export', [NiasController::class, 'export'])
        ->name('nias.export');

    Route::get('/nias/update-data', function () {
        if (auth()->user()->role !== 'admin' && !\App\Models\AppSetting::isNiasOpen()) {
            return redirect()->route('nias.index')->with('nias_closed', true);
        }
        return app(\App\Http\Controllers\NiasController::class)->showUpdateForm();
    })->name('nias.update-data');
    Route::get('/nias/existing', [NiasController::class, 'existing'])->name('nias.existing');
    Route::get('/nias/{id}/file/{col}', [NiasController::class, 'serveFile'])->name('nias.file');

    // NIAS CRUD — explicit routes agar tidak bentrok
    // Guard jadwal: regular user dicek apakah pendaftaran sedang dibuka
    // Index selalu bisa diakses (admin & regular)
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
    Route::post('/nias/{id}/acc',   [NiasController::class, 'acc'])->name('nias.acc');
    Route::post('/nias/{id}/reject',[NiasController::class, 'reject'])->name('nias.reject');

    // Daftar Lomba
    Route::get('/lomba', function () {
        return view('lomba.index'); // Sesuaikan dengan lokasi file view Anda
    })->name('lomba.index');
    // Form A1
    Route::get('/lomba/form-a1', [LombaController::class, 'formA1'])->name('lomba.form_a1');
    Route::post('/lomba/form-a1', [LombaController::class, 'saveKontingen'])->name('form_a1.saveKontingen');

    // Tab 2: Form Nama Atlet (Tambahkan baris ini)
    Route::get('/lomba/form-a1-atlet', [LombaController::class, 'formA1NamaAtlet'])->name('lomba.form_a1_namaatlet');

});