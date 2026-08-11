@extends('layouts.app')
@section('title', 'Edit Akun Lomba — Admin')

@section('content')
<div class="card page-card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Akun Lomba</h5>
    <a href="{{ route('settings', ['tab' => 'lomba']) }}" class="btn btn-success btn-sm fw-semibold">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>
<div class="card-body p-4">
    <div class="row justify-content-center">
        <div class="col-md-7">

            @php
                $k = optional($lombaUser->kontingen);
                $jnsLabels = ['K' => 'Antar Kota', 'P' => 'Antar Provinsi', 'C' => 'Antar Club'];
                $kompetisiLabel = $jnsLabels[$jnsKompetisi] ?? $jnsKompetisi;
            @endphp

            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                Mengedit akun <strong>{{ $lombaUser->email }}</strong>.
                Kompetisi aktif: <strong>{{ $kompetisiLabel }}</strong>.
            </div>

            <form method="POST" action="{{ route('settings.lomba.update', $lombaUser->id) }}">
                @csrf
                @method('PUT')

                {{-- Data Akun --}}
                <div class="bg-light p-3 rounded mb-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person me-1"></i>Data Akun</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama</label>
                            <input type="text" name="nama" class="form-control"
                                   value="{{ old('nama', $lombaUser->nama) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. WhatsApp</label>
                            <input type="text" name="no_wa" class="form-control"
                                   value="{{ old('no_wa', $lombaUser->no_wa) }}">
                        </div>
                    </div>
                </div>

                {{-- Data Kontingen — based on current kompetisi --}}
                <div class="bg-light p-3 rounded mb-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-people me-1"></i>Data Kontingen ({{ $kompetisiLabel }})</h6>

                    @if($jnsKompetisi === 'K')
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kota / Kabupaten <span class="text-danger">*</span></label>
                        @php
                            $currentKota = '';
                            if ($k && $k->jenis_wilayah && $k->nama_wilayah) {
                                $currentKota = $k->jenis_wilayah . ' ' . $k->nama_wilayah;
                            }
                        @endphp
                        <input type="text" name="kota_kab" class="form-control" list="kotaList"
                               value="{{ old('kota_kab', $currentKota) }}" required autocomplete="off">
                        <datalist id="kotaList">
                            @foreach($listKota as $kota)
                                <option value="{{ $kota->label }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Provinsi</label>
                        <input type="text" name="provinsi" class="form-control"
                               value="{{ old('provinsi', $k->provinsi ?? 'JAWA TIMUR') }}">
                    </div>
                    @elseif($jnsKompetisi === 'C')
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Club <span class="text-danger">*</span></label>
                        <select name="nama_kontingen" class="form-select" required>
                            <option value="">— Pilih Club —</option>
                            @foreach($clubList as $club)
                                <option value="{{ $club }}"
                                    {{ old('nama_kontingen', $k->nama_kontingen ?? '') == $club ? 'selected' : '' }}>
                                    {{ $club }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @elseif($jnsKompetisi === 'P')
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kontingen <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kontingen" class="form-control"
                               value="{{ old('nama_kontingen', $k->nama_kontingen ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Provinsi <span class="text-danger">*</span></label>
                        <select name="provinsi" class="form-select" required>
                            <option value="">— Pilih Provinsi —</option>
                            @foreach($provinsiList as $prov)
                                <option value="{{ $prov }}"
                                    {{ old('provinsi', $k->provinsi ?? 'JAWA TIMUR') == $prov ? 'selected' : '' }}>
                                    {{ $prov }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Current kontingen info --}}
                    @if($k && $k->exists)
                    <div class="small text-muted mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Saat ini: <strong>{{ $k->nama_kontingen }}</strong>
                        @if($k->jenis_wilayah && $k->nama_wilayah)
                            ({{ $k->jenis_wilayah }} {{ $k->nama_wilayah }})
                        @endif
                        @if($k->provinsi)
                            — {{ $k->provinsi }}
                        @endif
                    </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary w-100" id="btnSave">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>
</div>

<script>
document.querySelector('form')?.addEventListener('submit', function() {
    document.getElementById('btnSave').disabled = true;
    document.getElementById('btnSave').innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
});
</script>
@endsection
