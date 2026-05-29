<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Lomba Login — POSSI Jatim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e65100 0%, #ff8f00 60%, #ffb300 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff; border-radius: 16px; padding: 2.5rem 2rem;
            width: 100%; max-width: 420px;
            box-shadow: 0 12px 40px rgba(0,0,0,.2);
        }
        .login-card .logo { text-align: center; margin-bottom: 1.5rem; }
        .login-card .logo i { font-size: 3rem; color: #e65100; }
        .login-card h4 { font-weight: 700; text-align: center; margin-bottom: .25rem; }
        .login-card .subtitle { font-size: .85rem; color: #888; text-align: center; margin-bottom: 1.75rem; }
        .btn-lomba { background: #e65100; border-color: #e65100; color: #fff; font-weight: 600; }
        .btn-lomba:hover { background: #bf360c; border-color: #bf360c; color: #fff; }
        .back-link { text-align: center; margin-top: 1.25rem; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo"><i class="bi bi-trophy-fill"></i></div>
        <h4>Daftar Lomba Login</h4>
        <p class="subtitle">Masukkan email untuk menerima token login</p>

        @if(session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('lomba.request-token') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">Alamat Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="contoh@email.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn btn-lomba w-100" id="btnRequest">
                <i class="bi bi-send me-1"></i>Kirim Token
            </button>
        </form>

        <div class="back-link">
            <a href="{{ route('home') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Portal
            </a>
        </div>
    </div>

    <script>
        // Cooldown visual
        const form = document.querySelector('form');
        const btn = document.getElementById('btnRequest');
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';
        });
    </script>
</body>
</html>
