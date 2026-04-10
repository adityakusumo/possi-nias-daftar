<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — NIAS POSSI Jawa Timur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        :root { --possi-blue:#003d8f; --possi-gold:#f0a500; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #003d8f 0%, #0057cc 60%, #1a73e8 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            width: 100%; max-width: 500px;
            border: none; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .auth-header {
            background: linear-gradient(135deg, #002d6e, #003d8f);
            border-radius: 16px 16px 0 0;
            padding: 1.6rem 2rem;
            text-align: center;
        }
        .auth-header .logo-icon {
            width: 56px; height: 56px;
            background: var(--possi-gold);
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color:#fff; margin-bottom:.6rem;
        }
        .auth-header h4 { color:#fff; font-weight:700; margin:0; font-size:1.1rem; }
        .auth-header p  { color:rgba(255,255,255,.65); font-size:.8rem; margin:0; }
        .form-label { font-weight:600; font-size:.875rem; color:#2c3e50; }
        .btn-possi {
            background: linear-gradient(135deg, var(--possi-blue), #0057cc);
            color:#fff; border:none; font-weight:600;
        }
        .btn-possi:hover { background: linear-gradient(135deg,#002d6e,#003d8f); color:#fff; }
        .section-title {
            font-size:.75rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.5px; color:#6c757d;
            border-bottom:1px solid #e9ecef; padding-bottom:.4rem; margin-bottom:.8rem;
        }
        /* password strength bar */
        #pwdStrengthBar { height:4px; border-radius:2px; transition:all .3s; }
    </style>
</head>
<body>
<div class="auth-card card">
    {{-- Header --}}
    <div class="auth-header">
        <div class="logo-icon"><i class="bi bi-person-plus"></i></div>
        <h4>Daftar Akun Pelatih / Official</h4>
        <p>NIAS POSSI Jawa Timur</p>
    </div>

    {{-- Body --}}
    <div class="card-body p-4">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Periksa kembali form:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info py-2 small">
                <i class="bi bi-info-circle me-1"></i>{{ session('info') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.register') }}" autocomplete="off">
            @csrf

            {{-- ── Data Pribadi Pelatih ── --}}
            <p class="section-title"><i class="bi bi-person me-1"></i>Data Pelatih / Official</p>

            {{-- Nama --}}
            <div class="mb-3">
                <label class="form-label" for="nama">
                    Nama Lengkap <span class="text-danger">*</span>
                </label>
                <input type="text" id="nama" name="nama"
                       class="form-control text-uppercase @error('nama') is-invalid @enderror"
                       value="{{ old('nama') }}"
                       placeholder="Nama lengkap pelatih" required maxlength="100">
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Gender --}}
            <div class="mb-3">
                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender"
                               id="gL" value="L" {{ old('gender','L') === 'L' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="gL">Laki-laki</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender"
                               id="gP" value="P" {{ old('gender') === 'P' ? 'checked' : '' }}>
                        <label class="form-check-label" for="gP">Perempuan</label>
                    </div>
                </div>
                @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Club --}}
            <div class="mb-4">
                <label class="form-label" for="namaclub">
                    Klub yang Diwakili <span class="text-danger">*</span>
                </label>
                <select name="namaclub" id="namaclub"
                        class="form-select @error('namaclub') is-invalid @enderror" required>
                    <option value="">— Pilih Klub —</option>
                    @foreach($clubs as $club)
                        <option value="{{ $club }}"
                            {{ old('namaclub') === $club ? 'selected' : '' }}>
                            {{ $club }}
                        </option>
                    @endforeach
                </select>
                @error('namaclub')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- ── Akun ── --}}
            <p class="section-title"><i class="bi bi-shield-lock me-1"></i>Data Akun</p>

            {{-- Email --}}
            <div class="mb-3">
                <label class="form-label" for="email">
                    Email <span class="text-danger">*</span>
                </label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="email@contoh.com" required maxlength="100">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text small">Email digunakan sebagai username untuk login.</div>
            </div>

            {{-- Password --}}
            <div class="mb-3">
                <label class="form-label" for="password">
                    Password <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Minimal 8 karakter" required minlength="8">
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd1">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                {{-- Strength bar --}}
                <div class="mt-1 bg-light rounded" style="height:4px">
                    <div id="pwdStrengthBar" style="width:0%"></div>
                </div>
                <div id="pwdStrengthText" class="form-text small"></div>
            </div>

            {{-- Konfirmasi Password --}}
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">
                    Konfirmasi Password <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           placeholder="Ulangi password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd2">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>
                <div id="pwdMatchMsg" class="form-text small"></div>
            </div>

            <button type="submit" class="btn btn-possi w-100 py-2" id="btnDaftar">
                <i class="bi bi-person-check me-1"></i>Daftar
            </button>
        </form>

        <hr class="my-3">
        <p class="text-center small text-muted mb-0">
            Sudah punya akun?
            <a href="{{ route('auth.login.show') }}" class="fw-semibold text-decoration-none">
                Login di sini
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    // Select2 klub
    $('#namaclub').select2({
        theme: 'bootstrap-5',
        placeholder: '— Ketik atau pilih klub —',
        allowClear: true,
        width: '100%',
    });

    // Auto uppercase nama
    $('#nama').on('input', function () {
        const p = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(p, p);
    });

    // Toggle show/hide password
    function togglePassword(btnId, inputId, iconId) {
        document.getElementById(btnId).addEventListener('click', function () {
            const inp  = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            inp.type   = inp.type === 'password' ? 'text' : 'password';
            icon.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }
    togglePassword('togglePwd1', 'password', 'eye1');
    togglePassword('togglePwd2', 'password_confirmation', 'eye2');

    // Password strength indicator
    $('#password').on('input', function () {
        const val = this.value;
        const bar = document.getElementById('pwdStrengthBar');
        const txt = document.getElementById('pwdStrengthText');
        let score = 0;
        if (val.length >= 8)              score++;
        if (/[A-Z]/.test(val))            score++;
        if (/[0-9]/.test(val))            score++;
        if (/[^A-Za-z0-9]/.test(val))    score++;

        const levels = [
            { w:'25%',  bg:'#dc3545', label:'Lemah' },
            { w:'50%',  bg:'#fd7e14', label:'Cukup' },
            { w:'75%',  bg:'#ffc107', label:'Baik' },
            { w:'100%', bg:'#198754', label:'Kuat' },
        ];
        if (val.length === 0) { bar.style.width='0%'; txt.textContent=''; return; }
        const lvl = levels[score - 1] || levels[0];
        bar.style.width      = lvl.w;
        bar.style.background = lvl.bg;
        txt.textContent      = 'Kekuatan: ' + lvl.label;
        txt.style.color      = lvl.bg;

        checkMatch();
    });

    // Password match check
    function checkMatch() {
        const p1  = document.getElementById('password').value;
        const p2  = document.getElementById('password_confirmation').value;
        const msg = document.getElementById('pwdMatchMsg');
        if (!p2) { msg.textContent = ''; return; }
        if (p1 === p2) {
            msg.textContent = '✓ Password cocok';
            msg.style.color = '#198754';
        } else {
            msg.textContent = '✗ Password tidak cocok';
            msg.style.color = '#dc3545';
        }
    }
    $('#password_confirmation').on('input', checkMatch);
});
</script>
</body>
</html>
