@extends('layouts.app')
@section('title', 'Detail Akun — ' . $user->nama)

@section('content')
<div class="card page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-person-circle me-2"></i>Detail Akun
        </h5>
        <a href="{{ route('settings', ['tab' => 'akun']) }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card-body">
        <div class="row g-4">

            {{-- Info Akun --}}
            <div class="col-md-6">
                <div class="card section-card h-100">
                    <div class="card-header py-2 px-3">
                        <span class="fw-bold text-primary small">
                            <i class="bi bi-person me-1"></i>INFORMASI AKUN
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div style="width:56px;height:56px;border-radius:50%;
                                        background:var(--possi-blue);color:#fff;
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:1.4rem;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($user->nama, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold fs-5">{{ $user->nama }}</div>
                                <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : 'bg-secondary' }}">
                                    {{ $user->role === 'admin' ? 'Admin' : 'Regular' }}
                                </span>
                            </div>
                        </div>

                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted small" style="width:130px">Email</td>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Club</td>
                                <td class="fw-semibold text-primary">{{ $user->namaclub ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Jenis Kelamin</td>
                                <td>{{ $user->gender === 'L' ? 'Laki-laki' : ($user->gender === 'P' ? 'Perempuan' : '—') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Dibuat</td>
                                <td class="small">{{ $user->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Diperbarui</td>
                                <td class="small">{{ $user->updated_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Statistik NIAS --}}
            <div class="col-md-6">
                <div class="card section-card h-100">
                    <div class="card-header py-2 px-3">
                        <span class="fw-bold text-primary small">
                            <i class="bi bi-bar-chart me-1"></i>STATISTIK NIAS
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <div class="p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-primary">{{ $statNias['total'] }}</div>
                                    <div class="small text-muted">Total Data</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-warning">{{ $statNias['pending'] }}</div>
                                    <div class="small text-muted">Pending</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-success">{{ $statNias['terkirim'] }}</div>
                                    <div class="small text-muted">Terkirim</div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex flex-column gap-2 mt-2">
                            <a href="{{ route('nias.index') }}?user_filter={{ $user->id }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-list-ul me-1"></i>Lihat Data NIAS User Ini
                            </a>
                            @if($user->role !== 'admin')
                            <button type="button"
                                    class="btn btn-outline-warning btn-sm"
                                    onclick="confirmResetPwd({{ $user->id }}, '{{ addslashes($user->nama) }}')">
                                <i class="bi bi-key me-1"></i>Reset Password → Possi@1234
                            </button>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->nama) }}')">
                                <i class="bi bi-trash me-1"></i>Hapus Akun Ini
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Hidden forms --}}
<form id="form_reset_pwd" method="POST" style="display:none;">
    @csrf
</form>
<form id="form_delete" method="POST" style="display:none;">
    @csrf @method('DELETE')
</form>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmResetPwd(userId, nama) {
    Swal.fire({
        title: 'Reset Password?',
        html: 'Password <strong>' + nama + '</strong> akan direset ke <code>Possi@1234</code>.',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Reset!', cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('form_reset_pwd');
            form.action = '/settings/users/' + userId + '/reset-password';
            form.submit();
        }
    });
}

function confirmDelete(userId, nama) {
    Swal.fire({
        title: 'Hapus Akun?',
        html: 'Akun <strong>' + nama + '</strong> akan dihapus permanen.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('form_delete');
            form.action = '/settings/users/' + userId + '/delete';
            form.submit();
        }
    });
}
</script>
@endpush
