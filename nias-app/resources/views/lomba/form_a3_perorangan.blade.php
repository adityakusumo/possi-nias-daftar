@extends('layouts.app')
@section('title', 'Form A3 - Nomor Perorangan')

@section('content')
<div class="card page-card">
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Kontingen</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1_namaatlet') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Atlet</a></li>
<li class="nav-item"><a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.form_a3_perorangan') }}"><i class="bi bi-3-circle-fill me-1"></i> A3 Perorangan</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0" href="{{ route('lomba.form_a3_estafet') }}"><i class="bi bi-4-circle me-1"></i> A3 Estafet</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0" href="{{ route('lomba.proses') }}"><i class="bi bi-5-circle me-1"></i> Proses</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0" href="{{ route('lomba.biaya') }}"><i class="bi bi-6-circle me-1"></i> Biaya</a></li>
</ul>
</div>

<div class="card-body p-4">
@if(session('error'))
<div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
<h5 class="mb-0 fw-bold"><i class="bi bi-trophy me-2"></i>Nomor Perorangan — {{ $kontingen->nama_kontingen }}</h5>
</div>

<div class="row g-2 mb-3">
<div class="col-auto">
<span class="badge bg-info">Atlet: {{ $atletList->count() }}</span>
<span class="badge bg-success">Gaya Aktif: {{ $activeStyles->count() }}</span>
<span class="badge bg-warning text-dark">Entry A3: {{ $a3Entries->count() }}</span>
</div>
</div>

{{-- Entry form: dropdown atlet → gaya by KU → time inputs --}}
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-person me-1"></i>Entry Waktu Perorangan</span>
</div>
<div class="card-body">
<form method="POST" action="{{ route('lomba.save_a3_perorangan') }}">
@csrf

<div class="row g-3">
<div class="col-md-4">
<label class="form-label">Pilih Atlet <span class="text-danger">*</span></label>
<select name="atlet_id" id="atletSelect" class="form-select" required>
<option value="">-- Pilih Atlet --</option>
@foreach($atletList as $a)
<option value="{{ $a->IDATLET }}" data-ku="{{ $a->KU }}"
{{ old('atlet_id') == $a->IDATLET ? 'selected' : '' }}>
{{ $a->NAMAATLET }} ({{ $a->GENDER == 'Pa' ? 'L' : 'P' }}, KU: {{ $a->KU }})
</option>
@endforeach
</select>
</div>

<div class="col-md-1">
<label class="form-label">SP</label>
<select name="sp" class="form-select">
<option value="0" {{ old('sp', '0') == '0' ? 'selected' : '' }}>Non SP</option>
<option value="1" {{ old('sp') == '1' ? 'selected' : '' }}>SP</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Pilih Gaya <span class="text-danger">*</span></label>
<select id="gayaSelect" class="form-select" required>
<option value="">-- Pilih Atlet dulu --</option>
</select>
</div>
</div>

{{-- Dynamic time input area --}}
<div id="timeEntryArea" class="mt-3 p-3 bg-light rounded border d-none">
<div class="row g-2 align-items-center">
<div class="col-md-3">
<strong id="gayaLabel" class="fs-5 text-primary">-</strong>
</div>
<div class="col-md-7">
<div class="row g-1 align-items-center justify-content-center">
<div class="col-auto">
<input type="text" id="timeMM" class="form-control form-control-lg text-center time-digit" maxlength="2" placeholder="MM" style="width:80px;font-size:1.5rem">
</div>
<div class="col-auto fs-3 fw-bold px-0">:</div>
<div class="col-auto">
<input type="text" id="timeSS" class="form-control form-control-lg text-center time-digit" maxlength="2" placeholder="SS" style="width:80px;font-size:1.5rem">
</div>
<div class="col-auto fs-3 fw-bold px-0">.</div>
<div class="col-auto">
<input type="text" id="timeHS" class="form-control form-control-lg text-center time-digit" maxlength="2" placeholder="HS" style="width:80px;font-size:1.5rem">
</div>
</div>
</div>
<div class="col-md-2 text-end">
<button type="submit" class="btn btn-primary btn-lg px-4 shadow" id="btnSimpan">
<i class="bi bi-save me-1"></i>Simpan
</button>
</div>
</div>
<small class="text-muted mt-2 d-block">Format: Menit : Detik . Ratusan (contoh: 00:45.30)</small>
</div>

<input type="hidden" name="gaya_prefix" id="gayaPrefix" value="">
<input type="hidden" name="gaya_dist" id="gayaDist" value="">
</form>
</div>
</div>

{{-- Entry yang sudah disimpan --}}
@if($a3Entries->count() > 0)
<div class="card section-card">
<div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
<span class="fw-bold"><i class="bi bi-list me-1"></i>Entry A3 Tersimpan</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-bordered table-sm mb-0">
<thead class="table-light text-center small">
<tr><th>No</th><th>Atlet</th><th>KU</th><th>L/P</th><th>SP</th><th>50 S</th><th>100 S</th><th>200 S</th><th>400 S</th><th>800 S</th><th>1500 S</th><th>50 B</th><th>100 B</th><th>200 B</th><th>400 B</th><th>A50</th><th>I100</th><th>I400</th><th>I800</th><th>Aksi</th></tr>
</thead>
<tbody>
@foreach($a3Entries as $i => $e)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $e->NAMAATLET }}</td>
<td>{{ $e->KU }}</td>
<td>{{ $e->GENDER == 'Pa' ? 'L' : 'P' }}</td>
<td>
@if($e->SP == '1' || $e->SP == 'SP')
<span class="badge bg-warning text-dark">SP</span>
@else
<span class="badge" style="background:#6c757d;color:#fff;">Non SP</span>
@endif
</td>
<td>{{ $e->MON50SS ? $e->MON50MM . ':' . $e->MON50SS . '.' . $e->MON50HS : '-' }}</td>
<td>{{ $e->MON100SS ? $e->MON100MM . ':' . $e->MON100SS . '.' . $e->MON100HS : '-' }}</td>
<td>{{ $e->MON200SS ? $e->MON200MM . ':' . $e->MON200SS . '.' . $e->MON200HS : '-' }}</td>
<td>{{ $e->MON400SS ? $e->MON400MM . ':' . $e->MON400SS . '.' . $e->MON400HS : '-' }}</td>
<td>{{ $e->MON800SS ? $e->MON800MM . ':' . $e->MON800SS . '.' . $e->MON800HS : '-' }}</td>
<td>{{ $e->MON1500SS ? $e->MON1500MM . ':' . $e->MON1500SS . '.' . $e->MON1500HS : '-' }}</td>
<td>{{ $e->SUB50SS ? $e->SUB50MM . ':' . $e->SUB50SS . '.' . $e->SUB50HS : '-' }}</td>
<td>{{ $e->SUB100SS ? $e->SUB100MM . ':' . $e->SUB100SS . '.' . $e->SUB100HS : '-' }}</td>
<td>{{ $e->SUB200SS ? $e->SUB200MM . ':' . $e->SUB200SS . '.' . $e->SUB200HS : '-' }}</td>
<td>{{ $e->SUB400SS ? $e->SUB400MM . ':' . $e->SUB400SS . '.' . $e->SUB400HS : '-' }}</td>
<td>{{ $e->APN50SS ? $e->APN50MM . ':' . $e->APN50SS . '.' . $e->APN50HS : '-' }}</td>
<td>{{ $e->IMM100SS ? $e->IMM100MM . ':' . $e->IMM100SS . '.' . $e->IMM100HS : '-' }}</td>
<td>{{ $e->IMM400SS ? $e->IMM400MM . ':' . $e->IMM400SS . '.' . $e->IMM400HS : '-' }}</td>
<td>{{ $e->IMM800SS ? $e->IMM800MM . ':' . $e->IMM800SS . '.' . $e->IMM800HS : '-' }}</td>
<td>
<form method="POST" action="{{ route('lomba.a3.delete', $e->IDA3P) }}" class="d-inline" onsubmit="return confirm('Hapus entry A3 ini?');">
@csrf @method('DELETE')
<button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
</div>
@endif

<hr class="my-3">
<div class="d-flex justify-content-between">
<a href="{{ route('lomba.form_a1_namaatlet') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Atlet</a>
<a href="{{ route('lomba.form_a3_estafet') }}" class="btn btn-primary px-4 shadow">Lanjut ke A3 Estafet <i class="bi bi-arrow-right ms-1"></i></a>
</div>
</div>
</div>

<style>
.nav-tabs .nav-link { color: #6c757d; background-color: #f8f9fa; border: none; border-bottom: 2px solid transparent; }
.nav-tabs .nav-link.active { color: #003d8f !important; background-color: #fff !important; border-bottom: 3px solid #003d8f !important; }
.nav-tabs .nav-link:hover:not(.disabled) { background-color: #e2e6ea; }
.time-digit { letter-spacing: 0.1em; font-weight: 700; }
</style>
@endsection

@push('scripts')
<script>
// Gaya database: label → { prefix, dist }
const gayaMap = {
    @foreach($activeStyles as $s)
        @php
            $label = $s->GAYA;
            $parts = explode(' ', $label);
            $dist = (int)($parts[0] ?? 0);
            $gayaName = strtolower($parts[2] ?? '');
            $prefixMap = ['surface' => 'MON', 'bifin' => 'SUB', 'apnea' => 'APN', 'immersion' => 'IMM'];
            $prefix = $prefixMap[$gayaName] ?? 'MON';
        @endphp
    "{{ $label }}": { prefix: "{{ $prefix }}", dist: {{ $dist }} },
    @endforeach
};

// KU → eligible gaya list from tSyaratPrestasi
const gayaByKu = {
    @foreach($gayaByKu as $ku => $gayas)
    "{{ $ku }}": [
        @foreach($gayas as $g)
        "{{ $g }}",
        @endforeach
    ],
    @endforeach
};

// Active styles set (from NOLOMBAAKTIF) for quick lookup
const activeStyles = new Set([
    @foreach($activeStyles as $s)
    "{{ $s->GAYA }}",
    @endforeach
]);

$(document).ready(function() {
    var $atletSelect = $('#atletSelect');
    var $gayaSelect = $('#gayaSelect');

    // When athlete changes, rebuild gaya dropdown based on KU
    $atletSelect.on('change', function() {
        var ku = $(this).find(':selected').data('ku');
        var $gaya = $gayaSelect;
        $gaya.empty();

        if (!ku) {
            $gaya.append('<option value="">-- Pilih Atlet dulu --</option>');
            $('#timeEntryArea').addClass('d-none');
            return;
        }

        // Get gaya for this KU from tSyaratPrestasi
        var eligible = gayaByKu[ku] || [];
        // Also try uppercase KU (some data has 'A', some 'a')
        if (eligible.length === 0) eligible = gayaByKu[ku.toUpperCase()] || [];

        // Filter: only include styles that are in activeStyles (NOLOMBAAKTIF)
        var filtered = eligible.filter(function(g) { return activeStyles.has(g); });

        if (filtered.length === 0) {
            $gaya.append('<option value="">-- Tidak ada gaya untuk KU ini --</option>');
            $('#timeEntryArea').addClass('d-none');
            return;
        }

        $gaya.append('<option value="">-- Pilih Gaya --</option>');
        filtered.forEach(function(g) {
            $gaya.append('<option value="' + g + '">' + g + '</option>');
        });
    });

    // When gaya selected, show time entry area
    $gayaSelect.on('change', function() {
        var label = $(this).val();
        if (label && gayaMap[label]) {
            var g = gayaMap[label];
            $('#gayaLabel').text(label);
            $('#gayaPrefix').val(g.prefix);
            $('#gayaDist').val(g.dist);
            $('#timeMM').val('');
            $('#timeSS').val('');
            $('#timeHS').val('');
            $('#timeEntryArea').removeClass('d-none');
            $('#timeMM').focus();
        } else {
            $('#timeEntryArea').addClass('d-none');
        }
    });

    // Auto-trigger athlete change on page load if old value exists
    var selectedAtlet = '{{ old('atlet_id') }}';
    if (selectedAtlet) {
        $atletSelect.val(selectedAtlet).trigger('change');
    }

    // Hook form submit: copy time values to hidden fields
    $('form').on('submit', function() {
        var prefix = $('#gayaPrefix').val();
        var dist = $('#gayaDist').val();
        if (!prefix || !dist) {
            alert('Pilih gaya terlebih dahulu.');
            return false;
        }
        $(this).append('<input type="hidden" name="' + prefix + dist + 'MM" value="' + $('#timeMM').val() + '">');
        $(this).append('<input type="hidden" name="' + prefix + dist + 'SS" value="' + $('#timeSS').val() + '">');
        $(this).append('<input type="hidden" name="' + prefix + dist + 'HS" value="' + $('#timeHS').val() + '">');
        return true;
    });

    // Numeric only + auto-tab
    $('.time-digit').on('input', function() {
        this.value = this.value.replace(/\D/g, '').substring(0, 2);
        if (this.value.length >= 2) {
            var inputs = $('.time-digit');
            var idx = inputs.index(this);
            if (idx < inputs.length - 1) inputs.eq(idx + 1).focus();
        }
    });
    $('.time-digit').on('keypress', function(e) {
        if (e.which < 48 || e.which > 57) e.preventDefault();
    });
});
</script>
@endpush
