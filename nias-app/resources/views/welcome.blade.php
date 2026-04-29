<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Aplikasi — POSSI Jawa Timur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --possi-blue: #003d8f;
            --possi-gold: #f0a500;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #003d8f 0%, #0057cc 60%, #1a73e8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .welcome-header {
            text-align: center;
            color: #fff;
            margin-bottom: 2.5rem;
        }

        .welcome-header .logo-wrap {
            width: 80px;
            height: 80px;
            background: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .2);
            overflow: hidden;
            padding: 4px;
        }

        .welcome-header .logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        .welcome-header h3 {
            font-weight: 700;
            margin: 0;
        }

        .welcome-header p {
            color: rgba(255, 255, 255, .75);
            font-size: .9rem;
            margin: .25rem 0 0;
        }

        .app-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem 1.75rem;
            width: 100%;
            max-width: 280px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .18);
            transition: transform .2s, box-shadow .2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .app-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, .25);
            color: inherit;
        }

        .app-card .icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .app-card .icon-wrap.nias {
            background: #e8f0fb;
            color: var(--possi-blue);
        }

        .app-card .icon-wrap.lomba {
            background: #fff8e1;
            color: #e65100;
        }

        .app-card h5 {
            font-weight: 700;
            margin-bottom: .4rem;
        }

        .app-card p {
            font-size: .82rem;
            color: #666;
            margin: 0;
            line-height: 1.5;
        }

        .app-card .btn-choose {
            margin-top: 1.25rem;
            width: 100%;
            font-weight: 600;
            border-radius: 8px;
            padding: .5rem;
        }

        .user-info {
            color: rgba(255, 255, 255, .8);
            font-size: .82rem;
            margin-bottom: 1.75rem;
            text-align: center;
        }

        .user-info .name-badge {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 20px;
            padding: .2rem .9rem;
            display: inline-block;
        }

        .logout-link {
            margin-top: 2rem;
            color: rgba(255, 255, 255, .6);
            font-size: .8rem;
            text-align: center;
        }

        .logout-link a {
            color: rgba(255, 255, 255, .8);
            text-decoration: underline;
        }

        .logout-link a:hover {
            color: #fff;
        }
        .welcome-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--possi-blue) 0%, #0057cc 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,.25);
            padding: .5rem 1.25rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
        }
        body { padding-top: 54px; }
    </style>
</head>

<body>

    {{-- Navbar --}}
    <nav class="welcome-navbar">
        @auth
        <span class="text-white-50 small d-none d-sm-inline me-1">
            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->nama }}
        </span>
        <a href="{{ route('user.setting') }}" class="btn btn-sm btn-outline-light">
            <i class="bi bi-person-gear me-1"></i>Akun
        </a>
        <form method="POST" action="{{ route('auth.logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
        @endauth
    </nav>

    <div class="welcome-header">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo-possi.jpg') }}" alt="POSSI">
        </div>
        <h3>POSSI Jawa Timur</h3>
        <p>Sistem Informasi Finswimming</p>
    </div>

    <div class="user-info">
        Selamat datang, <span class="name-badge">
            <i class="bi bi-person-circle me-1"></i>
            {{ Auth::user()->nama }}
        </span>
    </div>

    <div class="d-flex flex-wrap gap-4 justify-content-center px-3">

        @php $isNiasOpen = \App\Models\AppSetting::isNiasOpen(); @endphp

        {{-- Kartu NIAS --}}
        <a href="{{ route('nias.index') }}" class="app-card" onclick="saveChoice('nias')">
            <div class="icon-wrap" style="background:#e3f2fd;color:#0d6efd;">
                <i class="bi bi-person-vcard"></i>
            </div>
            <div class="app-info">
                <h3>Pendaftaran NIAS</h3>
                <p>
                    @if($isNiasOpen)
                        Pendaftaran sedang <span class="text-success fw-semibold">dibuka</span>.
                    @else
                        Pendaftaran sedang <span class="text-danger fw-semibold">ditutup</span>.
                    @endif
                </p>
            </div>
        </a>

        {{-- Kartu Daftar Lomba --}}
        <a href="{{ route('lomba.index') }}" class="app-card" onclick="saveChoice('lomba')">
            <div class="icon-wrap" style="background:#fff3e0;color:#e65100;">
                <i class="bi bi-trophy"></i>
            </div>
            <div class="app-info">
                <h3>Pendaftaran Lomba</h3>
                <p>Daftarkan kontingen dan atlet untuk kompetisi.</p>
            </div>
        </a>

        {{-- Kartu Admin Setting --}}
        @if(Auth::user()->role === 'admin')
            <a href="{{ route('settings') }}" class="app-card">
                <div class="icon-wrap" style="background:#f3e5f5;color:#6a1b9a;">
                    <i class="bi bi-gear-fill"></i>
                </div>
                <div class="app-info">
                    <h3>Pengaturan Sistem</h3>
                    <p>Panel Kontrol Admin (NIAS & Lomba)</p>
                </div>
            </a>
        @endif
    </div>





    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveChoice(app) {
            fetch('{{ route("welcome.saveChoice") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ app: app })
            });
        }
    </script>
</body>

</html>