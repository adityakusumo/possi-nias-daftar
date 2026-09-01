<?php

namespace App\Http\Controllers;

use App\Models\Nias;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use App\Models\AppSetting;

class AuthController extends Controller
{
    // =========================================================================
    // SHOW LOGIN
    // =========================================================================
    public function showLogin()
    {
        // Kalau sudah login, langsung ke halaman NIAS
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
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'cf-turnstile-response' => 'required|string',
        ], [
            'cf-turnstile-response.required' => 'Silakan selesaikan verifikasi bahwa Anda bukan robot.',
        ]);

        if (!$this->turnstileIsValid($request)) {
            return back()->withErrors(['cf-turnstile-response' => 'Verifikasi keamanan gagal. Silakan coba lagi.'])
                ->withInput(['email' => $validated['email']]);
        }

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

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

            // Langsung ke halaman NIAS
            return redirect()->route('nias.index')
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
            return redirect()->route('welcome');
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
            'namaclub'              => ['required', 'string', 'max:100', Rule::in(array_keys(Nias::$clubLookup))],
            'email'                 => 'required|email|max:100|unique:users,email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
            'cf-turnstile-response' => 'required|string',
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
            'cf-turnstile-response.required' => 'Silakan selesaikan verifikasi bahwa Anda bukan robot.',
        ]);

        if (!$this->turnstileIsValid($request)) {
            return back()->withErrors(['cf-turnstile-response' => 'Verifikasi keamanan gagal. Silakan coba lagi.'])
                ->withInput($request->except('password', 'password_confirmation', 'cf-turnstile-response'));
        }

        // Cek batas akun per club sebelum buat akun baru
        $namaclub   = $request->namaclub;
        $maxAllowed = AppSetting::getMaxAccountsForClub($namaclub);
        $currentCount = User::where('namaclub', $namaclub)->where('role', 'regular')->count();

        if ($currentCount >= $maxAllowed) {
            return back()
                ->withErrors(['namaclub' => "Club {$namaclub} sudah mencapai batas maksimum {$maxAllowed} akun pelatih."])
                ->withInput();
        }

        $user = User::create([
            'nama'     => strtoupper(trim($request->nama)),
            'gender'   => $request->gender,
            'namaclub' => $namaclub,
            'role'     => 'regular',
            'email'    => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
        ]);

        // Auto login setelah daftar
        Auth::login($user);

        return redirect()->route('nias.index')
        ->with('success', 'Akun berhasil dibuat! Selamat datang, ' . $user->nama . '.');
    }

    private function turnstileIsValid(Request $request): bool
    {
        $secret = config('services.turnstile.secret_key');
        $token = $request->input('cf-turnstile-response');
        if (!$secret || !$token) return false;
        $response = Http::asForm()->timeout(5)->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            ['secret' => $secret, 'response' => $token, 'remoteip' => $request->ip()]
        );
        return $response->successful() && $response->json('success') === true;
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