<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Data — POSSI Jatim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: #f5f5f5;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-register {
            background: #fff; border-radius: 16px; padding: 2rem;
            width: 100%; max-width: 600px;
            box-shadow: 0 8px 32px rgba(0,0,0,.1);
        }
        .card-register h4 { font-weight: 700; margin-bottom: .25rem; }
        .card-register .subtitle { font-size: .85rem; color: #888; margin-bottom: 1.5rem; }
        .btn-lomba { background: #e65100; border-color: #e65100; color: #fff; font-weight: 600; }
        .btn-lomba:hover { background: #bf360c; border-color: #bf360c; color: #fff; }
        .section-label { font-size: .85rem; font-weight: 700; color: #495057; }
    </style>
</head>
<body>
    <div class="card-register">
        <h4><i class="bi bi-person-plus me-2"></i>Lengkapi Data Pendaftaran</h4>
        @php
            $kompetisiLabel = match($jnsKompetisi) {
                'K' => 'ANTAR KOTA',
                'P' => 'ANTAR PROPINSI',
                'C' => 'ANTAR CLUB',
                default => 'ANTAR KOTA'
            };
        @endphp
        <p class="subtitle">
            Login sebagai <strong>{{ $lombaUser->email }}</strong> &mdash; Kompetisi <strong>{{ $kompetisiLabel }}</strong>
        </p>

        @if(session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('lomba.register.save') }}">
            @csrf

            {{-- Data Diri --}}
            <div class="bg-light p-3 rounded mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-person me-1"></i>Data Diri</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control"
                               value="{{ old('nama', $lombaUser->nama) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_wa" class="form-control"
                               placeholder="0812..." value="{{ old('no_wa', $lombaUser->no_wa) }}" required>
                    </div>
                </div>
            </div>

            {{-- Password --}}
            <div class="bg-light p-3 rounded mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-lock me-1"></i>Buat Password</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Minimal 6 karakter" required minlength="6">
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Ulangi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Ketik ulang password" required minlength="6">
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Password digunakan untuk login berikutnya tanpa perlu token.
                </div>
            </div>

            @if($hasKontingen)
            {{-- Legasi user — hanya set password, kontingen sudah ada --}}
            <div class="alert alert-info py-2 small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Data kontingen kamu sudah tersimpan. Silakan buat password untuk akun ini.
            </div>
            @else
            {{-- Data Kontingen --}}
            <div class="bg-light p-3 rounded mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-people me-1"></i>Data Kontingen</h6>

                {{-- Kota/Kab (jika kompetisi = K) - Nama Kontingen otomatis --}}
                @if($jnsKompetisi === 'K')
                <div class="mb-3">
                    <label class="form-label small fw-bold">Kota / Kabupaten <span class="text-danger">*</span></label>
                    <input type="text" name="kota_kab" class="form-control"
                           list="kotaList" placeholder="Ketik atau pilih Kota/Kab..."
                           value="{{ old('kota_kab') }}" required autocomplete="off">
                    <datalist id="kotaList">
                        @foreach($listKota as $kota)
                            <option value="{{ $kota->label }}">
                        @endforeach
                    </datalist>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-info-circle me-1"></i>
                        Nama kontingen akan diisi otomatis. Jika kota tidak ada dalam daftar, ketik manual (contoh: KOTA MALANG atau KAB KEDIRI).
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Provinsi</label>
                    <input type="text" name="provinsi" class="form-control"
                           value="{{ old('provinsi', 'JAWA TIMUR') }}" placeholder="Provinsi">
                </div>
                @elseif($jnsKompetisi === 'P')
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Kontingen <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kontingen" class="form-control"
                           placeholder="Nama kontingen"
                           value="{{ old('nama_kontingen') }}" required>
                </div>
                @endif

                {{-- Club (jika kompetisi = C) --}}
                @if($jnsKompetisi === 'C')
                <div class="mb-3">
                    <label class="form-label small fw-bold">Club <span class="text-danger">*</span></label>
                    <select name="nama_kontingen" class="form-select" required>
                        <option value="">— Pilih Club —</option>
                        @foreach($clubList as $club)
                            <option value="{{ $club }}"
                                {{ old('nama_kontingen') == $club ? 'selected' : '' }}>
                                {{ $club }}
                            </option>
                        @endforeach
                    </select>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-info-circle me-1"></i>
                        Data club diambil dari database pendaftaran NIAS.
                    </div>
                </div>
                @endif

                {{-- Provinsi (jika kompetisi = P) --}}
                @if($jnsKompetisi === 'P')
                <div class="mb-3">
                    <label class="form-label small fw-bold">Provinsi <span class="text-danger">*</span></label>
                    <select name="provinsi" class="form-select" required>
                        <option value="">— Pilih Provinsi —</option>
                        @foreach($provinsiList as $prov)
                            <option value="{{ $prov }}"
                                {{ old('provinsi', 'JAWA TIMUR') == $prov ? 'selected' : '' }}>
                                {{ $prov }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            @endif

            <button type="submit" class="btn btn-lomba w-100" id="btnSubmit">
                <i class="bi bi-save me-1"></i>Simpan & Lanjutkan
            </button>
        </form>

        <div class="text-center mt-3">
            <form method="POST" action="{{ route('lomba.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right me-1"></i>Batal & Logout
                </button>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('form')?.addEventListener('submit', function() {
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('btnSubmit').innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        });
    </script>
</body>
</html>
