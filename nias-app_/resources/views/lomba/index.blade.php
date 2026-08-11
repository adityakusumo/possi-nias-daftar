@extends('layouts.app')
@section('title', 'Pendaftaran Lomba')

@section('content')
<div class="card page-card">
<div class="card-header d-flex justify-content-between align-items-center">
<h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Pendaftaran Lomba POSSI Jatim</h5>
<a href="{{ route('welcome.reset') }}" class="btn btn-success btn-sm fw-semibold">
<i class="bi bi-grid-3x3-gap me-1"></i>Kembali ke Portal
</a>
</div>

<div class="card-body p-4 text-center">
<div class="mt-4">
<a href="{{ route('lomba.form_a1') }}" class="btn btn-primary btn-lg px-5 shadow">
<i class="bi bi-pencil-square me-2"></i>Isi Data Lomba
</a>
</div>
<div class="py-5">
<i class="bi bi-cone-striped display-1 text-warning mb-3"></i>
<h3>Halaman Pendaftaran Lomba</h3>
<p class="text-muted">
Selamat Datang, <strong>{{ Auth::user()->nama }}</strong> dari <strong>{{ Auth::user()->namaclub }}</strong>.
</p>
<div class="alert alert-info d-inline-block">
Fitur pendaftaran lomba sedang dalam tahap sinkronisasi dengan database NIAS.
</div>
</div>

{{-- Contoh integrasi data klub dari dbnias --}}
<div class="row justify-content-center">
<div class="col-md-6">
<div class="list-group shadow-sm">
<div class="list-group-item bg-light fw-bold">Informasi Akun Pelatih</div>
<div class="list-group-item d-flex justify-content-between">
<span>Nama Klub</span>
<span class="fw-semibold text-primary">{{ Auth::user()->namaclub }}</span>
</div>
<div class="list-group-item d-flex justify-content-between">
<span>Email Terdaftar</span>
<span>{{ Auth::user()->email }}</span>
</div>
</div>
</div>
</div>
</div>
</div>
@endsection
