<?php

namespace App\Http\Controllers;

use App\Models\Nias;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // =========================================================================
    // SHOW LOGIN
    // =========================================================================
    public function showLogin()
    {
        // Kalau sudah login, langsung ke dashboard
        if (Auth::check()) {
            return redirect()->route('nias.index');
        }

        return view('auth.login');
    }

    // =========================================================================
    // PROSES LOGIN
    // =========================================================================
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Cek apakah ada user dengan email ini
        $userExists = User::where('email', $request->email)->exists();

        if (!$userExists) {
            // Belum ada akun → arahkan ke halaman daftar
            return redirect()->route('auth.register.show')
            ->with('info', 'Email belum terdaftar. Silakan buat akun terlebih dahulu.')
            ->withInput(['email' => $request->email]);
        }

        // Ada akun, coba login
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // ✨ REDIRECT BERDASARKAN ROLE
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.existing')
                ->with('success', 'Selamat datang, Admin ' . Auth::user()->nama . '!');
            }

            return redirect()->intended(route('nias.index'))
            ->with('success', 'Selamat datang, ' . Auth::user()->nama . '!');
        }

        // Password salah
        return back()
        ->withErrors(['password' => 'Password yang kamu masukkan salah.'])
        ->withInput(['email' => $request->email]);
    }

    // =========================================================================
    // SHOW REGISTER
    // =========================================================================
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('nias.index');
        }

        $clubs = array_keys(Nias::$clubLookup);
        sort($clubs);

        return view('auth.register', compact('clubs'));
    }

    // =========================================================================
    // PROSES REGISTER
    // =========================================================================
    public function register(Request $request)
    {
        $request->validate([
            'nama'                  => 'required|string|max:100',
            'gender'                => 'required|in:L,P',
            'namaclub'              => 'required|string|max:100',
            'email'                 => 'required|email|max:100|unique:users,email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ], [
            'nama.required'      => 'Nama lengkap wajib diisi.',
            'gender.required'    => 'Jenis kelamin wajib dipilih.',
            'namaclub.required'  => 'Klub wajib dipilih.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email ini sudah terdaftar. Silakan login.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'nama'     => strtoupper(trim($request->nama)),
            'gender'   => $request->gender,
            'namaclub' => $request->namaclub,
            'role'     => 'regular',
            'email'    => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
        ]);

        // Auto login setelah daftar
        Auth::login($user);

        return redirect()->route('nias.index')
            ->with('success', 'Akun berhasil dibuat! Selamat datang, ' . $user->nama . '.');
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login.show')
            ->with('success', 'Kamu berhasil logout.');
    }
}
