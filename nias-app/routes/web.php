<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NiasController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('auth.login.show'));

Route::get('/login',     [AuthController::class, 'showLogin'])->name('auth.login.show');
Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
Route::get('/register',  [AuthController::class, 'showRegister'])->name('auth.register.show');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

// ── Protected ────────────────────────────────────────────────────
Route::post('/logout', [AuthController::class, 'logout'])
->name('auth.logout')
->middleware('auth');

Route::middleware('auth')->group(function () {

    // ── Admin Routes ──
    Route::get('/admin/existing', [AdminController::class, 'existing'])
    ->name('admin.existing');

    // Club info helper
    Route::get('/nias/clubinfo', function () {
        $club = request('club');
        $info = \App\Models\Nias::$clubLookup[$club] ?? null;
        if (!$info) return response()->json(['found' => false]);
        return response()->json([
            'found'    => true,
            'kdjenis'  => $info[0],
            'jenis'    => $info[1],
            'kdkota'   => $info[2],
            'namakota' => $info[3],
        ]);
    })->name('nias.clubinfo');

    // Export CSV
    Route::get('/nias/export', [NiasController::class, 'export'])
    ->name('nias.export');

    Route::get('/nias/update-data', [NiasController::class, 'showUpdateForm'])->name('nias.update-data');
    Route::get('/nias/existing',     [NiasController::class, 'existing'])->name('nias.existing');

    // NIAS CRUD — explicit routes agar tidak bentrok
    Route::get('/nias',                  [NiasController::class, 'index'])->name('nias.index');
    Route::get('/nias/create',           [NiasController::class, 'create'])->name('nias.create');
    Route::post('/nias',                 [NiasController::class, 'store'])->name('nias.store');
    Route::get('/nias/{id}',             [NiasController::class, 'show'])->name('nias.show');
    Route::get('/nias/{id}/edit',        [NiasController::class, 'edit'])->name('nias.edit');
    Route::put('/nias/{id}',             [NiasController::class, 'update'])->name('nias.update');
    Route::delete('/nias/{id}',          [NiasController::class, 'destroy'])->name('nias.destroy');
    Route::delete('/nias-selected',      [NiasController::class, 'destroySelected'])->name('nias.destroy-selected');
    Route::delete('/nias-all',           [NiasController::class, 'destroyAll'])->name('nias.destroy-all');
    Route::post('/nias/send-email',      [NiasController::class, 'sendEmail'])->name('nias.send-email');

});
