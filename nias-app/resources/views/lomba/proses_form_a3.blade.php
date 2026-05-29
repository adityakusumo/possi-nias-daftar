@extends('layouts.app')
@section('title', 'Proses Form A3')

@section('content')
<div class="card page-card">
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Kontingen</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1_namaatlet') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Atlet</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a3_perorangan') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> A3 Perorangan</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a3_estafet') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> A3 Estafet</a></li>
<li class="nav-item"><a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.proses') }}"><i class="bi bi-5-circle-fill me-1"></i> Proses</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0" href="{{ route('lomba.biaya') }}"><i class="bi bi-6-circle me-1"></i> Biaya</a></li>
</ul>
</div>

<div class="card-body p-4">
@if(session('error'))
<div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

<h5 class="fw-bold mb-3"><i class="bi bi-gear-fill me-2"></i>Proses Form A3 — {{ $kontingen->nama_kontingen }}</h5>

<div class="row g-3 mb-4">
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h2 mb-1">{{ $atletCount }}</div>
<div class="text-muted small">Atlet Terdaftar</div>
</div>
</div>
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h2 mb-1">{{ $a3Count }}</div>
<div class="text-muted small">Entry A3 (Perorangan + Estafet)</div>
</div>
</div>
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h2 mb-1">{{ $pesertaEmailCount }}</div>
<div class="text-muted small">Entry PesertaEmail Saat Ini</div>
</div>
</div>
<div class="col-md-3">
<div class="card section-card text-center p-3">
<div class="h2 mb-1">{{ $event->NAMAEVENT ?? '-' }}</div>
<div class="text-muted small">Event Aktif</div>
</div>
</div>
</div>

@if($event)
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-calendar-event me-1"></i>Info Event</span>
</div>
<div class="card-body py-2">
<div class="row">
<div class="col-md-3"><strong>Event:</strong> {{ $event->NAMAEVENT }}</div>
<div class="col-md-3"><strong>Kode:</strong> {{ $event->KDEVENT }}</div>
<div class="col-md-3"><strong>Tanggal:</strong> {{ $event->TGLMULAIEVENT ? date('d/m/Y', strtotime($event->TGLMULAIEVENT)) : '-' }} s.d. {{ $event->TGLAKHIREVENT ? date('d/m/Y', strtotime($event->TGLAKHIREVENT)) : '-' }}</div>
<div class="col-md-3"><strong>Lokasi:</strong> {{ $event->LOKASI }}</div>
</div>
</div>
</div>

{{-- Detail Perorangan --}}
@if(count($peroranganDetails) > 0)
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-person me-1"></i>Detail Perorangan</span>
</div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-light">
<tr><th>Atlet</th><th>G</th><th>KU</th><th>SP</th><th>Gaya</th><th>Waktu</th></tr>
</thead>
<tbody>
@foreach($peroranganDetails as $atlet)
@foreach($atlet['entries'] as $i => $entry)
<tr>
@if($i === 0)
<td rowspan="{{ count($atlet['entries']) }}" class="fw-semibold">{{ $atlet['nama'] }}</td>
<td rowspan="{{ count($atlet['entries']) }}">{{ $atlet['gender'] }}</td>
<td rowspan="{{ count($atlet['entries']) }}">{{ $atlet['ku'] }}</td>
<td rowspan="{{ count($atlet['entries']) }}">
@if($atlet['sp'] == '1' || $atlet['sp'] == 'SP')
<span class="badge bg-warning text-dark">SP</span>
@else
<span class="badge" style="background:#6c757d;color:#fff;">Non SP</span>
@endif
</td>
@endif
<td>{{ $entry['gaya'] }}</td>
<td class="font-monospace">{{ $entry['waktu'] }}</td>
</tr>
@endforeach
@endforeach
</tbody>
</table>
</div>
</div>
@endif

{{-- Detail Estafet --}}
@if(count($estafetDetails) > 0)
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-people me-1"></i>Detail Estafet</span>
</div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-light">
<tr><th>Team</th><th>G</th><th>KU</th><th>SP</th><th>Gaya</th><th>Waktu</th></tr>
</thead>
<tbody>
@foreach($estafetDetails as $team)
@foreach($team['entries'] as $i => $entry)
<tr>
@if($i === 0)
<td rowspan="{{ count($team['entries']) }}" class="fw-semibold">{{ $team['nama'] }}</td>
<td rowspan="{{ count($team['entries']) }}">{{ $team['gender'] }}</td>
<td rowspan="{{ count($team['entries']) }}">{{ $team['ku'] }}</td>
<td rowspan="{{ count($team['entries']) }}">
@if($team['sp'] == '1' || $team['sp'] == 'SP')
<span class="badge bg-warning text-dark">SP</span>
@else
<span class="badge" style="background:#6c757d;color:#fff;">Non SP</span>
@endif
</td>
@endif
<td>{{ $entry['gaya'] }}</td>
<td class="font-monospace">{{ $entry['waktu'] }}</td>
</tr>
@endforeach
@endforeach
</tbody>
</table>
</div>
</div>
@endif

@if($a3Count > 0 && $atletCount > 0)
<form method="POST" action="{{ route('lomba.run_proses') }}" onsubmit="return confirm('KIRIM FORM A3 ke panitia?');">
@csrf

<div class="d-flex justify-content-between">
<a href="{{ route('lomba.form_a3_estafet') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
<button type="submit" class="btn btn-success px-5 shadow">
<i class="bi bi-play-fill me-1"></i>Proses Form A3
</button>
</div>
</form>
@else
<div class="alert alert-danger">
<i class="bi bi-x-circle me-2"></i>
Data belum lengkap. Pastikan sudah mengisi atlet ({{ $atletCount }}) dan entry A3 ({{ $a3Count }}).
</div>
<div class="d-flex justify-content-between">
<a href="{{ route('lomba.form_a3_estafet') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>
@endif

@if($pesertaEmailCount > 0)
<div class="mt-3 alert alert-success">
<i class="bi bi-check-circle me-2"></i>
Sudah ada {{ $pesertaEmailCount }} entry di PesertaEmail. Jalankan proses lagi jika ada perubahan data A3.
</div>
@endif

@else
<div class="alert alert-danger">
<i class="bi bi-x-circle me-2"></i>
Data Event belum diisi. Hubungi panitia untuk mengisi data event.
</div>
@endif

<hr class="my-4">
<div class="d-flex justify-content-between">
<a href="{{ route('lomba.form_a3_estafet') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali ke A3 Estafet</a>
<a href="{{ route('lomba.biaya') }}" class="btn btn-primary px-4 shadow">Lanjut ke Hitung Biaya <i class="bi bi-arrow-right ms-1"></i></a>
</div>
</div>
</div>

<style>
.nav-tabs .nav-link { color: #6c757d; background-color: #f8f9fa; border: none; border-bottom: 2px solid transparent; }
.nav-tabs .nav-link.active { color: #003d8f !important; background-color: #fff !important; border-bottom: 3px solid #003d8f !important; }
.nav-tabs .nav-link:hover:not(.disabled) { background-color: #e2e6ea; }
</style>
@endsection
