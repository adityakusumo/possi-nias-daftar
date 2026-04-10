@extends('layouts.app')
@section('title','Data NIAS')

@section('content')

{{-- SECTION 1: DATA BELUM DIKIRIM --}}
<div class="card page-card mb-4">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
<h5 class="mb-0"><i class="bi bi-table me-2"></i>List Nama Atlet Daftar Nias Baru/Update</h5>
<div class="d-flex gap-2">
<a href="{{ route('nias.existing') }}" class="btn btn-secondary btn-sm fw-semibold shadow-sm">
<i class="bi bi-people me-1"></i>Data Atlet Existing
</a>
<a href="{{ route('nias.update-data') }}" class="btn btn-primary btn-sm fw-semibold shadow-sm">
<i class="bi bi-arrow-repeat me-1"></i>Update NIAS
</a>
<a href="{{ route('nias.create') }}" class="btn btn-warning btn-sm fw-semibold shadow-sm">
<i class="bi bi-person-plus me-1"></i>Daftar Baru
</a>
</div>
</div>

<div class="card-body p-3">

{{-- Search bar --}}
<form method="GET" action="{{ route('nias.index') }}" class="row g-2 mb-3">
@if(request('jenis'))
<input type="hidden" name="jenis" value="{{ request('jenis') }}">
@endif
<div class="col-md-5">
<div class="input-group">
<span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
<input type="text" name="search" class="form-control border-start-0"
placeholder="Cari nama, No. NIAS, atau klub…"
value="{{ request('search') }}">
</div>
</div>
<div class="col-auto d-flex gap-1">
<button type="submit" class="btn btn-primary">Cari</button>
@if(request('search') || request('jenis'))
<a href="{{ route('nias.index') }}" class="btn btn-outline-secondary">Reset</a>
@endif
</div>
</form>

{{-- Tab filter --}}
@php
$totalSemua  = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->count();
$totalBaru   = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->where('is_update', false)->count();
$totalUpdate = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->where('is_update', true)->count();
@endphp
<ul class="nav nav-tabs mb-3">
<li class="nav-item">
<a class="nav-link {{ !request('jenis') ? 'active' : '' }}"
href="{{ route('nias.index') }}{{ request('search') ? '?search='.request('search') : '' }}">
Semua <span class="badge bg-secondary ms-1">{{ $totalSemua }}</span>
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ request('jenis') === 'baru' ? 'active' : '' }}"
href="{{ route('nias.index', array_merge(request()->only('search'), ['jenis' => 'baru'])) }}">
<i class="bi bi-person-plus me-1"></i>Daftar Baru
<span class="badge bg-warning text-dark ms-1">{{ $totalBaru }}</span>
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ request('jenis') === 'update' ? 'active' : '' }}"
href="{{ route('nias.index', array_merge(request()->only('search'), ['jenis' => 'update'])) }}">
<i class="bi bi-arrow-repeat me-1"></i>Update / Perpanjang
<span class="badge bg-info text-dark ms-1">{{ $totalUpdate }}</span>
</a>
</li>
</ul>

{{-- Toolbar bulk action --}}
<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
<div class="d-flex align-items-center gap-2">
<input type="checkbox" id="chk_all" class="form-check-input mt-0" title="Pilih semua">
<span id="selected_count" class="text-muted small">0 dipilih</span>
<button type="button" id="btn_delete_selected"
class="btn btn-sm btn-outline-danger d-none"
onclick="confirmDeleteSelected()">
<i class="bi bi-trash me-1"></i>Hapus Dipilih
</button>
</div>
<button type="button" class="btn btn-sm btn-danger"
onclick="confirmDeleteAll()">
<i class="bi bi-trash3 me-1"></i>Hapus Semua
</button>
</div>

{{-- Form untuk delete selected --}}
<form id="form_delete_selected" method="POST"
action="{{ route('nias.destroy-selected') }}" style="display:none;">
@csrf @method('DELETE')
</form>

{{-- Form untuk delete all --}}
<form id="form_delete_all" method="POST"
action="{{ route('nias.destroy-all') }}" style="display:none;">
@csrf @method('DELETE')
</form>

<div class="table-responsive">
<table class="table table-nias table-bordered table-sm align-middle mb-2" id="tbl_nias">
<thead>
<tr>
<th style="width:36px"></th>{{-- checkbox col --}}
<th>#</th>
<th>No. NIAS</th>
<th>Nama</th>
<th>L/P</th>
<th>Tgl Lahir</th>
<th>Klub</th>
<th>Kota / Kab Domisili</th>
<th>Tgl Daftar</th>
<th>Tgl Update</th>
<th>Expired</th>
<th>Status</th>
<th class="text-center">Aksi</th>
</tr>
</thead>
<tbody>
@forelse($records as $r)
<tr class="{{ $r->is_update ? 'table-info' : '' }}" data-id="{{ $r->ID }}">
<td class="text-center">
<input type="checkbox" class="form-check-input chk_row mt-0"
value="{{ $r->ID }}">
</td>
<td class="text-muted small">{{ $records->firstItem() + $loop->index }}</td>
<td><code class="small">{{ $r->NONIAS ?? '—' }}</code></td>
<td class="fw-semibold">
{{ $r->NAMA }}
@if($r->is_update)
<span class="badge bg-info text-dark ms-1" title="Perpanjangan / Update NIAS">
<i class="bi bi-arrow-repeat"></i> UPDATE
</span>
@endif
</td>
<td>
@if($r->GENDER === 'L')
<span class="badge bg-primary">L</span>
@else
<span class="badge bg-danger">P</span>
@endif
</td>
<td class="small">{{ $r->TGLLAHIR?->format('d/m/Y') }}</td>
<td class="small">{{ $r->NAMACLUB }}</td>
<td class="small">
<span class="text-muted">{{ $r->JENISDOM }}</span>
{{ $r->NAMAKOTADOM }}
</td>
<td class="small">{{ $r->TGLDAFTAR?->format('d/m/Y') ?? '—' }}</td>
<td class="small">
@if($r->is_update && $r->TGLDAFTAR_UPDATE)
<span class="text-info fw-semibold">
{{ $r->TGLDAFTAR_UPDATE->format('d/m/Y') }}
</span>
@else
<span class="text-muted">—</span>
@endif
</td>
<td class="small {{ $r->EXPIRED?->isPast() ? 'text-danger fw-semibold' : '' }}">
{{ $r->EXPIRED?->format('d/m/Y') }}
</td>
<td>
@if($r->STATUS == 1 && !$r->EXPIRED?->isPast())
<span class="badge badge-aktif">AKTIF</span>
@else
<span class="badge badge-expired">EXPIRED</span>
@endif
</td>
<td class="text-center" style="white-space:nowrap">
<a href="{{ route('nias.show', $r) }}"
class="btn btn-sm btn-outline-primary py-0" title="Detail">
<i class="bi bi-eye"></i>
</a>
<a href="{{ route('nias.edit', $r) }}"
class="btn btn-sm btn-outline-warning py-0" title="Edit">
<i class="bi bi-pencil"></i>
</a>
<button type="button"
class="btn btn-sm btn-outline-danger py-0"
title="Hapus"
onclick="confirmDeleteOne('{{ addslashes($r->NAMA) }}', '{{ route('nias.destroy', $r) }}')">
<i class="bi bi-trash"></i>
</button>
</td>
</tr>
@empty
<tr>
<td colspan="13" class="text-center text-muted py-5">
<i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
Belum ada data NIAS terdaftar.
</td>
</tr>
@endforelse
</tbody>
</table>
</div>

{{-- Hidden form untuk delete satu data (SweetAlert) --}}
<form id="form_delete_one" method="POST" style="display:none;">
@csrf @method('DELETE')
</form>

{{-- Pagination --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
<small class="text-muted">
@if($records->total())
Menampilkan {{ $records->firstItem() }}–{{ $records->lastItem() }}
dari <strong>{{ $records->total() }}</strong> data
@endif
</small>
{{ $records->links() }}
</div>

</div>
</div>

{{-- Export & Kirim Email --}}
@if($records->total() > 0)
<div class="d-flex justify-content-end gap-2 mb-4">
<a href="{{ route('nias.export') }}" class="btn btn-success">
<i class="bi bi-filetype-csv me-1"></i>Export CSV (ZIP)
</a>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKirimEmail">
<i class="bi bi-envelope-arrow-up me-1"></i>Kirim ke POSSI Jatim
</button>
</div>
@endif

{{-- SECTION 2: DATA YANG SUDAH DIKIRIM --}}
@if($sentRecords->total() > 0)
<div class="card page-card">
<div class="card-header bg-success text-white">
<h5 class="mb-0">
<i class="bi bi-check-circle me-2"></i>List Pendaftaran yang Sudah Dikirim
</h5>
</div>

<div class="card-body p-3">
<div class="alert alert-success small mb-3">
<i class="bi bi-info-circle me-2"></i>
Data berikut sudah dikirim ke POSSI Jatim dan tidak dapat diedit atau dihapus.
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm align-middle mb-2">
<thead class="table-success">
<tr>
<th>#</th>
<th>No. NIAS</th>
<th>Nama</th>
<th>L/P</th>
<th>Tgl Lahir</th>
<th>Klub</th>
<th>Kota / Kab Domisili</th>
<th>Tgl Dikirim</th>
<th>Status</th>
<th class="text-center">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($sentRecords as $r)
<tr class="table-light">
<td class="text-muted small">{{ $sentRecords->firstItem() + $loop->index }}</td>
<td><code class="small">{{ $r->NONIAS ?? '—' }}</code></td>
<td class="fw-semibold">
{{ $r->NAMA }}
@if($r->is_update)
<span class="badge bg-info text-dark ms-1">
<i class="bi bi-arrow-repeat"></i> UPDATE
</span>
@endif
</td>
<td>
@if($r->GENDER === 'L')
<span class="badge bg-primary">L</span>
@else
<span class="badge bg-danger">P</span>
@endif
</td>
<td class="small">{{ $r->TGLLAHIR?->format('d/m/Y') }}</td>
<td class="small">{{ $r->NAMACLUB }}</td>
<td class="small">
<span class="text-muted">{{ $r->JENISDOM }}</span>
{{ $r->NAMAKOTADOM }}
</td>
<td class="small text-success fw-semibold">
<i class="bi bi-check-circle me-1"></i>
{{ $r->sent_at?->format('d/m/Y H:i') }}
</td>
<td>
<span class="badge bg-success">
<i class="bi bi-send-check"></i> TERKIRIM
</span>
</td>
<td class="text-center">
<a href="{{ route('nias.show', $r) }}"
class="btn btn-sm btn-outline-primary py-0" title="Detail">
<i class="bi bi-eye"></i>
</a>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>

{{-- Pagination untuk sent records --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
<small class="text-muted">
Menampilkan {{ $sentRecords->firstItem() }}–{{ $sentRecords->lastItem() }}
dari <strong>{{ $sentRecords->total() }}</strong> data terkirim
</small>
{{ $sentRecords->links() }}
</div>

</div>
</div>
@endif

{{-- Modal Kirim Email --}}
<div class="modal fade" id="modalKirimEmail" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<form method="POST" action="{{ route('nias.send-email') }}">
@csrf
<div class="modal-header bg-primary text-white">
<h5 class="modal-title">
<i class="bi bi-envelope-arrow-up me-2"></i>Kirim Data ke POSSI Jatim
</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
@php
$jmlBaru   = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->where('is_update', false)->count();
$jmlUpdate = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->where('is_update', true)->count();
@endphp
<div class="alert alert-light border small mb-3">
<table class="w-100">
<tr>
<td class="text-muted" style="width:130px">Club</td>
<td>: <strong>{{ Auth::user()->namaclub }}</strong></td>
</tr>
<tr>
<td class="text-muted">Email Pelatih</td>
<td>: {{ Auth::user()->email ?? '-' }}</td>
</tr>
<tr>
<td class="text-muted">Tujuan Email</td>
<td>: <strong>it.possijatim@gmail.com</strong></td>
</tr>
<tr>
<td class="text-muted">Jumlah Data</td>
<td>:
<span class="badge bg-warning text-dark">Baru: {{ $jmlBaru }}</span>
<span class="badge bg-info text-dark ms-1">Update: {{ $jmlUpdate }}</span>
</td>
</tr>
</table>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">
Keterangan <span class="text-muted small">(opsional)</span>
</label>
<textarea name="keterangan" class="form-control" rows="4"
maxlength="1000"
placeholder="Contoh: Mohon segera diproses, ada 2 atlet yang expired bulan ini..."></textarea>
<div class="form-text text-end">
<span id="char_count">0</span>/1000 karakter
</div>
</div>
<div class="alert alert-warning small mb-0">
<i class="bi bi-info-circle me-1"></i>
File ZIP berisi seluruh data (<strong>{{ $jmlBaru + $jmlUpdate }}</strong> atlet)
beserta dokumen pendukung akan dilampirkan ke email.
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" id="btn_kirim" class="btn btn-primary"
onclick="konfirmasiKirim(event)">
<i class="bi bi-send me-1"></i>Kirim Email
</button>
</div>
</form>
</div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Checkbox: select all / per baris ─────────────────────────────
const chkAll = document.getElementById('chk_all');

chkAll.addEventListener('change', function () {
    document.querySelectorAll('.chk_row').forEach(c => c.checked = this.checked);
    updateSelectedCount();
});

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('chk_row')) updateSelectedCount();
});

function updateSelectedCount() {
    const checked = document.querySelectorAll('.chk_row:checked');
    const count   = checked.length;
    document.getElementById('selected_count').textContent = count + ' dipilih';
    document.getElementById('btn_delete_selected').classList.toggle('d-none', count === 0);
    chkAll.indeterminate = count > 0 && count < document.querySelectorAll('.chk_row').length;
    chkAll.checked = count > 0 && count === document.querySelectorAll('.chk_row').length;
}

// ── Delete satu data ──────────────────────────────────────────────
function confirmDeleteOne(nama, url) {
    Swal.fire({
        title: 'Hapus Data?',
        html: 'Data <strong>' + nama + '</strong> akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('form_delete_one');
            form.action = url;
            form.submit();
        }
    });
}

// ── Delete selected ───────────────────────────────────────────────
function confirmDeleteSelected() {
    const ids = [...document.querySelectorAll('.chk_row:checked')].map(c => c.value);
    if (!ids.length) return;

    Swal.fire({
        title: 'Hapus ' + ids.length + ' Data?',
        text: 'Data yang dipilih akan dihapus permanen dan tidak bisa dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus ' + ids.length + ' Data!',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('form_delete_selected');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = id;
            form.appendChild(input);
            });
            form.submit();
        }
    });
}

// ── Delete all ────────────────────────────────────────────────────
function confirmDeleteAll() {
    const total = {{ $records->total() ?? 0 }};
    if (!total) {
        Swal.fire('Tidak Ada Data', 'Tidak ada data yang bisa dihapus.', 'info');
        return;
    }

    Swal.fire({
        title: 'Hapus SEMUA Data?',
        html: 'Seluruh <strong>' + total + '</strong> data NIAS Anda akan dihapus permanen.<br><br>'
    + '<span class="text-danger fw-bold">Tindakan ini tidak bisa dibatalkan!</span>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Hapus Semua!',
    cancelButtonText: 'Batal',
    input: 'text',
    inputPlaceholder: 'Ketik HAPUS untuk konfirmasi',
    inputValidator: (value) => {
        if (value !== 'HAPUS') return 'Ketik kata HAPUS untuk melanjutkan.';
    }
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('form_delete_all').submit();
        }
    });
}
</script>

<script>
document.querySelector('textarea[name="keterangan"]')?.addEventListener('input', function () {
    document.getElementById('char_count').textContent = this.value.length;
});

function konfirmasiKirim(e) {
    e.preventDefault();
    const btn  = document.getElementById('btn_kirim');
    const form = btn.closest('form');
    Swal.fire({
        title: 'Kirim Data ke POSSI Jatim?',
        html: 'Data dan dokumen akan dikirim ke <strong>it.possijatim@gmail.com</strong>.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Kirim!',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';
    form.submit();
        }
    });
}

@if(session('success'))
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({ title: 'Berhasil!', text: '{{ addslashes(session("success")) }}',
              icon: 'success', confirmButtonColor: '#0d6efd' });
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
