<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    // -------------------------------------------------------------------------
    // SHOW — Halaman pilihan aplikasi
    // -------------------------------------------------------------------------
    public function show()
    {
        $user = Auth::user();

        // Guest: just show the welcome page directly (login choice cards)
        if (!$user) {
            return view('welcome');
        }

        // Regular NIAS user: tidak boleh mengakses welcome page
        // (karena bisa masuk ke daftar lomba yang butuh akun dedicated)
        if ($user->role !== 'admin') {
            return redirect()->route('nias.index');
        }

        // Admin: jika sudah punya pilihan terakhir di session → langsung redirect
        if (session()->has('last_app')) {
            $lastApp = session('last_app');
            if ($lastApp === 'nias') {
                return redirect()->route('nias.index');
            }
            if ($lastApp === 'lomba') {
                if (!config('lomba.enabled')) {
                    session()->forget('last_app');
                    return view('welcome');
                }
                return redirect()->route('lomba.index');
            }
        }

        return view('welcome');
    }

    // -------------------------------------------------------------------------
    // SAVE CHOICE — Simpan pilihan aplikasi ke session (dipanggil via fetch)
    // -------------------------------------------------------------------------
    public function saveChoice(Request $request)
    {
        $app = $request->input('app');

        if (in_array($app, ['nias', 'lomba'])) {
            session(['last_app' => $app]);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // RESET — Hapus session pilihan, kembali ke halaman pilihan (untuk admin)
    // -------------------------------------------------------------------------
    public function reset()
    {
        session()->forget('last_app');
        return redirect()->route('welcome');
    }
}
