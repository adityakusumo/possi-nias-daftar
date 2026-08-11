<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — NIAS POSSI Jawa Timur</title>
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
            padding: 2rem; text-align: center;
        }
        .auth-header .logo-wrap {
            width: 80px; height: 80px;
            background: #fff; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: .8rem; overflow: hidden; padding: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,.2);
        }
        .auth-header .logo-wrap img {
            width: 100%; height: 100%; object-fit: contain; border-radius: 50%;
        }
        .auth-header h4 { color:#fff; font-weight:700; margin:0; }
        .auth-header p  { color:rgba(255,255,255,.65); font-size:.82rem; margin:.25rem 0 0; }
        .form-label { font-weight:600; font-size:.875rem; color:#2c3e50; }
        .btn-possi {
            background: linear-gradient(135deg, var(--possi-blue), #0057cc);
            color:#fff; border:none; font-weight:600;
        }
        .btn-possi:hover { background: linear-gradient(135deg, #002d6e, #003d8f); color:#fff; }
    </style>
</head>
<body>
<div class="auth-card card">
    <div class="auth-header">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo-possi.jpg') }}" alt="POSSI">
        </div>
        <h4>Reset Password</h4>
        <p>NIAS POSSI Jawa Timur</p>
    </div>

    <div class="card-body p-4">

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            {{-- Token & email sebagai hidden field --}}
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            {{-- Email (read-only untuk konfirmasi) --}}
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control bg-light"
                       value="{{ $email }}" readonly>
            </div>

            {{-- Password baru --}}
            <div class="mb-3">
                <label class="form-label" for="password">
                    <i class="bi bi-lock me-1"></i>Password Baru
                </label>
                <div class="input-group">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Minimal 8 karakter" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd1">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Konfirmasi password --}}
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">
                    <i class="bi bi-lock-fill me-1"></i>Konfirmasi Password Baru
                </label>
                <div class="input-group">
                    <input type="password" id="password_confirmation"
                           name="password_confirmation"
                           class="form-control @error('password_confirmation') is-invalid @enderror"
                           placeholder="Ulangi password baru" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd2">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                    @error('password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if($errors->has('email'))
            <div class="alert alert-danger small py-2 mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first('email') }}
            </div>
            @endif

            <button type="submit" class="btn btn-possi w-100 py-2">
                <i class="bi bi-shield-check me-1"></i>Reset Password
            </button>
        </form>

        <hr class="my-3">
        <p class="text-center small text-muted mb-0">
            <a href="{{ route('auth.login.show') }}" class="fw-semibold text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Login
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle show/hide password
    [['togglePwd1','eyeIcon1','password'], ['togglePwd2','eyeIcon2','password_confirmation']]
    .forEach(([btnId, iconId, inputId]) => {
        document.getElementById(btnId).addEventListener('click', function () {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    });
</script>
</body>
</html>
