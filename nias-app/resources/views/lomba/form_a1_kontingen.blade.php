@extends('layouts.app')
@section('title', 'Form A1 - Pendaftaran Lomba')

@section('content')
<div class="card page-card">
{{-- Header dengan Navigasi Tab --}}
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item">
<a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.form_a1') }}">
<i class="bi bi-1-circle-fill me-1"></i> Data Kontingen
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ !$isKontingenSaved ? 'disabled text-muted' : '' }} py-3 border-0"
href="{{ $isKontingenSaved ? route('lomba.form_a1_namaatlet') : '#' }}">
<i class="bi bi-2-circle me-1"></i> Data Atlet
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ !$isKontingenSaved ? 'disabled text-muted' : '' }} py-3 border-0"
href="{{ $isKontingenSaved ? route('lomba.form_a3_perorangan') : '#' }}">
<i class="bi bi-3-circle me-1"></i> A3 Perorangan
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ !$isKontingenSaved ? 'disabled text-muted' : '' }} py-3 border-0"
href="{{ $isKontingenSaved ? route('lomba.form_a3_estafet') : '#' }}">
<i class="bi bi-4-circle me-1"></i> A3 Estafet
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ !$isKontingenSaved ? 'disabled text-muted' : '' }} py-3 border-0"
href="{{ $isKontingenSaved ? route('lomba.proses') : '#' }}">
<i class="bi bi-5-circle me-1"></i> Proses
</a>
</li>
<li class="nav-item">
<a class="nav-link {{ !$isKontingenSaved ? 'disabled text-muted' : '' }} py-3 border-0"
href="{{ $isKontingenSaved ? route('lomba.biaya') : '#' }}">
<i class="bi bi-6-circle me-1"></i> Biaya
</a>
</li>
</ul>
</div>

<div class="card-body p-4">
@if(session('error'))
<div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

@if($isKontingenSaved)
<div class="alert alert-success d-flex align-items-center small py-2">
<i class="bi bi-check-circle-fill me-2"></i>
Data kontingen sudah tersimpan. Lanjut ke <strong>Data Atlet</strong> atau perbarui di bawah.
</div>
@else
<div class="alert alert-info d-flex align-items-center small py-2">
<i class="bi bi-info-circle-fill me-2"></i>
Lengkapi <strong>Data Kontingen</strong> terlebih dahulu.
</div>
@endif

<form id="save-kontingen-form" action="{{ route('form_a1.saveKontingen') }}" method="POST">
@csrf
<div class="row g-4">
<div class="col-md-5">
<div class="p-3 bg-light rounded shadow-sm border">
<label class="form-label d-block mb-3 fw-bold">Jenis Kompetisi</label>
<div class="form-check mb-2">
<input class="form-check-input" type="radio" name="jnsKompetisi" id="jnsKab" value="K"
{{ old('jnsKompetisi', $kontingen->jns_kompetisi ?? 'K') == 'K' ? 'checked' : '' }}>
<label class="form-check-label" for="jnsKab">Antar Kabupaten / Kota</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="jnsKompetisi" id="jnsPerkumpulan" value="P"
{{ old('jnsKompetisi', $kontingen->jns_kompetisi ?? '') == 'P' ? 'checked' : '' }}>
<label class="form-check-label" for="jnsPerkumpulan">Antar Perkumpulan / Club</label>
</div>
</div>
</div>

<div class="col-md-7">
<div class="mb-3">
<label class="form-label fw-bold">Nama Kontingen / Club</label>
<input type="text" name="nama_kontingen" class="form-control bg-light"
value="{{ Auth::user()?->namaclub ?? old('nama_kontingen', $kontingen->nama_kontingen ?? '') }}" readonly>
</div>

<div id="detail_lokasi">
<div class="mb-3">
<label class="form-label fw-bold">Pilih Kabupaten / Kota</label>
<select id="select_kota_master" class="form-select shadow-sm">
<option value="">-- Pilih Wilayah --</option>
@foreach($listKota as $kota)
<option value="{{ $kota->ID }}"
data-jenis="{{ $kota->JENIS }}"
data-nama="{{ $kota->NAMAKOTA }}"
{{ (old('nama_wilayah', $kontingen->nama_wilayah ?? '') == $kota->NAMAKOTA) ? 'selected' : '' }}>
{{ $kota->JENIS }} {{ $kota->NAMAKOTA }}
</option>
@endforeach
</select>
</div>
<input type="hidden" name="jenis" id="input_jenis_wilayah" value="{{ old('jenis', $kontingen->jenis_wilayah ?? '') }}">
<input type="hidden" name="nama_wilayah" id="input_nama_wilayah" value="{{ old('nama_wilayah', $kontingen->nama_wilayah ?? '') }}">

<div class="mb-3">
<label class="form-label">Provinsi</label>
<input type="text" name="provinsi" class="form-control bg-light" value="JAWA TIMUR" readonly>
</div>
</div>
</div>
</div>

<hr class="my-4">
<div class="d-flex justify-content-end">
<button type="submit" class="btn btn-primary px-4 shadow">
<i class="bi bi-save me-1"></i>Simpan & Lanjutkan
</button>
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
    function toggleWilayah() {
        let val = $('input[name="jnsKompetisi"]:checked').val();
        $('#detail_lokasi').toggle(val !== 'P');
    }
    toggleWilayah();
    $('input[name="jnsKompetisi"]').on('change', toggleWilayah);

    $('#select_kota_master').on('change', function() {
        let selected = $(this).find(':selected');
        $('#input_jenis_wilayah').val(selected.data('jenis') || '');
        $('#input_nama_wilayah').val(selected.data('nama') || '');
    });
});
</script>
@endpush
