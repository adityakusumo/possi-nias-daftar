@extends('layouts.app')
@section('title', 'Form A3 - Nomor Estafet')

@section('content')
<div class="card page-card">
<div class="card-header bg-white p-0 border-bottom-0">
<ul class="nav nav-tabs nav-fill" id="lombaTab" role="tablist">
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Kontingen</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a1_namaatlet') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> Data Atlet</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0 text-muted" href="{{ route('lomba.form_a3_perorangan') }}"><i class="bi bi-check-circle-fill text-success me-1"></i> A3 Perorangan</a></li>
<li class="nav-item"><a class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0" href="{{ route('lomba.form_a3_estafet') }}"><i class="bi bi-4-circle-fill me-1"></i> A3 Estafet</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0" href="{{ route('lomba.proses') }}"><i class="bi bi-5-circle me-1"></i> Proses</a></li>
<li class="nav-item"><a class="nav-link py-3 border-0" href="{{ route('lomba.biaya') }}"><i class="bi bi-6-circle me-1"></i> Biaya</a></li>
</ul>
</div>

<div class="card-body p-4">
@if(session('error'))
<div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
<h5 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Nomor Estafet — {{ $kontingen->nama_kontingen }}</h5>
</div>

<div class="row g-2 mb-3">
<div class="col-auto">
<span class="badge bg-success">Gaya Estafet Aktif: {{ $activeStyles->count() }}</span>
<span class="badge bg-warning text-dark">Entry Estafet: {{ $a3Entries->count() }}</span>
</div>
</div>

{{-- Entry form: team name, KU, gender → gaya by KU → time inputs --}}
<div class="card section-card mb-3">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-people me-1"></i>Entry Waktu Estafet</span>
</div>
<div class="card-body">
<form method="POST" action="{{ route('lomba.save_a3_estafet') }}">
@csrf

<div class="row g-3">
<div class="col-md-3">
<label class="form-label">Nama Tim Estafet <span class="text-danger">*</span></label>
<input type="text" name="nama_team" class="form-control" value="{{ old('nama_team') }}" required maxlength="50" placeholder="Mis: TIM A" style="text-transform:uppercase">
</div>
<div class="col-md-2">
<label class="form-label">KU <span class="text-danger">*</span></label>
<select name="ku" id="kuSelect" class="form-select" required>
<option value="">-- KU --</option>
@foreach($kuList as $k)
<option value="{{ $k->KU }}" {{ old('ku') == $k->KU ? 'selected' : '' }}>{{ $k->KU }}</option>
@endforeach
</select>
</div>
<div class="col-md-2">
<label class="form-label">Gender <span class="text-danger">*</span></label>
<select name="gender" id="genderSelect" class="form-select" required>
<option value="Pa" {{ old('gender', 'Pa') == 'Pa' ? 'selected' : '' }}>Pa (Putra)</option>
<option value="Pi" {{ old('gender') == 'Pi' ? 'selected' : '' }}>Pi (Putri)</option>
<option value="Mix" {{ old('gender') == 'Mix' ? 'selected' : '' }}>Mix (Campuran)</option>
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
<label class="form-label">Pilih Gaya Estafet <span class="text-danger">*</span></label>
<select id="gayaSelect" class="form-select" required>
<option value="">-- Pilih KU dulu --</option>
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
<button type="submit" class="btn btn-primary btn-lg px-4 shadow">
<i class="bi bi-save me-1"></i>Simpan
</button>
</div>
</div>
<small class="text-muted mt-2 d-block">Format: Menit : Detik . Ratusan (contoh: 01:45.30)</small>
</div>

<input type="hidden" name="gaya_prefix" id="gayaPrefix" value="">
<input type="hidden" name="gaya_dist" id="gayaDist" value="">
</form>
</div>
</div>

{{-- Entry yang sudah disimpan --}}
@if($a3Entries->count() > 0)
<div class="card section-card">
<div class="card-header bg-white py-2">
<span class="fw-bold"><i class="bi bi-list me-1"></i>Entry Estafet Tersimpan</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-bordered table-sm mb-0">
<thead class="table-light text-center small">
<tr><th>No</th><th>Nama Tim</th><th>KU</th><th>Gender</th><th>SP</th><th>4x50 S</th><th>4x100 S</th><th>4x200 S</th><th>4x50 B</th><th>4x100 B</th><th>4x50 SM</th><th>4x100 SM</th><th>Aksi</th></tr>
</thead>
<tbody>
@foreach($a3Entries as $e)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $e->NAMAATLET }}</td>
<td>{{ $e->KU }}</td>
<td>{{ $e->GENDER }}</td>
<td>
@if($e->SP == '1' || $e->SP == 'SP')
<span class="badge bg-warning text-dark">SP</span>
@else
<span class="badge" style="background:#6c757d;color:#fff;">Non SP</span>
@endif
</td>
<td>{{ $e->ESTMON200SS ? $e->ESTMON200MM . ':' . $e->ESTMON200SS . '.' . $e->ESTMON200HS : '-' }}</td>
<td>{{ $e->ESTMON400SS ? $e->ESTMON400MM . ':' . $e->ESTMON400SS . '.' . $e->ESTMON400HS : '-' }}</td>
<td>{{ $e->ESTMON800SS ? $e->ESTMON800MM . ':' . $e->ESTMON800SS . '.' . $e->ESTMON800HS : '-' }}</td>
<td>{{ $e->ESTSUB200SS ? $e->ESTSUB200MM . ':' . $e->ESTSUB200SS . '.' . $e->ESTSUB200HS : '-' }}</td>
<td>{{ $e->ESTSUB400SS ? $e->ESTSUB400MM . ':' . $e->ESTSUB400SS . '.' . $e->ESTSUB400HS : '-' }}</td>
<td>{{ $e->ESTMONM200SS ? $e->ESTMONM200MM . ':' . $e->ESTMONM200SS . '.' . $e->ESTMONM200HS : '-' }}</td>
<td>{{ $e->ESTMONM400SS ? $e->ESTMONM400MM . ':' . $e->ESTMONM400SS . '.' . $e->ESTMONM400HS : '-' }}</td>
<td>
<form method="POST" action="{{ route('lomba.a3.delete', $e->IDA3P) }}" class="d-inline" onsubmit="return confirm('Hapus entry ini?');">
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
<a href="{{ route('lomba.form_a3_perorangan') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
<a href="{{ route('lomba.proses') }}" class="btn btn-primary px-4 shadow">Lanjut ke Proses <i class="bi bi-arrow-right ms-1"></i></a>
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
// Estafet gaya database: label → { prefix, dist }
const estafetMap = {
    @foreach($activeStyles as $s)
        @php
            $label = $s->GAYA;
            $parts = explode(' ', $label);
            $leg = (int)($parts[2] ?? 0);
            $type = implode(' ', array_slice($parts, 4)); // "Surface", "Bifin", "Surface Mix", "Bifin Mix"
            $totalDist = $leg * 4;
            $prefixMap = [
                'Surface'     => 'ESTMON',
                'Bifin'       => 'ESTSUB',
                'Surface Mix' => 'ESTMONM',
                'Bifin Mix'   => 'ESTSUBM',
            ];
            $prefix = $prefixMap[$type] ?? 'ESTMON';
        @endphp
    "{{ $label }}": { prefix: "{{ $prefix }}", dist: {{ $totalDist }} },
    @endforeach
};

// KU → eligible estafet gaya from tSyaratPrestasi
const gayaByKu = {
    @foreach($gayaByKu as $ku => $gayas)
    "{{ $ku }}": [
        @foreach($gayas as $g)
        "{{ $g }}",
        @endforeach
    ],
    @endforeach
};

// Active estafet styles from NOLOMBAAKTIF
const activeStyles = new Set([
    @foreach($activeStyles as $s)
    "{{ $s->GAYA }}",
    @endforeach
]);

// ── Populate gaya dropdown filtered by KU + gender ──
function populateGaya() {
    var ku = $('#kuSelect').val();
    var gender = $('#genderSelect').val();
    var $gaya = $('#gayaSelect');
    $gaya.empty();

    if (!ku || !gender) {
        $gaya.append('<option value="">-- Pilih KU & Gender dulu --</option>');
        $('#timeEntryArea').addClass('d-none');
        return;
    }

    var eligible = gayaByKu[ku] || [];
    if (eligible.length === 0) eligible = gayaByKu[ku.toUpperCase()] || [];

    // Filter by active styles from NOLOMBAAKTIF
    var filtered = eligible.filter(function(g) { return activeStyles.has(g); });

    // Gender-gated: Mix styles only for gender=Mix; non-Mix styles for Pa/Pi
    var isMix = (gender === 'Mix');
    filtered = filtered.filter(function(g) {
        var hasMix = g.toLowerCase().indexOf(' mix') !== -1;
        return isMix ? hasMix : !hasMix;
    });

    if (filtered.length === 0) {
        $gaya.append('<option value="">-- Tidak ada gaya untuk KU & Gender ini --</option>');
        $('#timeEntryArea').addClass('d-none');
        return;
    }

    $gaya.append('<option value="">-- Pilih Gaya Estafet --</option>');
    filtered.forEach(function(g) {
        $gaya.append('<option value="' + g + '">' + g + '</option>');
    });
}

$(document).ready(function() {
    var $kuSelect = $('#kuSelect');
    var $genderSelect = $('#genderSelect');
    var $gayaSelect = $('#gayaSelect');

    // When KU or gender changes, rebuild gaya dropdown
    $kuSelect.on('change', populateGaya);
    $genderSelect.on('change', populateGaya);

    // Auto-trigger on page load if old values exist
    var selectedKu = '{{ old('ku') }}';
    var selectedGender = '{{ old('gender', 'Pa') }}';
    if (selectedKu) {
        $kuSelect.val(selectedKu);
        $genderSelect.val(selectedGender);
        populateGaya();
    }

    // When gaya selected, show time entry area
    $gayaSelect.on('change', function() {
        var label = $(this).val();
        if (label && estafetMap[label]) {
            var g = estafetMap[label];
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

    // Hook form submit: inject time field names
    $('form').on('submit', function() {
        var prefix = $('#gayaPrefix').val();
        var dist = $('#gayaDist').val();
        if (!prefix || !dist) {
            alert('Pilih gaya estafet terlebih dahulu.');
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
