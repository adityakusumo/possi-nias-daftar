<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pendaftaran NIAS') — POSSI Jawa Timur</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <style>
        :root {
            --possi-blue: #003d8f;
            --possi-gold: #f0a500;
            --possi-light: #e8f0fb;
        }

        body {
            background: #f4f6fb;
            font-family: 'Segoe UI', sans-serif;
        }

        .navbar-possi {
            background: linear-gradient(135deg, var(--possi-blue) 0%, #0057cc 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, .25);
        }

        .navbar-possi .navbar-brand {
            color: #fff;
            font-weight: 700;
        }

        .navbar-possi .navbar-brand span {
            color: var(--possi-gold);
        }

        .navbar-possi .nav-link {
            color: rgba(255, 255, 255, .85) !important;
        }

        .navbar-possi .nav-link:hover,
        .navbar-possi .nav-link.active {
            color: #fff !important;
        }

        /* User badge di navbar */
        .user-badge {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 20px;
            padding: .25rem .75rem;
            color: #fff;
            font-size: .82rem;
        }

        .user-badge .club-tag {
            background: var(--possi-gold);
            color: #000;
            border-radius: 10px;
            padding: 1px 7px;
            font-size: .75rem;
            font-weight: 700;
        }

        .page-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 60, 140, .10);
        }

        .page-card .card-header {
            background: linear-gradient(135deg, var(--possi-blue), #0057cc);
            color: #fff;
            border-radius: 12px 12px 0 0 !important;
            padding: .9rem 1.4rem;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: .875rem;
        }

        .section-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .07);
        }

        .section-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e3eaf7;
            border-radius: 10px 10px 0 0 !important;
        }

        .table-nias thead {
            background: var(--possi-blue);
            color: #fff;
            font-size: .82rem;
        }

        .table-nias tbody tr:hover {
            background: var(--possi-light);
        }

        .table-nias td,
        .table-nias th {
            vertical-align: middle;
        }

        .badge-aktif {
            background: #198754;
        }

        .badge-expired {
            background: #dc3545;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
            background-color: #f8f9fa;
            border: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .nav-tabs .nav-link.active {
            color: #003d8f !important;
            background-color: #fff !important;
            border-bottom: 3px solid #003d8f !important;
        }

        .nav-tabs .nav-link.disabled {
            background-color: #e9ecef;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .nav-tabs .nav-link:hover:not(.disabled) {
            background-color: #e2e6ea;
            border-bottom: 2px solid #dee2e6;
        }

        footer {
            background: var(--possi-blue);
            color: rgba(255, 255, 255, .65);
            font-size: .8rem;
            padding: .9rem 0;
            margin-top: 2rem;
        }
    </style>
    @stack('styles')
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-possi mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('nias.index') }}">
                <img src="{{ asset('images/logo-possi.jpg') }}" alt="POSSI" height="36" class="me-2"
                    style="object-fit:contain;">POSSI <span>Jawa Timur</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto gap-1">

                    {{-- ── NIAS Navigation ── --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('nias.index') ? 'active fw-semibold' : '' }}"
                            href="{{ route('nias.index') }}">
                            <i class="bi bi-list-ul me-1"></i>Data NIAS
                        </a>
                    </li>

                    {{-- Kembali ke Daftar NIAS — muncul di subpage NIAS --}}
                    @if(request()->routeIs('nias.create', 'nias.edit', 'nias.show', 'nias.update-data', 'nias.existing'))
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-warning" href="{{ route('nias.index') }}">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                    </li>
                    @endif

                    {{-- ── Lomba Navigation ── --}}
                    @if(request()->routeIs('lomba.*'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('lomba.index') ? 'active fw-semibold' : '' }}"
                            href="{{ route('lomba.index') }}">
                            <i class="bi bi-trophy me-1"></i>Beranda Lomba
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('lomba.form_a1', 'lomba.form_a1_namaatlet') ? 'active fw-semibold' : '' }}"
                            href="{{ route('lomba.form_a1') }}">
                            <i class="bi bi-pencil-square me-1"></i>Isi Data Lomba
                        </a>
                    </li>
                    @endif

                </ul>

                {{-- User info + logout — NIAS user --}}
                @auth
                    <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                        <div class="user-badge d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ Auth::user()->nama }}</span>
                            <span class="club-tag">{{ Auth::user()->namaclub }}</span>
                        </div>

                        <a href="{{ route('user.setting') }}"
                            class="btn btn-sm btn-outline-light {{ request()->routeIs('user.setting') ? 'active' : '' }}"
                            title="Pengaturan Akun">
                            <i class="bi bi-person-gear me-1"></i>Akun
                        </a>

                        <form method="POST" action="{{ route('auth.logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light" title="Logout">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                    </div>
                @endauth

                {{-- User info + logout — Lomba user (session-based) --}}
                @if(!auth()->check() && session()->has('lomba_user_id'))
                    @php
                        $lombaUser = \App\Models\LombaUser::find(session('lomba_user_id'));
                    @endphp
                    @if($lombaUser)
                    <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                        <div class="user-badge d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ $lombaUser->nama ?? $lombaUser->email }}</span>
                        </div>

                        <a href="{{ route('lomba.account') }}"
                            class="btn btn-sm btn-outline-light"
                            title="Pengaturan Akun Lomba">
                            <i class="bi bi-person-gear me-1"></i>Akun
                        </a>

                        <form method="POST" action="{{ route('lomba.logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light" title="Logout Lomba">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </nav>

    <main class="container mb-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="text-center">
        <div class="container">
            &copy; {{ date('Y') }} POSSI Jawa Timur &mdash; Sistem Pendaftaran NIAS
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')
</body>

</html>