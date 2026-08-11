@extends('layouts.app')
@section('title', 'Pengaturan Akun Lomba')

@section('content')
<div class="card page-card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Pengaturan Akun Lomba</h5>
    <a href="{{ route('lomba.index') }}" class="btn btn-success btn-sm fw-semibold">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>
<div class="card-body p-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="list-group shadow-sm mb-4">
                <div class="list-group-item bg-light fw-bold">Informasi Akun</div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Email</span>
                    <span class="fw-semibold">{{ $lombaUser->email }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Nama</span>
                    <span class="fw-semibold">{{ $lombaUser->nama ?? '—' }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>No. WhatsApp</span>
                    <span>{{ $lombaUser->no_wa ?? '—' }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Status Password</span>
                    @if($lombaUser->isRegistered())
                        <span class="badge bg-success">Aktif</span>
                    @else
                        <span class="badge bg-warning text-dark">Belum Diset</span>
                    @endif
                </div>
            </div>

            <div class="card border shadow-sm">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-lock me-2"></i>Ganti Password
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('lomba.account.password') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Saat Ini <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control"
                                   placeholder="Masukkan password saat ini" required minlength="6">
                            @error('current_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="Minimal 6 karakter" required minlength="6">
                            @error('new_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Ulangi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="new_password_confirmation" class="form-control"
                                   placeholder="Ketik ulang password baru" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="btnUpdatePw">
                            <i class="bi bi-save me-1"></i>Perbarui Password
                        </button>
                    </form>

                    @if(!$lombaUser->isRegistered())
                    <hr>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Kamu belum memiliki password. Isi ketiga field di atas untuk membuat password baru.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.querySelector('form')?.addEventListener('submit', function() {
    document.getElementById('btnUpdatePw').disabled = true;
    document.getElementById('btnUpdatePw').innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
});
</script>
@endsection
