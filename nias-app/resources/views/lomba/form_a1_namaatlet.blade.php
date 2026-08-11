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
<form method="POST" action="{{ route('lomba.atlet.store') }}" class="modal-content" id="formTambahAtlet">
@csrf
{{-- Expired names list for client-side validation --}}
<script>var EXPIRED_ATHLETE_NAMES = {!! json_encode($allNiasAtlets->where('is_expired', true)->pluck('NAMA')->map(fn($n) => strtoupper(trim($n)))->values()) !!};</script>
<div class="modal-header bg-primary text-white">
<h6 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Atlet Baru</h6>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">

{{-- Form fields --}}
<div class="mb-2">
<label class="form-label">Nama Atlet <span class="text-danger">*</span></label>
<input type="text" name="nama_atlet" id="add_nama_atlet" class="form-control"
       list="niasAtletList" required maxlength="50" style="text-transform:uppercase"
       placeholder="Ketik atau pilih dari daftar atlet NIAS">
<datalist id="niasAtletList">
    @foreach($niasAtlets as $na)
    <option value="{{ $na->NAMA }}"
        data-gender="{{ $na->GENDER }}"
        data-tgl="{{ $na->TGLLAHIR ? \Carbon\Carbon::parse($na->TGLLAHIR)->format('Y-m-d') : '' }}"
        data-nias="{{ $na->NONIAS ?? '' }}">
    @endforeach
</datalist>
<small class="text-muted">Daftar atlet dari database NIAS sesuai {{ $kontingen->jns_kompetisi === 'C' ? 'club' : ($kontingen->jns_kompetisi === 'K' ? 'kota/kab' : 'provinsi') }}.</small>
</div>
<div class="row g-2 mb-2">
<div class="col">
<label class="form-label">Gender <span class="text-danger">*</span></label>
<select name="gender" id="add_gender" class="form-select" required>
<option value="">-- Pilih --</option>
<option value="Pa">Laki-laki (Pa)</option>
<option value="Pi">Perempuan (Pi)</option>
</select>
</div>
<div class="col">
<label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
<input type="date" name="tgl_lahir" id="add_tgl_lahir" class="form-control" required>
</div>
</div>
<div class="mb-2">
<label class="form-label">No. NIAS</label>
<input type="text" name="nonias" id="add_nonias" class="form-control" maxlength="20" placeholder="Kosongkan jika belum punya NIAS">
<small class="text-muted">KU akan dihitung otomatis dari tanggal lahir.</small>
</div>

<hr>

{{-- Athlete list table --}}
<h6 class="fw-bold mb-2"><i class="bi bi-list-ul me-1"></i>Daftar Atlet NIAS</h6>
<div class="table-responsive" style="max-height:260px; overflow-y:auto;">
<table class="table table-sm table-hover table-bordered mb-0" id="niasAtletTable">
<thead class="table-light small sticky-top">
<tr>
<th>Nama</th>
<th>L/P</th>
<th>Tgl Lahir</th>
<th>NIAS</th>
<th>Status</th>
</tr>
</thead>
<tbody>
@foreach($allNiasAtlets as $na)
<tr class="nias-row {{ $na->is_expired ? 'table-danger expired-row' : 'cursor-pointer' }}"
    data-nama="{{ $na->NAMA }}"
    data-gender="{{ $na->GENDER }}"
    data-tgl="{{ $na->TGLLAHIR ? \Carbon\Carbon::parse($na->TGLLAHIR)->format('Y-m-d') : '' }}"
    data-nias="{{ $na->NONIAS ?? '' }}"
    data-expired="{{ $na->is_expired ? '1' : '0' }}">
    <td class="small">{{ $na->NAMA }}</td>
    <td class="small text-center">{{ $na->GENDER == 'Pa' ? 'L' : 'P' }}</td>
    <td class="small">{{ $na->TGLLAHIR ? \Carbon\Carbon::parse($na->TGLLAHIR)->format('d/m/Y') : '-' }}</td>
    <td class="small">{{ $na->NONIAS ?: '-' }}</td>
    <td class="small text-center">
        @if($na->is_expired)
            <span class="badge bg-danger">Expired</span>
        @else
            <span class="badge bg-success">Aktif</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@if($allNiasAtlets->isEmpty())
<p class="text-muted small text-center mt-2 mb-0"><i class="bi bi-inbox me-1"></i>Tidak ada atlet NIAS ditemukan.</p>
@else
<p class="text-muted small mt-1 mb-0">
    <i class="bi bi-info-circle me-1"></i>Klik baris atlet untuk autofill.
    <span class="text-danger">Baris merah = NIAS expired, tidak bisa dipilih.</span>
</p>
@endif

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
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: #d4edda !important; }
.expired-row { cursor: not-allowed; opacity: 0.85; }
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

    // ── Shared auto-fill helper ──
    var $namaInput = $('#add_nama_atlet');
    var $gender    = $('#add_gender');
    var $tglLahir  = $('#add_tgl_lahir');
    var $nonias    = $('#add_nonias');

    function fillAtletFields(nama, gender, tgl, nias) {
        $namaInput.val(nama);
        $gender.val(gender || '');
        $tglLahir.val(tgl || '');
        $nonias.val(nias || '');
    }

    // ── Auto-fill from NIAS datalist ──
    $namaInput.on('input', function() {
        var val = this.value.toUpperCase().trim();
        if (!val) return;

        var $option = $('#niasAtletList option').filter(function() {
            return this.value.toUpperCase().trim() === val;
        }).first();

        if ($option.length) {
            $gender.val($option.data('gender') || '');
            $tglLahir.val($option.data('tgl') || '');
            $nonias.val($option.data('nias') || '');
        }
    });

    // ── Block submit if the entered name is an expired athlete ──
    $('#formTambahAtlet').on('submit', function(e) {
        var enteredName = $namaInput.val().toUpperCase().trim();
        if (enteredName && EXPIRED_ATHLETE_NAMES.indexOf(enteredName) !== -1) {
            e.preventDefault();
            alert('Atlet "' + enteredName + '" memiliki status NIAS expired dan tidak dapat didaftarkan.');
            return false;
        }
    });

    // ── Auto-fill from table row click ──
    $('#niasAtletTable').on('click', '.nias-row', function() {
        if ($(this).data('expired') == 1) {
            alert('Atlet dengan status NIAS expired tidak dapat dipilih.');
            return;
        }
        fillAtletFields(
            $(this).data('nama'),
            $(this).data('gender'),
            $(this).data('tgl'),
            $(this).data('nias')
        );
    });
});
</script>
@endpush
