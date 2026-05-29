<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Data Kontingen — POSSI Jatim</title>
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
            width: 100%; max-width: 560px;
            box-shadow: 0 8px 32px rgba(0,0,0,.1);
        }
        .card-register h4 { font-weight: 700; margin-bottom: .25rem; }
        .card-register .subtitle { font-size: .85rem; color: #888; margin-bottom: 1.5rem; }
        .btn-lomba { background: #e65100; border-color: #e65100; color: #fff; font-weight: 600; }
        .btn-lomba:hover { background: #bf360c; border-color: #bf360c; color: #fff; }
    </style>
</head>
<body>
    <div class="card-register">
        <h4><i class="bi bi-person-plus me-2"></i>Lengkapi Data Kontingen</h4>
        <p class="subtitle">Login sebagai <strong>{{ $lombaUser->email }}</strong></p>

        @if(session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('lomba.register.save') }}">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control"
                           value="{{ old('nama', $lombaUser->nama) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">No. WhatsApp</label>
                    <input type="text" name="no_wa" class="form-control"
                           placeholder="0812..." value="{{ old('no_wa', $lombaUser->no_wa) }}" required>
                </div>
            </div>

            <hr>

            <h6 class="fw-bold mb-3"><i class="bi bi-people me-1"></i>Data Kontingen</h6>

            <div class="mb-3">
                <label class="form-label small fw-bold">Jenis Kompetisi</label>
                <select name="jns_kompetisi" class="form-select" id="jnsKompetisi" required>
                    <option value="">— Pilih —</option>
                    <option value="K" {{ old('jns_kompetisi') == 'K' ? 'selected' : '' }}>Kabupaten / Kota</option>
                    <option value="P" {{ old('jns_kompetisi') == 'P' ? 'selected' : '' }}>Provinsi</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Nama Kontingen</label>
                <input type="text" name="nama_kontingen" class="form-control"
                       value="{{ old('nama_kontingen') }}" required>
            </div>

            <div id="wilayahGroup" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Jenis Wilayah</label>
                        <select name="jenis_wilayah" class="form-select" id="jenisWilayah">
                            <option value="">— Pilih —</option>
                            <option value="KAB" {{ old('jenis_wilayah') == 'KAB' ? 'selected' : '' }}>Kabupaten</option>
                            <option value="KOTA" {{ old('jenis_wilayah') == 'KOTA' ? 'selected' : '' }}>Kota</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nama Kota / Kab</label>
                        <select name="nama_wilayah" class="form-select" id="namaWilayah">
                            <option value="">— Pilih —</option>
                            @foreach($listKota as $kota)
                                <option value="{{ $kota->NAMAKOTA }}"
                                    {{ old('nama_wilayah') == $kota->NAMAKOTA ? 'selected' : '' }}>
                                    {{ $kota->NAMAKOTA }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Provinsi</label>
                <input type="text" name="provinsi" class="form-control"
                       value="{{ old('provinsi', 'JAWA TIMUR') }}" required>
            </div>

            <button type="submit" class="btn btn-lomba w-100">
                <i class="bi bi-save me-1"></i>Simpan & Lanjutkan
            </button>
        </form>

        <div class="text-center mt-3">
            <form method="POST" action="{{ route('lomba.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
            </form>
        </div>
    </div>

    <script>
        const jnsKompetisi = document.getElementById('jnsKompetisi');
        const wilayahGroup = document.getElementById('wilayahGroup');
        const jenisWilayah = document.getElementById('jenisWilayah');
        const namaWilayah = document.getElementById('namaWilayah');

        function toggleWilayah() {
            if (jnsKompetisi.value === 'K') {
                wilayahGroup.style.display = 'block';
                jenisWilayah.required = true;
                namaWilayah.required = true;
            } else {
                wilayahGroup.style.display = 'none';
                jenisWilayah.required = false;
                namaWilayah.required = false;
                jenisWilayah.value = '';
                namaWilayah.value = '';
            }
        }

        jnsKompetisi.addEventListener('change', toggleWilayah);
        toggleWilayah();
    </script>
</body>
</html>
