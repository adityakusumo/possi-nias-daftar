@extends('layouts.app')
@section('title','Edit Data NIAS')

@section('content')
<div class="card page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-pencil-square me-2"></i>Edit Data NIAS
            <span class="fw-normal opacity-75 ms-1">— {{ $nias->NAMA }}</span>
        </h5>
        <a href="{{ route('nias.show', $nias) }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card-body p-4">
        <form method="POST" action="{{ route('nias.update', $nias) }}"
              enctype="multipart/form-data" autocomplete="off">
            @csrf
            @method('PUT')
            @include('nias._form')

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('nias.show', $nias) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </a>
                <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#NAMAKOTADOM').select2({
        theme: 'bootstrap-5',
        placeholder: '— Pilih Kota / Kabupaten —',
        allowClear: true,
        width: '100%',
    });

    $('input[name="NAMA"], input[name="TEMPATLAHIR"]').on('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    $('input[type="file"]').on('change', function () {
        const file = this.files[0];
        if (file) {
            const mb = (file.size / 1024 / 1024).toFixed(2);
            $(this).next('.file-info').remove();
            $(this).after(`<div class="file-info form-text text-success small mt-1">
                <i class="bi bi-file-earmark-check me-1"></i>${file.name} (${mb} MB)
            </div>`);
        }
    });
});
</script>
@endpush
