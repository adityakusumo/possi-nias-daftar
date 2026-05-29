@extends('layouts.app')
@section('title', 'Form A1 - Nama Atlet')

@section('content')
<div class="card page-card">
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item">
<a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1') }}">
<i class="bi bi-check-circle-fill text-success me-1"></i> Data Kontingen
</a>
</li>
<li class="nav-item">
<a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.form_a1_namaatlet') }}">
<i class="bi bi-2-circle-fill me-1"></i> Data Atlet
</a>
</li>
<li class="nav-item">
<a class="nav-link py-3 border-0" href="{{ route('lomba.form_a3_perorangan') }}">
<i class="bi bi-3-circle me-1"></i> A3 Perorangan
</a>
</li>
<li class="nav-item">
<a class="nav-link py-3 border-0" href="{{ route('lomba.form_a3_estafet') }}">
<i class="bi bi-4-circle me-1"></i> A3 Estafet
</a>
</li>
<li class="nav-item">
<a class="nav-link py-3 border-0" href="{{ route('lomba.proses') }}">
<i class="bi bi-5-circle me-1"></i> Proses
</a>
</li>
<li class="nav-item">
<a class="nav-link py-3 border-0" href="{{ route('lomba.biaya') }}">
<i class="bi bi-6-circle me-1"></i> Biaya
</a>
</li>
</ul>
</div>

<div class="card-body p-4">
@if(session('error'))
<div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
<h5 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Daftar Nama Atlet — {{ $kontingen->nama_kontingen }}</h5>
<button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahAtlet">
<i class="bi bi-plus-lg me-1"></i>Tambah Atlet
</button>
</div>

{{-- Info KU dan Kompetisi --}}
<div class="row g-2 mb-3">
<div class="col-auto">
@if($kompetisi)
<span class="badge bg-primary me-1">Jenis: {{ $kompetisi->JNSKOMPETISI == 'K' ? 'Antar Kota/Kab' : 'Antar Club' }}</span>
<span class="badge bg-{{ $kompetisi->WAJIBNIAS == '1' ? 'warning' : 'info' }} me-1">
NIAS: {{ $kompetisi->WAJIBNIAS == '1' ? 'Wajib' : 'Bebas' }}
</span>
@endif
<span class="badge bg-secondary">KU: {{ $kuList->count() }} grup</span>
<span class="badge bg-info">Atlet: {{ $atletList->count() }}</span>
</div>
</div>

{{-- Tabel Daftar Atlet --}}
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">
<thead class="table-light text-center small">
<tr>
<th width="40">No</th>
<th>Nama Atlet</th>
<th>L/P</th>
<th>Tanggal Lahir</th>
<th>KU</th>
<th>NIAS</th>
<th>SP</th>
<th width="120">Aksi</th>
</tr>
</thead>
<tbody>
@forelse($atletList as $i => $a)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $a->NAMAATLET }}</td>
<td>{{ $a->GENDER == 'Pa' ? 'L' : 'P' }}</td>
<td>{{ $a->TGLLAHIR ? \Carbon\Carbon::parse($a->TGLLAHIR)->format('d/m/Y') : '-' }}</td>
<td>{{ $a->KU }}</td>
<td>{{ $a->NONIAS ?: '-' }}</td>
<td>{{ $a->SP == '1' ? 'SP' : '-' }}</td>
<td>
<div class="btn-group btn-group-sm">
<button class="btn btn-outline-primary btn-sm edit-atlet"
data-id="{{ $a->IDATLET }}"
data-nama="{{ $a->NAMAATLET }}"
data-gender="{{ $a->GENDER }}"
data-tgl="{{ $a->TGLLAHIR ? \Carbon\Carbon::parse($a->TGLLAHIR)->format('Y-m-d') : '' }}"
data-nias="{{ $a->NONIAS }}"
data-bs-toggle="modal" data-bs-target="#modalEditAtlet">
<i class="bi bi-pencil"></i>
</button>
<form method="POST" action="{{ route('lomba.atlet.delete', $a->IDATLET) }}" class="d-inline"
onsubmit="return confirm('Hapus atlet {{ $a->NAMAATLET }}?');">
@csrf @method('DELETE')
<button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
</form>
</div>
</td>
</tr>
@empty
<tr>
<td colspan="8" class="text-center text-muted py-4">Belum ada data atlet. Klik "Tambah Atlet".</td>
</tr>
@endforelse
</tbody>
</table>
</div>

<hr class="my-4">
<div class="d-flex justify-content-between">
<a href="{{ route('lomba.form_a1') }}" class="btn btn-outline-secondary">
<i class="bi bi-arrow-left me-1"></i>Kembali ke Kontingen
</a>
<a href="{{ route('lomba.form_a3_perorangan') }}" class="btn btn-primary px-4 shadow">
Lanjut ke A3 Perorangan <i class="bi bi-arrow-right ms-1"></i>
</a>
</div>
</div>
</div>

<!-- Modal Tambah Atlet -->
<div class="modal fade" id="modalTambahAtlet" tabindex="-1">
<div class="modal-dialog">
<form method="POST" action="{{ route('lomba.atlet.store') }}" class="modal-content">
@csrf
<div class="modal-header bg-primary text-white">
<h6 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Atlet Baru</h6>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="mb-2">
<label class="form-label">Nama Atlet <span class="text-danger">*</span></label>
<input type="text" name="nama_atlet" class="form-control" required maxlength="50" style="text-transform:uppercase">
</div>
<div class="row g-2 mb-2">
<div class="col">
<label class="form-label">Gender <span class="text-danger">*</span></label>
<select name="gender" class="form-select" required>
<option value="">-- Pilih --</option>
<option value="Pa">Laki-laki (Pa)</option>
<option value="Pi">Perempuan (Pi)</option>
</select>
</div>
<div class="col">
<label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
<input type="date" name="tgl_lahir" class="form-control" required>
</div>
</div>
<div class="mb-2">
<label class="form-label">No. NIAS</label>
<input type="text" name="nonias" class="form-control" maxlength="20" placeholder="Kosongkan jika belum punya NIAS">
<small class="text-muted">KU akan dihitung otomatis dari tanggal lahir.</small>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>

<!-- Modal Edit Atlet -->
<div class="modal fade" id="modalEditAtlet" tabindex="-1">
<div class="modal-dialog">
<form method="POST" action="" class="modal-content" id="formEditAtlet">
@csrf @method('PUT')
<div class="modal-header bg-warning text-dark">
<h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Atlet</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="mb-2">
<label class="form-label">Nama Atlet <span class="text-danger">*</span></label>
<input type="text" name="nama_atlet" id="edit_nama" class="form-control" required maxlength="50" style="text-transform:uppercase">
</div>
<div class="row g-2 mb-2">
<div class="col">
<label class="form-label">Gender <span class="text-danger">*</span></label>
<select name="gender" id="edit_gender" class="form-select" required>
<option value="Pa">Laki-laki (Pa)</option>
<option value="Pi">Perempuan (Pi)</option>
</select>
</div>
<div class="col">
<label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
<input type="date" name="tgl_lahir" id="edit_tgl" class="form-control" required>
</div>
</div>
<div class="mb-2">
<label class="form-label">No. NIAS</label>
<input type="text" name="nonias" id="edit_nias" class="form-control" maxlength="20">
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
</div>
</form>
</div>
</div>

<style>
.nav-tabs .nav-link { color: #6c757d; background-color: #f8f9fa; border: none; border-bottom: 2px solid transparent; }
.nav-tabs .nav-link.active { color: #003d8f !important; background-color: #fff !important; border-bottom: 3px solid #003d8f !important; }
.nav-tabs .nav-link.disabled { background-color: #e9ecef; opacity: 0.6; }
.nav-tabs .nav-link:hover:not(.disabled) { background-color: #e2e6ea; }
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Edit modal - populate fields
    $('.edit-atlet').on('click', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var gender = $(this).data('gender');
        var tgl = $(this).data('tgl');
        var nias = $(this).data('nias');
        $('#edit_nama').val(nama);
        $('#edit_gender').val(gender);
        $('#edit_tgl').val(tgl);
        $('#edit_nias').val(nias);
        $('#formEditAtlet').attr('action', '/lomba/atlet/' + id);
    });
});
</script>
@endpush
