@extends('layouts.app')
@section('title', 'Pengaturan Akun')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">

        {{-- Header --}}
        <div class="card page-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-person-gear me-2"></i>Pengaturan Akun
                </h5>
                <a href="{{ route('welcome') }}" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Portal
                </a>
            </div>
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ══ DETAIL AKUN ══════════════════════════════════════════ --}}
        <div class="card section-card mb-4">
            <div class="card-header py-2 px-3">
                <span class="fw-bold text-primary small">
                    <i class="bi bi-person-badge me-1"></i>INFORMASI AKUN
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    {{-- Avatar / inisial --}}
                    <div class="col-12 d-flex align-items-center gap-3 mb-2">
                        <div style="width:64px;height:64px;border-radius:50%;
                                    background:var(--possi-blue);color:#fff;
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:1.6rem;font-weight:700;flex-shrink:0;">
                            {{ strtoupper(substr(Auth::user()->nama, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold fs-5">{{ Auth::user()->nama }}</div>
                            <div class="text-muted small">{{ Auth::user()->email }}</div>
                            <span class="badge {{ Auth::user()->role === 'admin' ? 'bg-danger' : 'bg-secondary' }} mt-1">
                                {{ Auth::user()->role === 'admin' ? 'Admin' : 'Pelatih / Official' }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Nama Lengkap</label>
                        <div class="form-control bg-light fw-semibold">{{ Auth::user()->nama }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Email</label>
                        <div class="form-control bg-light">{{ Auth::user()->email }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Club</label>
                        <div class="form-control bg-light fw-semibold text-primary">
                            {{ Auth::user()->namaclub ?? '—' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Jenis Kelamin</label>
                        <div class="form-control bg-light">
                            {{ Auth::user()->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Akun Dibuat</label>
                        <div class="form-control bg-light small">
                            {{ Auth::user()->created_at?->format('d/m/Y H:i') ?? '—' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Jumlah Data NIAS Dikirim</label>
                        <div class="form-control bg-light">
                            <span class="fw-semibold text-success">{{ $jumlahTerkirim }}</span>
                            <span class="text-muted small"> atlet</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ══ GANTI PASSWORD ═══════════════════════════════════════ --}}
        <div class="card section-card mb-4">
            <div class="card-header py-2 px-3">
                <span class="fw-bold text-primary small">
                    <i class="bi bi-shield-lock me-1"></i>GANTI PASSWORD
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.setting.password') }}" id="formGantiPassword">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       placeholder="Masukkan password saat ini" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('current_password', 'eye0')">
                                    <i class="bi bi-eye" id="eye0"></i>
                                </button>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="new_password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Minimal 8 karakter" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('new_password', 'eye1')">
                                    <i class="bi bi-eye" id="eye1"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="confirm_password"
                                       class="form-control" placeholder="Ulangi password baru" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('confirm_password', 'eye2')">
                                    <i class="bi bi-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-shield-check me-1"></i>Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

@if(session('success'))
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({ title: 'Berhasil!', text: '{{ addslashes(session("success")) }}',
        icon: 'success', confirmButtonColor: '#0d6efd', timer: 2500, showConfirmButton: false });
});
@endif
@if(session('error'))
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({ title: 'Gagal!', text: '{{ addslashes(session("error")) }}',
        icon: 'error', confirmButtonColor: '#dc3545' });
});
@endif
</script>
@endpush
