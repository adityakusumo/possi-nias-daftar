<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NIAS POSSI Jawa Timur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --possi-blue:#003d8f; --possi-gold:#f0a500; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #003d8f 0%, #0057cc 60%, #1a73e8 100%);
            display: flex; align-items: center; justify-content: center;
        }
        .auth-card {
            width: 100%; max-width: 420px;
            border: none; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .auth-header {
            background: linear-gradient(135deg, #002d6e, #003d8f);
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .auth-header .logo-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            overflow: hidden;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: .8rem;
            background: #fff;
            padding: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,.2);
        }
        .auth-header .logo-icon img {
            width: 100%; height: 100%;
            object-fit: contain; border-radius: 50%;
        }
        .auth-header h4 { color:#fff; font-weight:700; margin:0; }
        .auth-header p  { color:rgba(255,255,255,.65); font-size:.82rem; margin:0; }
        .form-label { font-weight:600; font-size:.875rem; color:#2c3e50; }
        .btn-possi {
            background: linear-gradient(135deg, var(--possi-blue), #0057cc);
            color: #fff; border: none; font-weight: 600;
        }
        .btn-possi:hover { background: linear-gradient(135deg, #002d6e, #003d8f); color:#fff; }
        .divider { border-color: #dee2e6; }
    </style>
</head>
<body>
<div class="auth-card card">
    {{-- Header --}}
    <div class="auth-header">
        <div class="logo-icon">
            <img src="{{ asset('images/logo-possi.jpg') }}" alt="POSSI Jawa Timur">
        </div>
        <h4>NIAS POSSI</h4>
        <p>Jawa Timur — Portal Pelatih &amp; Official</p>
    </div>

    {{-- Body --}}
    <div class="card-body p-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 small">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show py-2 small">
                <i class="bi bi-info-circle me-1"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <h6 class="fw-bold mb-3 text-center text-muted">Masuk ke Akun Anda</h6>

        <form method="POST" action="{{ route('auth.login') }}">
            @csrf

            {{-- Email --}}
            <div class="mb-3">
                <label class="form-label" for="email">
                    <i class="bi bi-envelope me-1"></i>Email
                </label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="email@contoh.com" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Password --}}
            <div class="mb-3">
                <label class="form-label" for="password">
                    <i class="bi bi-lock me-1"></i>Password
                </label>
                <div class="input-group">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Remember --}}
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label small" for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="btn btn-possi w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
            </button>
        </form>

        <hr class="divider my-3">

        <p class="text-center small text-muted mb-0">
            Belum punya akun?
            <a href="{{ route('auth.register.show') }}" class="fw-semibold text-decoration-none">
                Daftar di sini
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>
