<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    // ── Tampilkan form input email ────────────────────────────────
    public function showForm()
    {
        return view('auth.forgot_password');
    }

    // ── Kirim link reset ke email ─────────────────────────────────
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.');
        }

        // Email tidak ditemukan di database
        return back()
            ->withErrors(['email' => 'Email tidak ditemukan dalam sistem kami.'])
            ->withInput();
    }
}
