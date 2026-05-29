<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateLomba
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('lomba_user_id')) {
            return $next($request);
        }

        // Also allow if regular auth (nias user) is logged in
        if (auth()->check()) {
            return $next($request);
        }

        return redirect()->route('lomba.login')
            ->with('error', 'Silakan login dulu untuk mengakses Daftar Lomba.');
    }
}
