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
$q = request()->only('search');
@endphp

<div class="card page-card">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
<h5 class="mb-0">
<i class="bi bi-people me-2"></i>Data NIAS — {{ $namaclub }}
</h5>
<a href="{{ route('nias.index') }}" class="btn btn-light btn-sm">
<i class="bi bi-arrow-left me-1"></i>Kembali
</a>
</div>

<div class="card-body p-3">

<div class="alert alert-info small mb-3">
<i class="bi bi-info-circle me-2"></i>
Menampilkan data atlet yang sudah terdaftar di database NIAS Jawa Timur untuk club <strong>{{ $namaclub }}</strong>.
Default urutan: Kadaluwarsa (terlama di atas), kemudian Nama (A–Z).
</div>

{{-- Search --}}
<form method="GET" action="{{ route('nias.existing') }}" class="row g-2 mb-3">
{{-- Pertahankan sort aktif saat search --}}
<input type="hidden" name="sort" value="{{ $sortCol }}">
<input type="hidden" name="dir"  value="{{ $sortDir }}">

<div class="col-md-5">
<div class="input-group">
<span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
<input type="text" name="search" class="form-control border-start-0"
placeholder="Cari nama atau No. NIAS…"
value="{{ request('search') }}">
</div>
</div>
<div class="col-auto d-flex gap-1">
<button type="submit" class="btn btn-primary">Cari</button>
@if(request('search'))
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
<td class="small {{ $r->EXPIRED && \Carbon\Carbon::parse($r->EXPIRED)->isPast() ? 'text-danger fw-semibold' : '' }}">
{{ $r->EXPIRED ? \Carbon\Carbon::parse($r->EXPIRED)->format('d/m/Y') : '—' }}
</td>
</tr>
@empty
<tr>
<td colspan="8" class="text-center text-muted py-5">
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
@endsection
