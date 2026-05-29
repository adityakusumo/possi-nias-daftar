@extends('layouts.app')
@section('title', 'Hitung Biaya Pendaftaran')

@section('content')
<div class="card page-card">
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Kontingen</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1_namaatlet') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Atlet</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a3_perorangan') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> A3 Perorangan</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a3_estafet') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> A3 Estafet</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.proses') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Proses</a></li>
<li class="nav-item"><a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.biaya') }}"><i class="bi bi-6-circle-fill me-1"></i> Biaya</a></li>
</ul>
</div>

<div class="card-body p-4">
@if(session('error'))
<div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

<h5 class="fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Hitung Biaya — {{ $kontingen->nama_kontingen }}</h5>

<div class="alert alert-info">
<i class="bi bi-info-circle me-2"></i>
Perhitungan biaya berdasarkan data di <strong>PesertaEmail</strong>. Jalankan <strong>Proses Form A3</strong> terlebih dahulu jika ada perubahan.
</div>

<div class="row g-3 mb-4">
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h3 mb-0 text-primary">{{ $pesertaEmailCount }}</div>
<div class="text-muted small">Entry di PesertaEmail</div>
</div>
</div>
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h3 mb-0 text-primary">{{ count($feeRecords) > 0 ? $feeRecords->first()->JMLATLET : 0 }}</div>
<div class="text-muted small">Atlet (unik)</div>
</div>
</div>
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h3 mb-0 text-primary">{{ count($feeRecords) > 0 ? $feeRecords->first()->JMLNOLOMBA : 0 }}</div>
<div class="text-muted small">Total Nomor Lomba</div>
</div>
</div>
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h3 mb-0 text-success">{{ count($feeRecords) > 0 ? 'Rp ' . number_format($feeRecords->first()->RPTOTAL, 0, ',', '.') : 'Rp 0' }}</div>
<div class="text-muted small">Total Biaya</div>
</div>
</div>
</div>

{{-- Tombol Hitung Biaya --}}
@if($pesertaEmailCount > 0)
<form method="POST" action="{{ route('lomba.run_biaya') }}" onsubmit="return confirm('Hitung ulang biaya? Data perhitungan sebelumnya akan dihapus.');">
@csrf
<div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
<button type="submit" class="btn btn-success px-5 shadow">
<i class="bi bi-calculator-fill me-1"></i>Hitung / Hitung Ulang Biaya
</button>
</div>
</form>
@else
<div class="alert alert-warning">
<i class="bi bi-exclamation-triangle me-2"></i>
Belum ada data di PesertaEmail. Jalankan <a href="{{ route('lomba.proses') }}" class="alert-link">Proses Form A3</a> terlebih dahulu.
</div>
@endif

{{-- Detail Biaya --}}
@if(count($feeRecords) > 0)
@php $fee = $feeRecords->first(); @endphp
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-receipt me-1"></i>Rincian Biaya</span>
</div>
<div class="card-body">
<table class="table table-bordered">
<tr><th style="width:250px">Jumlah Atlet</th><td>{{ $fee->JMLATLET }} orang</td></tr>
<tr><th>Jumlah Entry Perorangan</th><td>{{ $jmlPerorangan }} nomor</td></tr>
<tr><th>Jumlah Entry Estafet</th><td>{{ $jmlEstafet }} nomor</td></tr>
<tr><th>Tarif Perorangan</th><td>{{ $jmlPerorangan }} x Rp {{ number_format($lombaTarifPerorangan, 0, ',', '.') }} = Rp {{ number_format($jmlPerorangan * $lombaTarifPerorangan, 0, ',', '.') }}</td></tr>
<tr><th>Tarif Estafet</th><td>{{ $jmlEstafet }} x Rp {{ number_format($lombaTarifEstafet, 0, ',', '.') }} = Rp {{ number_format($jmlEstafet * $lombaTarifEstafet, 0, ',', '.') }}</td></tr>
<tr><th>Total Biaya Daftar</th><td>Rp {{ number_format($fee->RPTOTDAFTAR, 0, ',', '.') }}</td></tr>
@if($adaDenda)
<tr><th>Deposit</th><td>Rp {{ number_format($fee->RPDEPOSIT, 0, ',', '.') }}</td></tr>
@endif
<tr><th>Biaya Lain-lain</th><td>Rp {{ number_format($fee->RPLAIN, 0, ',', '.') }}</td></tr>
<tr class="table-success fw-bold"><th>TOTAL</th><td>Rp {{ number_format($fee->RPTOTAL, 0, ',', '.') }}</td></tr>
</table>
<small class="text-muted">No. Kwitansi: {{ $fee->NOKWT }}</small>
</div>
</div>
@endif

{{-- Tarif Reference --}}
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-info-circle me-1"></i>Referensi Tarif</span>
</div>
<div class="card-body py-2">
<div class="row">
<div class="col-md-6">
<h6 class="fw-bold">Tarif Daftar (fixed)</h6>
<table class="table table-sm table-bordered">
<thead class="table-light"><tr><th>Nomor</th><th>Tarif</th></tr></thead>
<tbody>
<tr><td>Perorangan (per nomor)</td><td>Rp {{ number_format($lombaTarifPerorangan, 0, ',', '.') }}</td></tr>
<tr><td>Estafet (per nomor)</td><td>Rp {{ number_format($lombaTarifEstafet, 0, ',', '.') }}</td></tr>
</tbody>
</table>
</div>
@if($adaDenda)
<div class="col-md-3">
<h6 class="fw-bold">Deposit (per range atlet)</h6>
<table class="table table-sm table-bordered">
<thead class="table-light"><tr><th>Range</th><th>Deposit</th></tr></thead>
<tbody>
@forelse($deposits as $d)
<tr><td>{{ $d->JMLATLETMULAI }}-{{ $d->JMLATLETSAMPAI }}</td><td>Rp {{ number_format($d->RPDEPOSIT, 0, ',', '.') }}</td></tr>
@empty
<tr><td colspan="2" class="text-muted">-</td></tr>
@endforelse
</tbody>
</table>
</div>
@endif
<div class="col-md-3">
<h6 class="fw-bold">Biaya Lain-lain</h6>
<table class="table table-sm table-bordered">
<thead class="table-light"><tr><th>Keterangan</th><th>Tarif</th></tr></thead>
<tbody>
@forelse($extraFees as $e)
<tr><td>{{ $e->KETERANGAN }}</td><td>Rp {{ number_format($e->RPBIAYAEXTRA, 0, ',', '.') }}</td></tr>
@empty
<tr><td colspan="2" class="text-muted">-</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>
</div>

<hr class="my-4">
<div class="d-flex justify-content-between">
<a href="{{ route('lomba.proses') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Proses</a>
<a href="{{ route('lomba.form_a1') }}" class="btn btn-outline-primary">Ke Halaman Awal <i class="bi bi-house ms-1"></i></a>
</div>
</div>
</div>

<style>
.nav-tabs .nav-link { color: #6c757d; background-color: #f8f9fa; border: none; border-bottom: 2px solid transparent; }
.nav-tabs .nav-link.active { color: #003d8f !important; background-color: #fff !important; border-bottom: 3px solid #003d8f !important; }
.nav-tabs .nav-link:hover:not(.disabled) { background-color: #e2e6ea; }
</style>
@endsection
