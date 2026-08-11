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
        .btn-outline-lomba { color: #e65100; border-color: #e65100; font-weight: 600; }
        .btn-outline-lomba:hover { background: #e65100; color: #fff; }
        .back-link { text-align: center; margin-top: 1.25rem; font-size: .85rem; }
        .step-panel { display: none; }
        .step-panel.active { display: block; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo"><i class="bi bi-trophy-fill"></i></div>
        <h4>Daftar Lomba Login</h4>
        <p class="subtitle" id="subtitleText">Masukkan email untuk melanjutkan</p>

        @if(session('error'))
            <div class="alert alert-danger py-2 small" id="sessionError">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2 small" id="sessionSuccess">{{ session('success') }}</div>
        @endif
        <div id="ajaxAlert"></div>

        {{-- Step 1: Email Input --}}
        <div id="stepEmail" class="step-panel active">
            <form id="formEmail" method="POST" action="{{ route('lomba.check-email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alamat Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="inputEmail" class="form-control"
                               placeholder="contoh@email.com" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-lomba w-100" id="btnCheckEmail">
                    <i class="bi bi-arrow-right me-1"></i>Lanjutkan
                </button>
            </form>
        </div>

        {{-- Step 2a: Password Login (existing user) --}}
        <div id="stepPassword" class="step-panel">
            <div class="alert alert-info py-2 small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Email <strong id="displayEmailPw"></strong> sudah terdaftar. Silakan masukkan password.
            </div>
            <form id="formPassword" method="POST" action="{{ route('lomba.login-password') }}">
                @csrf
                <input type="hidden" name="email" id="hiddenEmailPw">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="inputPassword" class="form-control"
                               placeholder="Masukkan password" required minlength="6">
                        <button type="button" class="btn btn-outline-secondary" id="togglePw"
                                onclick="togglePassword()">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-lomba w-100" id="btnLoginPw">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </button>
            </form>
            <div class="text-center mt-2">
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="resetToEmail()">
                    <i class="bi bi-arrow-left me-1"></i>Gunakan email lain
                </button>
            </div>
        </div>

        {{-- Step 2b: Token Request (new user) --}}
        <div id="stepToken" class="step-panel">
            <div class="alert alert-warning py-2 small mb-3">
                <i class="bi bi-envelope-plus me-1"></i>
                Email <strong id="displayEmailToken"></strong> belum terdaftar. Klik tombol di bawah untuk menerima token verifikasi.
            </div>
            <form id="formRequestToken" method="POST" action="{{ route('lomba.request-token') }}">
                @csrf
                <input type="hidden" name="email" id="hiddenEmailToken">
                <button type="submit" class="btn btn-lomba w-100" id="btnRequestToken">
                    <i class="bi bi-send me-1"></i>Kirim Token ke Email
                </button>
            </form>
            <div class="text-center mt-2">
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="resetToEmail()">
                    <i class="bi bi-arrow-left me-1"></i>Gunakan email lain
                </button>
            </div>
        </div>

        <div class="back-link">
            <a href="{{ route('home') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Portal
            </a>
        </div>
    </div>

    <script>
    function showStep(stepId) {
        document.querySelectorAll('.step-panel').forEach(el => el.classList.remove('active'));
        document.getElementById(stepId).classList.add('active');
    }

    function showAlert(type, msg) {
        const container = document.getElementById('ajaxAlert');
        container.innerHTML = '<div class="alert alert-' + type + ' py-2 small">' + msg + '</div>';
    }

    function clearAlerts() {
        document.getElementById('ajaxAlert').innerHTML = '';
        const se = document.getElementById('sessionError');
        const ss = document.getElementById('sessionSuccess');
        if (se) se.style.display = 'none';
        if (ss) ss.style.display = 'none';
    }

    function togglePassword() {
        const pw = document.getElementById('inputPassword');
        const icon = document.querySelector('#togglePw i');
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            pw.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    function resetToEmail() {
        document.getElementById('inputEmail').value = '';
        document.getElementById('inputEmail').focus();
        document.getElementById('subtitleText').textContent = 'Masukkan email untuk melanjutkan';
        showStep('stepEmail');
    }

    {{-- Handle email form submission via AJAX --}}
    document.getElementById('formEmail').addEventListener('submit', function(e) {
        e.preventDefault();
        clearAlerts();

        const email = document.getElementById('inputEmail').value.trim();
        if (!email) return;

        const btn = document.getElementById('btnCheckEmail');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memeriksa...';

        fetch('{{ route("lomba.check-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email: email })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-right me-1"></i>Lanjutkan';

            if (data.exists && data.hasPassword) {
                {{-- Existing user with password → show password step --}}
                document.getElementById('displayEmailPw').textContent = data.email;
                document.getElementById('hiddenEmailPw').value = data.email;
                document.getElementById('subtitleText').textContent = 'Masukkan password untuk ' + data.email;
                document.getElementById('inputPassword').value = '';
                document.getElementById('inputPassword').focus();
                showStep('stepPassword');
            } else {
                {{-- New user or no password yet → show token step --}}
                document.getElementById('displayEmailToken').textContent = data.email;
                document.getElementById('hiddenEmailToken').value = data.email;
                document.getElementById('subtitleText').textContent = 'Verifikasi email ' + data.email;
                showStep('stepToken');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-right me-1"></i>Lanjutkan';
            showAlert('danger', 'Terjadi kesalahan. Silakan coba lagi.');
        });
    });

    {{-- Cooldown visual for token button --}}
    document.getElementById('formRequestToken')?.addEventListener('submit', function() {
        const btn = document.getElementById('btnRequestToken');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';
    });

    {{-- Cooldown for password form --}}
    document.getElementById('formPassword')?.addEventListener('submit', function() {
        const btn = document.getElementById('btnLoginPw');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Login...';
    });
    </script>
</body>
</html>