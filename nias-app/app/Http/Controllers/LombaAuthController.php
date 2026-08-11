<?php

namespace App\Http\Controllers;

use App\Mail\LombaTokenMail;
use App\Models\LombaToken;
use App\Models\LombaUser;
use App\Models\Kompetisi;
use App\Models\MstKota;
use App\Models\Nias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LombaAuthController extends Controller
{
    // ── Show login form (enter email) ────────────────────────────
    public function showLogin()
    {
        // Already logged in as lomba user? → redirect to lomba index
        if (session()->has('lomba_user_id')) {
            return redirect()->route('lomba.index');
        }
        return view('auth.lomba_login');
    }

    // ── Check email: registered (has password + matching kompetisi) or new ──
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100',
        ]);

        $email = strtolower(trim($request->email));
        $user  = LombaUser::where('email', $email)->first();

        // Only consider user "registered with password" if their kontingen
        // matches the current kompetisi setting. Each kompetisi is a separate event.
        $hasMatchingCompetition = $user && $this->kontingenMatchesCurrent($user);

        $response = [
            'exists'      => $user ? true : false,
            'hasPassword' => $user && $user->isRegistered() && $hasMatchingCompetition ? true : false,
            'email'       => $email,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($response);
        }

        // Non-AJAX fallback: store email in session and redirect back
        session(['lomba_email' => $email]);
        return back()->withInput();
    }

    // ── Login with password (returning user) ─────────────────────
    public function loginWithPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:100',
            'password' => 'required|string|min:6',
        ]);

        $email = strtolower(trim($request->email));
        $user  = LombaUser::where('email', $email)->first();

        if (!$user || !$user->isRegistered() || !$user->verifyPassword($request->password)) {
            return back()
                ->withInput()
                ->with('error', 'Email atau password salah.');
        }

        // Check if kontingen matches current kompetisi setting
        if (!$this->kontingenMatchesCurrent($user)) {
            return back()
                ->withInput()
                ->with('error', 'Akun ini terdaftar untuk kompetisi lain. Silakan gunakan token untuk mendaftar kompetisi saat ini.');
        }

        // Login: store lomba user ID in session
        session(['lomba_user_id' => $user->id]);
        session()->forget('lomba_email');

        return redirect()->route('lomba.index')
            ->with('success', 'Login berhasil! Selamat datang kembali.');
    }

    // ── Request token: validate email, generate token, send email ─
    public function requestToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100',
        ]);

        $email = strtolower(trim($request->email));

        // ── Cooldown: cek token terakhir untuk email ini (1 menit) ──
        $lastToken = LombaToken::where('email', $email)
            ->where('created_at', '>', now()->subMinute())
            ->first();

        if ($lastToken) {
            $secondsLeft = 60 - now()->diffInSeconds($lastToken->created_at);
            return back()
                ->withInput()
                ->with('error', "Silakan tunggu {$secondsLeft} detik lagi sebelum meminta token baru.");
        }

        // ── Generate unique token ─────────────────────────────────
        $token = strtoupper(Str::random(8));

        LombaToken::create([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => now()->addMinutes(10),
        ]);

        // ── Send email ────────────────────────────────────────────
        try {
            Mail::to($email)->send(new LombaTokenMail($token));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal mengirim email. Silakan coba lagi nanti.');
        }

        // Store email in session so verify page knows it
        session(['lomba_email' => $email]);

        return redirect()->route('lomba.verify')
            ->with('success', 'Token sudah dikirim ke ' . $email . '. Cek inbox kamu.');
    }

    // ── Show verify form (enter token) ───────────────────────────
    public function showVerify()
    {
        if (!session()->has('lomba_email')) {
            return redirect()->route('lomba.login');
        }
        return view('auth.lomba_verify', [
            'email' => session('lomba_email'),
        ]);
    }

    // ── Verify token → login → if new user redirect to register ──
    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:8',
        ]);

        $email = session('lomba_email');
        if (!$email) {
            return redirect()->route('lomba.login')
                ->with('error', 'Sesi habis. Silakan masukkan email lagi.');
        }

        $token = strtoupper(trim($request->token));

        $tokenRecord = LombaToken::where('email', $email)
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return back()->with('error', 'Token tidak valid atau sudah kadaluarsa.');
        }

        // Mark token as used
        $tokenRecord->update(['used_at' => now()]);

        // Find or create lomba user
        $lombaUser = LombaUser::firstOrCreate(
            ['email' => $email],
            ['nama' => null, 'no_wa' => null]
        );

        // Login: store lomba user ID in session
        session(['lomba_user_id' => $lombaUser->id]);
        // Keep lomba_email in session so register page knows it's a new signup
        // (don't forget it yet, will be cleared after successful registration)

        // Check if user needs to set password & has matching kompetisi
        $needsPassword     = !$lombaUser->isRegistered();
        $matchesKompetisi  = $this->kontingenMatchesCurrent($lombaUser);

        if ($matchesKompetisi && !$needsPassword) {
            // Existing user with password and matching kompetisi → full access
            session()->forget('lomba_email');
            return redirect()->route('lomba.index')
                ->with('success', 'Login berhasil! Selamat datang kembali.');
        }

        // New user OR existing user for different kompetisi → go to registration
        return redirect()->route('lomba.register')
            ->with('success', 'Token valid! Silakan daftarkan kontingen untuk kompetisi ini.');
    }

    // ── Show registration form (kontingen + whatsapp + password) ──
    public function showRegister()
    {
        $lombaUser = $this->getLombaUser();
        if (!$lombaUser) {
            return redirect()->route('lomba.login')
                ->with('error', 'Silakan login dulu.');
        }

        // Has kontingen for this kompetisi? If so, skip kontingen form (just set password)
        $hasKontingen = $this->kontingenMatchesCurrent($lombaUser);

        // Get kompetisi type for dynamic form fields
        $kompetisi = Kompetisi::first();
        $jnsKompetisi = $kompetisi ? $kompetisi->JNSKOMPETISI : 'K';

        // Data for dropdowns
        $rawKota = MstKota::orderBy('NAMAKOTA', 'asc')->get(['KDJENIS', 'JENIS', 'NAMAKOTA']);
        $listKota = $rawKota
            ->reject(function ($k) {
                // Filter out cities outside Jawa Timur (Jakarta)
                return str_starts_with(strtoupper(trim($k->NAMAKOTA)), 'JAKARTA');
            })
            ->map(function ($k) {
                return (object) [
                    'value' => strtoupper(trim($k->JENIS)) . '|' . strtoupper(trim($k->NAMAKOTA)),
                    'label' => strtoupper(trim($k->JENIS)) . ' ' . strtoupper(trim($k->NAMAKOTA)),
                ];
            })->sortBy('label');
        $clubList = \App\Models\NiasExisting::select('NAMACLUB')
            ->whereNotNull('NAMACLUB')
            ->where('NAMACLUB', '!=', '')
            ->distinct()
            ->orderBy('NAMACLUB')
            ->pluck('NAMACLUB');

        // Indonesian provinces
        $provinsiList = [
            'ACEH', 'SUMATERA UTARA', 'SUMATERA BARAT', 'RIAU', 'JAMBI',
            'SUMATERA SELATAN', 'BENGKULU', 'LAMPUNG', 'KEP. BANGKA BELITUNG',
            'KEP. RIAU', 'DKI JAKARTA', 'JAWA BARAT', 'JAWA TENGAH',
            'DI YOGYAKARTA', 'JAWA TIMUR', 'BANTEN', 'BALI',
            'NUSA TENGGARA BARAT', 'NUSA TENGGARA TIMUR', 'KALIMANTAN BARAT',
            'KALIMANTAN TENGAH', 'KALIMANTAN SELATAN', 'KALIMANTAN TIMUR',
            'KALIMANTAN UTARA', 'SULAWESI UTARA', 'SULAWESI TENGAH',
            'SULAWESI SELATAN', 'SULAWESI TENGGARA', 'GORONTALO',
            'SULAWESI BARAT', 'MALUKU', 'MALUKU UTARA',
            'PAPUA', 'PAPUA BARAT', 'PAPUA SELATAN', 'PAPUA TENGAH',
            'PAPUA PEGUNUNGAN', 'PAPUA BARAT DAYA',
        ];

        return view('auth.lomba_register', compact(
            'lombaUser', 'jnsKompetisi', 'listKota', 'clubList', 'provinsiList', 'hasKontingen'
        ));
    }

    // ── Save kontingen & whatsapp & password data ────────────────
    public function register(Request $request)
    {
        $lombaUser = $this->getLombaUser();
        if (!$lombaUser) {
            return redirect()->route('lomba.login')
                ->with('error', 'Sesi habis. Silakan login lagi.');
        }

        $hasKontingen  = $this->kontingenMatchesCurrent($lombaUser);
        $needsPassword = !$lombaUser->isRegistered();

        // Determine kompetisi type before building rules
        $jnsKompetisi = Kompetisi::getJenis();

        // Only require kontingen data if user doesn't already have it
        $kontingenRules = [];
        if (!$hasKontingen) {
            if ($jnsKompetisi === 'K') {
                // Antar kota: fillable text with datalist + province
                $kontingenRules = [
                    'kota_kab'      => 'required|string|max:100',
                    'provinsi'      => 'nullable|string|max:50',
                ];
            } else {
                $kontingenRules = [
                    'nama_kontingen'=> 'required|string|max:100',
                    'jenis'         => 'nullable|string|max:50',
                    'nama_wilayah'  => 'nullable|string|max:100',
                    'provinsi'      => 'nullable|string|max:50',
                ];
            }
        }

        $validated = $request->validate(array_merge([
            'nama'     => 'required|string|max:100',
            'no_wa'    => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ], $kontingenRules), [
            'nama.required'           => 'Nama lengkap wajib diisi.',
            'no_wa.required'          => 'Nomor WhatsApp wajib diisi.',
            'password.required'       => 'Password wajib diisi.',
            'password.confirmed'      => 'Konfirmasi password tidak cocok.',
            'nama_kontingen.required' => 'Nama kontingen wajib diisi.',
        ]);

        // Update lomba user (always: name, phone, password)
        $lombaUser->update([
            'nama'     => strtoupper(trim($validated['nama'])),
            'no_wa'    => trim($validated['no_wa']),
            'password' => $validated['password'], // auto-hashed by mutator
        ]);

        // Only create/update kontingen if user doesn't already have one
        if (!$hasKontingen) {
            $kontingenData = [
                'jns_kompetisi'  => $jnsKompetisi,
                'provinsi'       => 'JAWA TIMUR',
            ];

            if ($jnsKompetisi === 'K') {
                // Parse "KOTA MALANG" or "KOTA|MALANG" → jenis="KOTA", nama_wilayah="MALANG"
                $raw = strtoupper(trim($validated['kota_kab']));
                if (str_contains($raw, '|')) {
                    $parts = explode('|', $raw, 2);
                    $jenis = trim($parts[0] ?? 'KOTA');
                    $kota  = trim($parts[1] ?? '');
                } else {
                    // Split on first space: "KOTA MALANG" → ["KOTA", "MALANG"]
                    $parts = explode(' ', $raw, 2);
                    $jenis = trim($parts[0] ?? 'KOTA');
                    $kota  = trim($parts[1] ?? $raw);
                    // If no space or first part isn't KOTA/KAB, treat whole as city name
                    if (!in_array($jenis, ['KOTA', 'KAB', 'KAB.', 'KABUPATEN'])) {
                        $kota  = $raw;
                        $jenis = 'KOTA';
                    }
                }
                $sep   = ($jenis === 'KAB' || $jenis === 'KAB.' || $jenis === 'KABUPATEN') ? '. ' : ' ';
                $jenisNormalized = ($jenis === 'KAB.' || $jenis === 'KABUPATEN') ? 'KAB' : $jenis;
                $kontingenData['jenis_wilayah']  = $jenisNormalized;
                $kontingenData['nama_wilayah']   = $kota;
                $kontingenData['nama_kontingen'] = $jenisNormalized . $sep . $kota;
                $kontingenData['provinsi']       = strtoupper(trim($validated['provinsi'] ?? 'JAWA TIMUR'));
            } elseif ($jnsKompetisi === 'P') {
                $kontingenData['jenis_wilayah'] = 'PROP';
                $kontingenData['nama_wilayah']  = strtoupper(trim($validated['provinsi'] ?? 'JAWA TIMUR'));
                $kontingenData['provinsi']      = strtoupper(trim($validated['provinsi'] ?? 'JAWA TIMUR'));
            } else {
                $kontingenData['jenis_wilayah']  = null;
                $kontingenData['nama_wilayah']   = null;
                $kontingenData['provinsi']       = 'JAWA TIMUR';
                $kontingenData['nama_kontingen'] = strtoupper(trim($validated['nama_kontingen']));
            }

            \App\Models\Kontingen::updateOrCreate(
                ['lomba_user_id' => $lombaUser->id],
                $kontingenData
            );
        }

        // Clear email session now that registration is complete
        session()->forget('lomba_email');

        return redirect()->route('lomba.index')
            ->with('success', 'Data berhasil disimpan! Sekarang kamu bisa login dengan email dan password.');
    }

    // ── Logout ───────────────────────────────────────────────────
    public function logout()
    {
        session()->forget('lomba_user_id');
        session()->forget('lomba_email');

        return redirect()->route('home')
            ->with('success', 'Kamu berhasil logout.');
    }

    // ── Resend token (from verify page) ──────────────────────────
    public function resendToken()
    {
        $email = session('lomba_email');
        if (!$email) {
            return redirect()->route('lomba.login')
                ->with('error', 'Silakan masukkan email dulu.');
        }

        // Cooldown check
        $lastToken = LombaToken::where('email', $email)
            ->where('created_at', '>', now()->subMinute())
            ->first();

        if ($lastToken) {
            $secondsLeft = 60 - now()->diffInSeconds($lastToken->created_at);
            return back()->with('error', "Silakan tunggu {$secondsLeft} detik lagi.");
        }

        $token = strtoupper(Str::random(8));

        LombaToken::create([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($email)->send(new LombaTokenMail($token));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim email. Coba lagi.');
        }

        return back()->with('success', 'Token baru sudah dikirim ke ' . $email);
    }

    // ── Show account settings (change password) ─────────────────
    public function showAccountSettings()
    {
        $lombaUser = $this->getLombaUser();
        if (!$lombaUser) {
            return redirect()->route('lomba.login')
                ->with('error', 'Silakan login dulu.');
        }
        return view('auth.lomba_account', compact('lombaUser'));
    }

    // ── Update password (verify current, set new) ────────────────
    public function updatePassword(Request $request)
    {
        $lombaUser = $this->getLombaUser();
        if (!$lombaUser) {
            return redirect()->route('lomba.login')
                ->with('error', 'Silakan login dulu.');
        }

        $validated = $request->validate([
            'current_password'      => 'required|string|min:6',
            'new_password'          => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required'     => 'Password baru wajib diisi.',
            'new_password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Verify current password
        if (!$lombaUser->verifyPassword($validated['current_password'])) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        // Update to new password
        $lombaUser->update([
            'password' => $validated['new_password'],
        ]);

        return redirect()->route('lomba.account')
            ->with('success', 'Password berhasil diperbarui.');
    }

    // ── Helper: get current kompetisi type ───────────────────────
    private function getCurrentKompetisi(): string
    {
        return Kompetisi::getJenis();
    }

    // ── Helper: check if user's kontingen matches current kompetisi ──
    private function kontingenMatchesCurrent(LombaUser $user): bool
    {
        $kontingen = $user->kontingen;
        return $kontingen && $kontingen->jns_kompetisi === $this->getCurrentKompetisi();
    }

    // ── Helper: get current lomba user from session ──────────────
    private function getLombaUser(): ?LombaUser
    {
        $id = session('lomba_user_id');
        return $id ? LombaUser::find($id) : null;
    }
}
