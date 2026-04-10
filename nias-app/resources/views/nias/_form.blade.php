{{--
    Shared form partial — create & edit
    Expects: $domisilis (array), $userClub (string), $nias (optional model)
--}}

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show shadow-sm">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Terdapat kesalahan input:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ══════════════════════════════════════
     1. DATA PRIBADI
══════════════════════════════════════ --}}
<div class="card section-card mb-4">
    <div class="card-header py-2 px-3">
        <span class="fw-bold text-primary small">
            <i class="bi bi-person-badge me-1"></i>DATA PRIBADI ATLET
        </span>
    </div>
    <div class="card-body row g-3">

        {{-- Nama --}}
        <div class="col-md-8">
            <label class="form-label" for="NAMA">
                Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input type="text" id="NAMA" name="NAMA"
                   class="form-control text-uppercase @error('NAMA') is-invalid @enderror"
                   value="{{ old('NAMA', $nias->NAMA ?? '') }}"
                   placeholder="Nama lengkap sesuai dokumen resmi" required maxlength="100">
            @error('NAMA')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Gender --}}
        <div class="col-md-4">
            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
            <select name="GENDER" class="form-select @error('GENDER') is-invalid @enderror" required>
                <option value="L" {{ old('GENDER', $nias->GENDER ?? 'L') === 'L' ? 'selected' : '' }}>
                    Laki-laki
                </option>
                <option value="P" {{ old('GENDER', $nias->GENDER ?? '') === 'P' ? 'selected' : '' }}>
                    Perempuan
                </option>
            </select>
            @error('GENDER')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Tempat Lahir --}}
        <div class="col-md-5">
            <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
            <input type="text" name="TEMPATLAHIR"
                   class="form-control text-uppercase @error('TEMPATLAHIR') is-invalid @enderror"
                   value="{{ old('TEMPATLAHIR', $nias->TEMPATLAHIR ?? '') }}"
                   placeholder="Kota / kabupaten tempat lahir" required maxlength="100">
            @error('TEMPATLAHIR')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Tanggal Lahir --}}
        <div class="col-md-4">
            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
            <input type="date" name="TGLLAHIR"
                   class="form-control @error('TGLLAHIR') is-invalid @enderror"
                   value="{{ old('TGLLAHIR', isset($nias->TGLLAHIR) ? $nias->TGLLAHIR->format('Y-m-d') : '') }}"
                   max="{{ date('Y-m-d', strtotime('-1 day')) }}" required>
            @error('TGLLAHIR')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- NIK --}}
        <div class="col-md-6">
            <label class="form-label">
                NIK <span class="text-danger">*</span>
                <span class="text-muted fw-normal">(sesuai NIK di KK/KTP/KIA)</span>
            </label>
            <input type="text" name="NIK" maxlength="16"
                   class="form-control @error('NIK') is-invalid @enderror"
                   value="{{ old('NIK', $nias->NIK ?? '') }}"
                   placeholder="16 digit Nomor Induk Kependudukan"
                   inputmode="numeric" pattern="[0-9]{16}">
            @error('NIK')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Email --}}
        <div class="col-md-6">
            <label class="form-label">Email Atlet</label>
            <input type="email" name="EMAIL"
                   class="form-control @error('EMAIL') is-invalid @enderror"
                   value="{{ old('EMAIL', $nias->EMAIL ?? '') }}"
                   placeholder="email@contoh.com" maxlength="100">
            @error('EMAIL')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════
     2. KLUB (auto dari akun pelatih)
══════════════════════════════════════ --}}
<div class="card section-card mb-4">
    <div class="card-header py-2 px-3">
        <span class="fw-bold text-primary small">
            <i class="bi bi-people me-1"></i>DATA KLUB
        </span>
    </div>
    <div class="card-body row g-3">
        <div class="col-md-9">
            <label class="form-label">Nama Klub</label>
            {{-- Tampilkan sebagai text, nilai dari akun pelatih --}}
            <input type="text" class="form-control bg-light fw-semibold"
                   value="{{ $userClub }}" readonly>
            <div class="form-text small">
                <i class="bi bi-info-circle me-1 text-info"></i>
                Klub otomatis sesuai akun pelatih yang login.
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     3. DOMISILI
══════════════════════════════════════ --}}
<div class="card section-card mb-4">
    <div class="card-header py-2 px-3">
        <span class="fw-bold text-primary small">
            <i class="bi bi-geo-alt me-1"></i>DOMISILI DI JAWA TIMUR
        </span>
    </div>
    <div class="card-body row g-3">

        <div class="col-md-4">
            <label class="form-label">Provinsi</label>
            <input type="text" class="form-control bg-light" value="JAWA TIMUR" disabled>
        </div>

        <div class="col-md-8">
            <label class="form-label" for="NAMAKOTADOM">
                Kota / Kabupaten <span class="text-danger">*</span>
            </label>
            <select name="NAMAKOTADOM" id="NAMAKOTADOM"
                    class="form-select @error('NAMAKOTADOM') is-invalid @enderror" required>
                <option value="">— Pilih Kota / Kabupaten —</option>
                @foreach($domisilis as $dom)
                    <option value="{{ $dom }}"
                        {{ old('NAMAKOTADOM', $nias->NAMAKOTADOM ?? '') === $dom ? 'selected' : '' }}>
                        {{ $dom }}
                    </option>
                @endforeach
            </select>
            @error('NAMAKOTADOM')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════
     4. UPLOAD DOKUMEN
══════════════════════════════════════ --}}
<div class="card section-card mb-4">
    <div class="card-header py-2 px-3">
        <span class="fw-bold text-primary small">
            <i class="bi bi-paperclip me-1"></i>UPLOAD DOKUMEN
        </span>
    </div>
    <div class="card-body row g-3">

        <div class="col-12">
            <div class="alert alert-info py-2 small mb-2">
                <i class="bi bi-info-circle me-1"></i>
                Format yang diterima: <strong>PDF, JPG, PNG</strong>.
                Ukuran maksimal <strong>5 MB</strong> per file.
            </div>
        </div>

        {{-- KK --}}
        <div class="col-md-6">
            <label class="form-label" for="file_kk">
                Kartu Keluarga (KK) <span class="text-danger">*</span>
            </label>
            @if(isset($nias) && $nias->file_kk)
                <div class="mb-1">
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Sudah diupload
                    </span>
                    <span class="text-muted small ms-1">Upload baru untuk mengganti</span>
                </div>
            @endif
            <input type="file" id="file_kk" name="file_kk"
                   class="form-control @error('file_kk') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png"
                   {{ !isset($nias) ? 'required' : '' }}>
            @error('file_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Foto --}}
        <div class="col-md-6">
            <label class="form-label" for="file_foto">
                Foto Atlet <span class="text-danger">*</span>
            </label>
            @if(isset($nias) && $nias->file_foto)
                <div class="mb-1">
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Sudah diupload
                    </span>
                    <span class="text-muted small ms-1">Upload baru untuk mengganti</span>
                </div>
            @endif
            <input type="file" id="file_foto" name="file_foto"
                   class="form-control @error('file_foto') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png"
                   {{ !isset($nias) ? 'required' : '' }}>
            @error('file_foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Akte --}}
        <div class="col-md-6">
            <label class="form-label" for="file_akte">
                Akte Lahir <span class="text-danger">*</span>
            </label>
            @if(isset($nias) && $nias->file_akte)
                <div class="mb-1">
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Sudah diupload
                    </span>
                    <span class="text-muted small ms-1">Upload baru untuk mengganti</span>
                </div>
            @endif
            <input type="file" id="file_akte" name="file_akte"
                   class="form-control @error('file_akte') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png"
                   {{ !isset($nias) ? 'required' : '' }}>
            @error('file_akte')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Ijazah --}}
        <div class="col-md-6">
            <label class="form-label" for="file_ijazah">
                Ijazah / Raport Terakhir
            </label>
            @if(isset($nias) && $nias->file_ijazah)
                <div class="mb-1">
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Sudah diupload
                    </span>
                    <span class="text-muted small ms-1">Upload baru untuk mengganti</span>
                </div>
            @endif
            <input type="file" id="file_ijazah" name="file_ijazah"
                   class="form-control @error('file_ijazah') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png">
            @error('file_ijazah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════
     5. INFO OTOMATIS (tanpa tulisan status)
══════════════════════════════════════ --}}
<div class="rounded p-3 mb-4 border" style="background:#f0f5ff;border-color:#c5d8ff !important;">
    <p class="mb-2 small fw-semibold text-primary">
        <i class="bi bi-info-circle me-1"></i>Diisi otomatis oleh sistem:
    </p>
    <div class="row g-2 small">
        <div class="col-sm-4">
            <span class="text-secondary">Tanggal Daftar:</span>
            <strong class="ms-1">{{ now()->format('d/m/Y') }}</strong>
        </div>
        <div class="col-sm-4">
            <span class="text-secondary">Masa Berlaku s/d:</span>
            <strong class="ms-1 text-success">{{ now()->addYears(2)->format('d/m/Y') }}</strong>
        </div>
    </div>
</div>
