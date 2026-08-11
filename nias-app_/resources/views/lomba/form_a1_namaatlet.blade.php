@extends('layouts.app')
@section('title', 'Form A1 - Nama Atlet')

@section('content')
<div class="card page-card">
{{-- Header dengan Navigasi Tab --}}
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item">
<a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1') }}">
<i class="bi bi-check-circle-fill text-success me-2"></i>Data Kontingen
</a>
</li>
<li class="nav-item">
<a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.form_a1_namaatlet') }}">
<i class="bi bi-2-circle-fill me-2"></i>Data Nama Atlet
</a>
</li>
<li class="nav-item">
<a class="nav-link disabled py-3 border-0" href="#">
<i class="bi bi-3-circle me-2"></i>Form Selanjutnya
</a>
</li>
</ul>
</div>

<div class="card-body p-4">
<div class="d-flex justify-content-between align-items-center mb-4">
<h5 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Daftar Nama Atlet</h5>
<button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahAtlet">
<i class="bi bi-plus-lg me-1"></i>Tambah Atlet
</button>
</div>

{{-- Tabel Daftar Atlet --}}
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">
<thead class="table-light text-center small uppercase">
<tr>
<th width="50">No</th>
<th>Nama Atlet</th>
<th>L/P</th>
<th>Tempat, Tgl Lahir</th>
<th>KU</th>
<th width="100">Aksi</th>
</tr>
</thead>
<tbody>
{{-- Loop data atlet nanti di sini --}}
<tr>
<td colspan="6" class="text-center text-muted py-4">
Belum ada data atlet. Silakan klik tombol "Tambah Atlet".
</td>
</tr>
</tbody>
</table>
</div>

<hr class="my-4">

<div class="d-flex justify-content-between">
<a href="{{ route('lomba.form_a1') }}" class="btn btn-outline-secondary">
<i class="bi bi-arrow-left me-1"></i>Kembali ke Kontingen
</a>
<button type="button" class="btn btn-primary px-4 shadow">
Lanjut ke Form Berikutnya <i class="bi bi-arrow-right ms-1"></i>
</button>
</div>
</div>
</div>

{{-- CSS Tab Browser (Pastikan konsisten) --}}
<style>
.nav-tabs .nav-link { color: #6c757d; background-color: #f8f9fa; border: none; border-bottom: 2px solid transparent; }
.nav-tabs .nav-link.active { color: #003d8f !important; background-color: #fff !important; border-bottom: 3px solid #003d8f !important; }
.nav-tabs .nav-link.disabled { background-color: #e9ecef; opacity: 0.6; }
</style>
@endsection
