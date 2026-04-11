@extends('layouts.app')
@section('title', 'Update / Perpanjang NIAS')

@section('content')
<div class="card page-card">
<div class="card-header d-flex justify-content-between align-items-center">
<h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Form Update / Perpanjang NIAS</h5>
<a href="{{ route('nias.index') }}" class="btn btn-light btn-sm">
<i class="bi bi-arrow-left me-1"></i>Kembali
</a>
</div>

<div class="card-body p-4">
<div class="alert alert-info small mb-4">
<i class="bi bi-info-circle me-2"></i>Fitur ini digunakan untuk memperbarui data atlet atau memperpanjang masa berlaku NIAS yang akan/sudah habis.
Data yang disubmit akan masuk ke daftar atlet dengan keterangan <strong>UPDATE / PERPANJANG</strong>.
</div>

@if($errors->any())
<div class="alert alert-danger small mb-3">
<i class="bi bi-exclamation-triangle me-2"></i><strong>Mohon perbaiki kesalahan berikut:</strong>
<ul class="mb-0 mt-1">
@foreach($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif

<form method="POST" action="{{ route('nias.store') }}" enctype="multipart/form-data">
@csrf
<input type="hidden" name="is_update" value="1">

<div class="row g-3">

{{-- 1. Tipe Update --}}
<div class="col-md-12">
<label class="form-label fw-bold">Tipe Update <span class="text-danger">*</span></label>
<select name="tipe_update" id="tipe_update" class="form-select border-primary" required>
<option value="perpanjangan">Perpanjangan (Club dan Domisili tidak berubah)</option>
<option value="update_club">Pindah Club</option>
<option value="update_domisili">Pindah Domisili</option>
<option value="update_all">Pindah Club dan Domisili</option>
</select>
</div>

{{-- No NIAS Jatim — wajib pilih dari data existing, tidak boleh manual --}}
<div class="col-md-4">
<label class="form-label">
    No NIAS Jatim <span class="text-danger">*</span>
</label>
<select name="NONIAS" id="NONIAS" class="form-select select2-nonias" required>
    <option value="">— Pilih No NIAS —</option>
    {{-- Grup: hanya club sendiri (dipakai saat perpanjangan / pindah domisili) --}}
    @foreach($existingNiasMyClub as $en)
        <option value="{{ $en->NONIAS }}"
            class="opt-myclub"
            data-nama="{{ $en->NAMA }}"
            data-gender="{{ $en->GENDER }}"
            data-tptlahir="{{ $en->TPTLAHIR }}"
            data-tgllahir="{{ $en->TGLLAHIR ? \Carbon\Carbon::parse($en->TGLLAHIR)->format('Y-m-d') : '' }}"
            {{ old('NONIAS') == $en->NONIAS ? 'selected' : '' }}>
            {{ $en->NONIAS }} — {{ $en->NAMA }}
        </option>
    @endforeach
    {{-- Grup: semua club lain (hanya tampil saat pindah club / pindah club & domisili) --}}
    @foreach($existingNias->whereNotIn('NONIAS', $existingNiasMyClub->pluck('NONIAS')) as $en)
        <option value="{{ $en->NONIAS }}"
            class="opt-otherclub"
            style="display:none"
            data-nama="{{ $en->NAMA }}"
            data-gender="{{ $en->GENDER }}"
            data-tptlahir="{{ $en->TPTLAHIR }}"
            data-tgllahir="{{ $en->TGLLAHIR ? \Carbon\Carbon::parse($en->TGLLAHIR)->format('Y-m-d') : '' }}"
            {{ old('NONIAS') == $en->NONIAS ? 'selected' : '' }}>
            {{ $en->NONIAS }} — {{ $en->NAMA }}
        </option>
    @endforeach
</select>
<div class="form-text">Pilih No NIAS dari data existing. Nama, gender, dan TTL akan terisi otomatis.</div>
</div>

{{-- Nama & TTL --}}
<div class="col-md-6">
<label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
<select name="NAMA" id="NAMA" class="form-select select2-nama" required>
    <option value="">— Pilih atau ketik nama —</option>
    {{-- Grup: hanya club sendiri --}}
    @foreach($existingNamesMyClub as $nm)
        <option value="{{ $nm }}" class="opt-myclub" {{ old('NAMA') == $nm ? 'selected' : '' }}>
            {{ $nm }}
        </option>
    @endforeach
    {{-- Grup: semua club lain (hanya tampil saat pindah club / pindah club & domisili) --}}
    @foreach(array_diff($existingNames, $existingNamesMyClub) as $nm)
        <option value="{{ $nm }}" class="opt-otherclub" style="display:none"
            {{ old('NAMA') == $nm ? 'selected' : '' }}>
            {{ $nm }}
        </option>
    @endforeach
    {{-- Jika old('NAMA') tidak ada di list sama sekali (ketik manual) --}}
    @if(old('NAMA') && !in_array(old('NAMA'), $existingNames))
        <option value="{{ old('NAMA') }}" selected>{{ old('NAMA') }}</option>
    @endif
</select>
<div class="form-text text-muted small">
    <i class="bi bi-info-circle me-1"></i>
    Pilih dari daftar atau ketik nama baru jika belum terdaftar.
</div>
</div>
<div class="col-md-3">
<label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
<input type="text" name="TEMPATLAHIR" class="form-control text-uppercase"
value="{{ old('TEMPATLAHIR') }}" required>
</div>
<div class="col-md-3">
<label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
<input type="date" name="TGLLAHIR" class="form-control"
value="{{ old('TGLLAHIR') }}" required>
</div>

{{-- Gender & Club --}}
<div class="col-md-3">
<label class="form-label">Gender <span class="text-danger">*</span></label>
<select name="GENDER" class="form-select" required>
<option value="L" {{ old('GENDER') === 'L' ? 'selected' : '' }}>Laki-laki</option>
<option value="P" {{ old('GENDER') === 'P' ? 'selected' : '' }}>Perempuan</option>
</select>
</div>
<div class="col-md-9">
<label class="form-label">Club Terkini</label>
<input type="text" name="NAMACLUB" class="form-control bg-light"
value="{{ $userClub }}" readonly>
<div class="form-text">Otomatis mengikuti club akun pelatih.</div>
</div>

{{-- Domisili KK — hanya tampil saat update_domisili / update_all --}}
<div class="col-12" id="wrapper_domisili" style="display:none;">
<div class="row g-3">
<div class="col-md-4">
<label class="form-label">
Jenis Wilayah (KK) <span class="text-danger">*</span>
</label>
<select name="JENISDOM" id="JENISDOM" class="form-select">
<option value="">— Pilih Jenis —</option>
<option value="KOTA" {{ old('JENISDOM') === 'KOTA' ? 'selected' : '' }}>KOTA</option>
<option value="KAB"  {{ old('JENISDOM') === 'KAB'  ? 'selected' : '' }}>KABUPATEN</option>
</select>
</div>
<div class="col-md-8">
<label class="form-label">
Nama Kota/Kab (KK) <span class="text-danger">*</span>
</label>
<select name="NAMAKOTADOM" id="NAMAKOTADOM" class="form-select select2">
<option value="">— Pilih Kota/Kab —</option>
@foreach($domisilis as $d)
<option value="{{ $d }}" {{ old('NAMAKOTADOM') === $d ? 'selected' : '' }}>
{{ $d }}
</option>
@endforeach
</select>
</div>
{{-- Radio: Mutasi dari luar Jatim --}}
<div class="col-12" id="wrapper_mutasi_luar_jatim">
<label class="form-label fw-semibold">
Mutasi dari luar Jawa Timur?
<span class="text-danger">*</span>
</label>
<div class="d-flex gap-4">
<div class="form-check">
<input class="form-check-input" type="radio" name="mutasi_luar_jatim"
id="mutasi_ya" value="ya"
{{ old('mutasi_luar_jatim') === 'ya' ? 'checked' : '' }} required>
<label class="form-check-label" for="mutasi_ya">Ya</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="mutasi_luar_jatim"
id="mutasi_tidak" value="tidak"
{{ old('mutasi_luar_jatim', 'tidak') === 'tidak' ? 'checked' : '' }}>
<label class="form-check-label" for="mutasi_tidak">Tidak</label>
</div>
</div>
</div>
</div>
</div>

{{-- Upload Dokumen --}}
<div class="col-12 mt-2">
<h6 class="border-bottom pb-2">
<i class="bi bi-file-earmark-arrow-up me-2"></i>Upload Dokumen Pendukung
</h6>
</div>

{{-- Foto (Wajib) --}}
<div class="col-md-4">
<label class="form-label">File Foto <span class="text-danger">*</span></label>
<input type="file" name="file_foto" class="form-control"
accept=".jpg,.jpeg,.png" required>
<div class="form-text">Format: JPG/PNG, Maks: 5MB</div>
</div>

{{-- Kartu Keluarga (conditional: update_domisili / update_all) --}}
<div class="col-md-4" id="wrapper_file_kk" style="display:none;">
<label class="form-label">File Kartu Keluarga <span class="text-danger">*</span></label>
<input type="file" name="file_kk" id="file_kk" class="form-control"
accept=".pdf,.jpg,.jpeg,.png">
<div class="form-text text-danger fw-bold">Wajib untuk update Domisili</div>
</div>

{{-- SK Mutasi (conditional: update_club / update_all) --}}
<div class="col-md-4" id="wrapper_file_sk_mutasi" style="display:none;">
<label class="form-label">File SK Mutasi <span class="text-danger">*</span></label>
<input type="file" name="file_sk_mutasi" id="file_sk_mutasi" class="form-control"
accept=".pdf,.jpg,.jpeg,.png">
<div class="form-text text-danger fw-bold">Wajib untuk update Club</div>
</div>

{{-- Estimasi Masa Berlaku --}}
<div class="col-12">
<div class="p-3 border rounded bg-light">
<div class="row align-items-center">
<div class="col-sm-6 text-muted small">
<i class="bi bi-calendar-check me-1"></i>Estimasi Masa Berlaku Baru:
</div>
<div class="col-sm-6 text-end">
<strong class="text-primary fs-5">{{ $expiredDate->format('d F Y') }}</strong>
<input type="hidden" name="EXPIRED"   value="{{ $expiredDate->format('Y-m-d') }}">
<input type="hidden" name="TGLDAFTAR" value="{{ now()->format('Y-m-d') }}">
</div>
</div>
</div>
</div>

</div>{{-- end .row --}}

<div class="d-flex justify-content-end gap-2 mt-4">
<a href="{{ route('nias.index') }}" class="btn btn-outline-secondary px-4">
<i class="bi bi-x-circle me-1"></i>Batal
</a>
<button type="submit" id="btn_submit" class="btn btn-primary px-4">
<i class="bi bi-arrow-repeat me-1"></i>Proses Update
</button>
</div>
</form>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    // Inisialisasi Select2
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // Club user yang sedang login (untuk filter)
    const userClub = '{{ $userClub }}';

    // ── Show/hide option berdasarkan tipe ────────────────────────
    // opt-myclub  : selalu tampil
    // opt-otherclub : tampil hanya saat update_club / update_all
    function filterDropdowns(filterByClub) {
        // Untuk NONIAS
        $('#NONIAS .opt-otherclub').each(function () {
            $(this).prop('disabled', filterByClub).toggle(!filterByClub);
        });
        // Untuk NAMA
        $('#NAMA .opt-otherclub').each(function () {
            $(this).prop('disabled', filterByClub).toggle(!filterByClub);
        });
    }

    // ── Inisialisasi Select2 NONIAS (sekali saja) ─────────────────
    function initNoniasSelect2() {
        if (!$('#NONIAS').hasClass('select2-hidden-accessible')) {
            $('#NONIAS').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '— Pilih No NIAS —',
                allowClear: true,
            });
            bindNoniasChange();
        }
    }

    // ── Inisialisasi Select2 NAMA (sekali saja) ───────────────────
    function initNamaSelect2() {
        if (!$('#NAMA').hasClass('select2-hidden-accessible')) {
            $('#NAMA').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '— Pilih atau ketik nama —',
                allowClear: true,
                tags: true,
                createTag: function (params) {
                    const term = $.trim(params.term).toUpperCase();
                    if (!term) return null;
                    return { id: term, text: term, newTag: true };
                },
                language: {
                    noResults: function () { return 'Ketik nama baru jika belum terdaftar'; }
                }
            });
        }
    }

    // ── Autofill NAMA, GENDER, TTL saat NONIAS dipilih ────────────
    function bindNoniasChange() {
        $('#NONIAS').off('change.autofill').on('change.autofill', function () {
        const selected = $(this).find('option:selected');
        if (!selected.val()) return;

        const nama     = selected.data('nama')     || '';
        const gender   = selected.data('gender')   || '';
        const tptlahir = selected.data('tptlahir') || '';
        const tgllahir = selected.data('tgllahir') || '';

        // Set NAMA di Select2
        const namaSelect = $('#NAMA');
        // Cek apakah option sudah ada
        if (namaSelect.find("option[value='" + nama + "']").length === 0) {
            namaSelect.append(new Option(nama, nama, true, true)).trigger('change');
        } else {
            namaSelect.val(nama).trigger('change');
        }

        // Set Gender — mapping Pa/Pi (nilai di tabel NIAS) ke L/P (nilai di form)
        const genderMapped = (['PA', 'L'].includes(gender.toUpperCase())) ? 'L' : 'P';
        $('select[name="GENDER"]').val(genderMapped).trigger('change');

        // Set Tempat Lahir
        $('input[name="TEMPATLAHIR"]').val(tptlahir.toUpperCase());

        // Set Tanggal Lahir
        $('input[name="TGLLAHIR"]').val(tgllahir);
        });
    }



    // Auto-uppercase saat mengetik di Select2 NAMA
    $('#NAMA').on('select2:open', function () {
        setTimeout(function () {
            $('.select2-search__field').on('input', function () {
                this.value = this.value.toUpperCase();
            });
        }, 100);
    });

    // Auto-uppercase untuk TEMPATLAHIR
    $('input[name="TEMPATLAHIR"]').on('input', function () {
        this.value = this.value.toUpperCase();
    });

    // ── Update semua field berdasarkan tipe ───────────────────────
    function applyTipe(tipe) {
        const domisiliRequired = (tipe === 'update_domisili' || tipe === 'update_all');
        const clubRequired     = (tipe === 'update_club'     || tipe === 'update_all');

        // Filter NONIAS & NAMA: hanya club sendiri saat perpanjangan / pindah domisili
        const filterByClub = (tipe === 'perpanjangan' || tipe === 'update_domisili');
        filterDropdowns(filterByClub);
        // Reset pilihan saat tipe berubah
        $('#NONIAS').val('').trigger('change');
        $('#NAMA').val('').trigger('change');

        // Blok domisili (jenis wilayah + kota/kab + radio mutasi luar jatim)
        if (domisiliRequired) {
            $('#wrapper_domisili').show();
            $('#JENISDOM, #NAMAKOTADOM').prop('required', true);
            $('input[name="mutasi_luar_jatim"]').prop('required', true);
        } else {
            $('#wrapper_domisili').hide();
            $('#JENISDOM').val('');
            $('#NAMAKOTADOM').val('').trigger('change');
            $('#JENISDOM, #NAMAKOTADOM').prop('required', false);
            $('input[name="mutasi_luar_jatim"]').prop('required', false).prop('checked', false);
            $('#mutasi_tidak').prop('checked', true); // default tidak
        }

        // File KK
        $('#wrapper_file_kk').toggle(domisiliRequired);
        $('#file_kk').prop('required', domisiliRequired).val('');

        // File SK Mutasi
        $('#wrapper_file_sk_mutasi').toggle(clubRequired);
        $('#file_sk_mutasi').prop('required', clubRequired).val('');
    }

    // Inisialisasi Select2 (sekali saja)
    initNoniasSelect2();
    initNamaSelect2();
    bindNoniasChange();

    // Jalankan saat load
    applyTipe($('#tipe_update').val());

    // Jalankan saat dropdown berubah
    $('#tipe_update').on('change', function () {
        applyTipe($(this).val());
    });

    // ── Konfirmasi SweetAlert sebelum submit ──────────────────────
    $('form').on('submit', function (e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Konfirmasi Update',
            text: 'Apakah data yang diisi sudah benar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Cek Lagi',
        }).then((result) => {
            if (result.isConfirmed) {
                $('#btn_submit').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...'
                );
                form.submit();
            }
        });
    });
});
</script>

@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        title: 'Berhasil!',
        text: '{{ addslashes(session("success")) }}',
              icon: 'success',
              confirmButtonColor: '#0d6efd',
              confirmButtonText: 'OK',
    });
});
</script>
@endif

@if(session('error'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        title: 'Gagal!',
        text: '{{ addslashes(session("error")) }}',
              icon: 'error',
              confirmButtonColor: '#d33',
              confirmButtonText: 'Tutup',
    });
});
</script>
@endif
@endpush
