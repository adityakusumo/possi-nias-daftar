<?php

namespace App\Http\Controllers;

use App\Mail\LombaTokenMail;
use App\Models\LombaToken;
use App\Models\LombaUser;
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

    // ── Verify token ─────────────────────────────────────────────
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
        session()->forget('lomba_email');

        // Check if user already has kontingen data
        $hasKontingen = $lombaUser->kontingen()->exists();

        if ($hasKontingen) {
            return redirect()->route('lomba.index')
                ->with('success', 'Login berhasil! Selamat datang kembali.');
        }

        // First time: go to registration
        return redirect()->route('lomba.register')
            ->with('success', 'Login berhasil! Silakan lengkapi data kontingen.');
    }

    // ── Show registration form (kontingen + whatsapp) ────────────
    public function showRegister()
    {
        $lombaUser = $this->getLombaUser();
        if (!$lombaUser) {
            return redirect()->route('lomba.login')
                ->with('error', 'Silakan login dulu.');
        }

        $listKota = \App\Models\MstKota::orderBy('NAMAKOTA', 'asc')->get();

        return view('auth.lomba_register', compact('lombaUser', 'listKota'));
    }

    // ── Save kontingen & whatsapp data ───────────────────────────
    public function register(Request $request)
    {
        $lombaUser = $this->getLombaUser();
        if (!$lombaUser) {
            return redirect()->route('lomba.login')
                ->with('error', 'Sesi habis. Silakan login lagi.');
        }

        $validated = $request->validate([
            'nama'          => 'required|string|max:100',
            'no_wa'         => 'required|string|max:20',
            'jns_kompetisi' => 'required|in:K,P',
            'nama_kontingen'=> 'required|string|max:100',
            'jenis_wilayah' => 'required_if:jns_kompetisi,K|nullable|string|max:10',
            'nama_wilayah'  => 'required_if:jns_kompetisi,K|nullable|string|max:100',
            'provinsi'      => 'required|string|max:50',
        ], [
            'nama.required'           => 'Nama lengkap wajib diisi.',
            'no_wa.required'          => 'Nomor WhatsApp wajib diisi.',
            'jns_kompetisi.required'  => 'Jenis kompetisi wajib dipilih.',
            'nama_kontingen.required' => 'Nama kontingen wajib diisi.',
        ]);

        // Update lomba user
        $lombaUser->update([
            'nama'  => strtoupper(trim($validated['nama'])),
            'no_wa' => trim($validated['no_wa']),
        ]);

        // Create kontingen
        \App\Models\Kontingen::updateOrCreate(
            ['lomba_user_id' => $lombaUser->id],
            [
                'jns_kompetisi'  => $validated['jns_kompetisi'],
                'nama_kontingen' => strtoupper(trim($validated['nama_kontingen'])),
                'jenis_wilayah'  => $validated['jenis_wilayah'] ?? null,
                'nama_wilayah'   => $validated['nama_wilayah'] ? strtoupper(trim($validated['nama_wilayah'])) : null,
                'provinsi'       => strtoupper(trim($validated['provinsi'])),
            ]
        );

        return redirect()->route('lomba.index')
            ->with('success', 'Data kontingen berhasil disimpan!');
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

    // ── Helper: get current lomba user from session ──────────────
    private function getLombaUser(): ?LombaUser
    {
        $id = session('lomba_user_id');
        return $id ? LombaUser::find($id) : null;
    }
}
