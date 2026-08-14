@extends('layouts.app')
@section('title', 'Data Atlet Existing')

@section('content')
@php
// Helper: buat URL sort untuk header kolom
// - Klik kolom aktif → toggle asc/desc
// - Klik kolom lain → asc
function sortUrl(string $col, string $currentCol, string $currentDir, array $query): string {
    $dir = ($col === $currentCol && $currentDir === 'asc') ? 'desc' : 'asc';
    return request()->fullUrlWithQuery(array_merge($query, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}
$q = request()->only('search', 'club');
@endphp

<div class="card page-card">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
<h5 class="mb-0">
<i class="bi bi-people me-2"></i>Data NIAS —
@if($isAdmin)
    {{ $filterClub ?? 'Semua Club' }}
@else
    {{ $namaclub }}
@endif
</h5>
<div class="d-flex gap-2">
<button type="button" class="btn btn-success btn-sm fw-semibold shadow-sm"
data-bs-toggle="modal" data-bs-target="#exportExistingModal">
<i class="bi bi-file-earmark-arrow-down me-1"></i>Export Data Nias
</button>
<a href="{{ route('nias.index') }}" class="btn btn-light btn-sm">
<i class="bi bi-arrow-left me-1"></i>Kembali
</a>
</div>
</div>

<div class="card-body p-3">

<div class="alert alert-info small mb-3">
<i class="bi bi-info-circle me-2"></i>
@if($isAdmin)
    Menampilkan data atlet dari <strong>{{ $filterClub ?? 'semua club' }}</strong>.
@else
    Menampilkan data atlet untuk club <strong>{{ $namaclub }}</strong>.
@endif
Default urutan: Kadaluwarsa (terbaru di atas), kemudian Nama (A–Z).
</div>

{{-- Search --}}
<form method="GET" action="{{ route('nias.existing') }}" class="row g-2 mb-3">
<input type="hidden" name="sort" value="{{ $sortCol }}">
<input type="hidden" name="dir"  value="{{ $sortDir }}">

@if($isAdmin)
<div class="col-md-4">
<select name="club" class="form-select">
<option value="">— Semua Club —</option>
@foreach($allClubs as $club)
<option value="{{ $club }}" {{ $filterClub === $club ? 'selected' : '' }}>{{ $club }}</option>
@endforeach
</select>
</div>
@endif

<div class="col-md-4">
<div class="input-group">
<span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
<input type="text" name="search" class="form-control border-start-0"
placeholder="Cari nama atau No. NIAS…"
value="{{ request('search') }}">
</div>
</div>
<div class="col-auto d-flex gap-1">
<button type="submit" class="btn btn-primary">Cari</button>
@if(request('search') || request('club'))
<a href="{{ route('nias.existing', ['sort' => $sortCol, 'dir' => $sortDir]) }}"
class="btn btn-outline-secondary">Reset</a>
@endif
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-sm align-middle mb-2">
<thead class="table-dark">
<tr>
{{-- # dan Nama Club tidak sortable --}}
<th>#</th>
<th>Nama Club</th>

@php
// Helper macro untuk render th sortable
$thSort = function(string $col, string $label) use ($sortCol, $sortDir, $q): string {
    $isActive = $col === $sortCol;
    $nextDir  = ($isActive && $sortDir === 'asc') ? 'desc' : 'asc';
    $url      = request()->fullUrlWithQuery(array_merge($q, ['sort' => $col, 'dir' => $nextDir, 'page' => 1]));
    $icon     = '';
    if ($isActive) {
        $icon = $sortDir === 'asc'
        ? ' <i class="bi bi-caret-up-fill small"></i>'
        : ' <i class="bi bi-caret-down-fill small"></i>';
    } else {
        $icon = ' <i class="bi bi-chevron-expand small opacity-50"></i>';
    }
    return '<th><a href="' . $url . '" class="text-white text-decoration-none d-flex align-items-center gap-1 justify-content-between">'
    . e($label) . $icon . '</a></th>';
};
@endphp

{!! $thSort('NAMA',     'Nama') !!}
{!! $thSort('GENDER',   'Jenis Kelamin') !!}
{!! $thSort('TPTLAHIR', 'Tempat Lahir') !!}
{!! $thSort('TGLLAHIR', 'Tanggal Lahir') !!}
{!! $thSort('NONIAS',   'No Nias Jatim') !!}
{!! $thSort('JENISDOM',   'Jenis Dom') !!}
{!! $thSort('NAMAKOTADOM','Kota/Kab Dom') !!}
{!! $thSort('EXPIRED',  'Kadaluwarsa') !!}
</tr>
</thead>
<tbody>
@forelse($records as $r)
<tr class="{{ $r->EXPIRED && \Carbon\Carbon::parse($r->EXPIRED)->isPast() ? 'table-danger' : '' }}">
<td class="text-muted small">{{ $records->firstItem() + $loop->index }}</td>
<td class="small">{{ $r->NAMACLUB }}</td>
<td class="fw-semibold">{{ $r->NAMA }}</td>
<td>
@if(strtoupper($r->GENDER) === 'L' || strtoupper($r->GENDER) === 'PA')
<span class="badge bg-primary">Laki-laki</span>
@else
<span class="badge bg-danger">Perempuan</span>
@endif
</td>
<td class="small">{{ $r->TPTLAHIR }}</td>
<td class="small">
{{ $r->TGLLAHIR ? \Carbon\Carbon::parse($r->TGLLAHIR)->format('d/m/Y') : '—' }}
</td>
<td><code class="small">{{ $r->NONIAS ?? '—' }}</code></td>
<td class="small">{{ $r->JENISDOM ?? '—' }}</td>
<td class="small">{{ $r->NAMAKOTADOM ?? '—' }}</td>
<td class="small {{ $r->EXPIRED && \Carbon\Carbon::parse($r->EXPIRED)->isPast() ? 'text-danger fw-semibold' : '' }}">
{{ $r->EXPIRED ? \Carbon\Carbon::parse($r->EXPIRED)->format('d/m/Y') : '—' }}
</td>
</tr>
@empty
<tr>
<td colspan="10" class="text-center text-muted py-5">
<i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
Tidak ada data atlet existing untuk club ini.
</td>
</tr>
@endforelse
</tbody>
</table>
</div>

{{-- Pagination custom: tanpa simbol panah, cukup Previous / angka / Next --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
<small class="text-muted">
@if($records->total())
Menampilkan {{ $records->firstItem() }}–{{ $records->lastItem() }}
dari <strong>{{ $records->total() }}</strong> data
@endif
</small>

@if($records->hasPages())
<nav>
<ul class="pagination pagination-sm mb-0">
{{-- Previous --}}
@if($records->onFirstPage())
<li class="page-item disabled"><span class="page-link">Previous</span></li>
@else
<li class="page-item">
<a class="page-link" href="{{ $records->previousPageUrl() }}">Previous</a>
</li>
@endif

{{-- Nomor halaman --}}
@foreach($records->getUrlRange(1, $records->lastPage()) as $page => $url)
@if($page == $records->currentPage())
<li class="page-item active"><span class="page-link">{{ $page }}</span></li>
@else
<li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
@endif
@endforeach

{{-- Next --}}
@if($records->hasMorePages())
<li class="page-item">
<a class="page-link" href="{{ $records->nextPageUrl() }}">Next</a>
</li>
@else
<li class="page-item disabled"><span class="page-link">Next</span></li>
@endif
</ul>
</nav>
@endif
</div>

</div>
</div>

{{-- ── Modal Export CSV ──────────────────────────────────────────── --}}
<div class="modal fade" id="exportExistingModal" tabindex="-1"
aria-labelledby="exportExistingModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-scrollable">
<div class="modal-content">
<form method="GET" action="{{ route('nias.existing.export') }}">
<div class="modal-header">
<h5 class="modal-title" id="exportExistingModalLabel">
<i class="bi bi-file-earmark-arrow-down me-2"></i>Export Data Nias
</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>

<div class="modal-body">

{{-- Ikuti filter club & pencarian halaman saat ini --}}
<input type="hidden" name="club" value="{{ request('club') }}">
<input type="hidden" name="search" value="{{ request('search') }}">
<p class="small text-muted mb-3">
<i class="bi bi-info-circle me-1"></i>
Export mengikuti filter club &amp; pencarian di halaman ini.
</p>

{{-- Format export --}}
<div class="mb-3">
<label class="form-label">Format Export</label>
<div class="d-flex gap-4">
<div class="form-check">
<input class="form-check-input" type="radio" name="format"
id="fmtCsv" value="csv" checked>
<label class="form-check-label" for="fmtCsv">
<i class="bi bi-filetype-csv me-1"></i>CSV
</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="format"
id="fmtXlsx" value="xlsx" disabled>
<label class="form-check-label text-muted" for="fmtXlsx">
<i class="bi bi-file-earmark-excel me-1"></i>XLSX
<span class="badge bg-secondary ms-1">Segera hadir</span>
</label>
</div>
</div>
</div>

{{-- Status kadaluwarsa --}}
<div class="mb-3">
<label class="form-label">Status Kadaluwarsa (per hari ini)</label>
<div class="d-flex flex-column gap-2">
<div class="form-check">
<input class="form-check-input" type="radio" name="expired_status"
id="expStatusAll" value="all" checked>
<label class="form-check-label" for="expStatusAll">
Semua (termasuk atlet yang sudah kadaluwarsa)
</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="expired_status"
id="expStatusActive" value="active">
<label class="form-check-label" for="expStatusActive">
Belum kadaluwarsa (aktif per hari ini)
</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="expired_status"
id="expStatusExpired" value="expired">
<label class="form-check-label" for="expStatusExpired">
Sudah kadaluwarsa
</label>
</div>
<div class="form-check d-flex align-items-center gap-2">
<input class="form-check-input" type="radio" name="expired_status"
id="expStatusExpiring" value="expiring">
<label class="form-check-label" for="expStatusExpiring">
Akan kadaluwarsa dalam
</label>
<input type="number" name="expiring_days" id="expiringDays"
class="form-control form-control-sm" style="width:80px"
value="30" min="1" max="3650">
<span class="small text-muted">hari ke depan</span>
</div>
</div>
</div>

{{-- Rentang tanggal kadaluwarsa --}}
<div class="row g-2 mb-3">
<div class="col-md-6">
<label class="form-label" for="expiredFrom">Kadaluwarsa mulai tanggal</label>
<input type="date" name="expired_from" id="expiredFrom" class="form-control">
</div>
<div class="col-md-6">
<label class="form-label" for="expiredTo">Kadaluwarsa sampai tanggal</label>
<input type="date" name="expired_to" id="expiredTo" class="form-control">
</div>
</div>

{{-- Jenis kelamin --}}
<div class="row g-2 mb-3">
<div class="col-md-6">
<label class="form-label" for="exportGender">Jenis Kelamin</label>
<select name="gender" id="exportGender" class="form-select">
<option value="">Semua</option>
<option value="L">Laki-laki</option>
<option value="P">Perempuan</option>
</select>
</div>
</div>

<hr>

{{-- Urutan export --}}
<div class="row g-2">
<div class="col-md-8">
<label class="form-label" for="exportSort">Urutkan berdasarkan</label>
<select name="sort" id="exportSort" class="form-select">
<option value="EXPIRED"    {{ $sortCol === 'EXPIRED'    ? 'selected' : '' }}>Tanggal Kadaluwarsa</option>
<option value="NAMA"       {{ $sortCol === 'NAMA'       ? 'selected' : '' }}>Nama Atlet</option>
<option value="TGLLAHIR"   {{ $sortCol === 'TGLLAHIR'   ? 'selected' : '' }}>Tanggal Lahir</option>
<option value="NONIAS"     {{ $sortCol === 'NONIAS'     ? 'selected' : '' }}>No. NIAS Jatim</option>
<option value="NAMACLUB"   {{ $sortCol === 'NAMACLUB'   ? 'selected' : '' }}>Nama Club</option>
<option value="GENDER"     {{ $sortCol === 'GENDER'     ? 'selected' : '' }}>Jenis Kelamin</option>
<option value="TPTLAHIR"   {{ $sortCol === 'TPTLAHIR'   ? 'selected' : '' }}>Tempat Lahir</option>
<option value="JENISDOM"   {{ $sortCol === 'JENISDOM'   ? 'selected' : '' }}>Jenis Domisili</option>
<option value="NAMAKOTADOM"{{ $sortCol === 'NAMAKOTADOM'? 'selected' : '' }}>Kota/Kab Domisili</option>
<option value="TGLDAFTAR"  {{ $sortCol === 'TGLDAFTAR'  ? 'selected' : '' }}>Tanggal Daftar</option>
</select>
</div>
<div class="col-md-4">
<label class="form-label" for="exportDir">Arah Urutan</label>
<select name="dir" id="exportDir" class="form-select">
<option value="desc" {{ $sortDir === 'desc' ? 'selected' : '' }}>Terbaru / Z–A</option>
<option value="asc"  {{ $sortDir === 'asc'  ? 'selected' : '' }}>Terlama / A–Z</option>
</select>
</div>
</div>

</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-success">
<i class="bi bi-download me-1"></i>Download CSV
</button>
</div>
</form>
</div>
</div>
</div>

@push('scripts')
<script>
// Ketik angka hari → otomatis pilih radio "Akan kadaluwarsa dalam N hari"
document.getElementById('expiringDays')?.addEventListener('input', function () {
document.getElementById('expStatusExpiring').checked = true;
});
</script>
@endpush

@endsection
