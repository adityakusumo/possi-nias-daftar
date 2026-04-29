<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Nias;

class UserSettingController extends Controller
{
    // ── Tampilkan halaman setting user ────────────────────────────
    public function index()
    {
        $user = Auth::user();

        // Jumlah data NIAS yang sudah dikirim
        $jumlahTerkirim = Nias::where('user_id', $user->id)
                              ->where('is_sent', true)
                              ->count();

        return view('setting_user', compact('jumlahTerkirim'));
    }

    // ── Proses ganti password ─────────────────────────────────────
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.min'              => 'Password baru minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ]);

        $user = Auth::user();

        // Verifikasi password lama
        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Password saat ini tidak sesuai.'])
                ->withInput();
        }

        // Pastikan password baru berbeda dari yang lama
        if (Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors(['password' => 'Password baru tidak boleh sama dengan password saat ini.'])
                ->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('user.setting')
            ->with('success', 'Password berhasil diubah.');
    }
}
