<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Token — POSSI Jatim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e65100 0%, #ff8f00 60%, #ffb300 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-verify {
            background: #fff; border-radius: 16px; padding: 2.5rem 2rem;
            width: 100%; max-width: 420px;
            box-shadow: 0 12px 40px rgba(0,0,0,.2);
        }
        .card-verify h4 { font-weight: 700; text-align: center; margin-bottom: .5rem; }
        .card-verify .subtitle { font-size: .85rem; color: #888; text-align: center; margin-bottom: 1.5rem; }
        .token-input { font-size: 1.5rem; text-align: center; letter-spacing: 6px; font-family: 'Courier New', monospace; font-weight: 700; }
        .btn-lomba { background: #e65100; border-color: #e65100; color: #fff; font-weight: 600; }
        .btn-lomba:hover { background: #bf360c; border-color: #bf360c; color: #fff; }
        .back-link { text-align: center; margin-top: 1rem; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="card-verify">
        <h4><i class="bi bi-shield-lock me-2"></i>Verifikasi Token</h4>
        <p class="subtitle">Token sudah dikirim ke <strong>{{ $email }}</strong></p>

        @if(session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('lomba.verify-token') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">Masukkan 8 Karakter Token</label>
                <input type="text" name="token" class="form-control token-input"
                       maxlength="8" minlength="8" placeholder="XXXXXXXX"
                       required autofocus autocomplete="off">
            </div>
            <button type="submit" class="btn btn-lomba w-100">
                <i class="bi bi-check-circle me-1"></i>Verifikasi & Login
            </button>
        </form>

        <div class="mt-3 text-center">
            <form method="POST" action="{{ route('lomba.resend-token') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" id="btnResend">
                    <i class="bi bi-arrow-repeat me-1"></i>Kirim Ulang Token
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="{{ route('lomba.login') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Ganti Email
            </a>
        </div>
    </div>
</body>
</html>
