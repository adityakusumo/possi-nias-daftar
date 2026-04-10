@extends('layouts.app')
@section('title', 'Detail NIAS — ' . $nias->NAMA)

@section('content')
<div class="card page-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Detail Anggota NIAS</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('nias.edit', $nias) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <a href="{{ route('nias.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="card-body p-4">

        {{-- Header: avatar + name + badges --}}
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3"
             style="background:linear-gradient(135deg,#e8f0fb 0%,#fff 100%)">
            <div class="rounded-circle d-flex align-items-center justify-content-center
                        text-white fw-bold fs-2 flex-shrink-0"
                 style="width:68px;height:68px;background:#003d8f;letter-spacing:-1px">
                {{ strtoupper(substr($nias->NAMA, 0, 1)) }}
            </div>
            <div>
                <h4 class="mb-1 fw-bold">{{ $nias->NAMA }}</h4>
                <div class="d-flex flex-wrap gap-2">
                    @if($nias->NONIAS)
                        <code class="bg-white border px-2 py-1 rounded small">{{ $nias->NONIAS }}</code>
                    @else
                        <span class="text-muted small fst-italic">No. NIAS belum ditetapkan</span>
                    @endif

                    @if($nias->GENDER === 'L')
                        <span class="badge bg-primary">Laki-laki</span>
                    @else
                        <span class="badge bg-danger">Perempuan</span>
                    @endif

                    @if($nias->STATUS == 1 && !$nias->EXPIRED?->isPast())
                        <span class="badge bg-success">AKTIF</span>
                    @else
                        <span class="badge bg-secondary">EXPIRED / NON-AKTIF</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">

            {{-- Data Pribadi --}}
            <div class="col-md-6">
                <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 small text-uppercase">
                    <i class="bi bi-person-badge me-1"></i>Data Pribadi
                </h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="text-muted pe-3" style="width:42%">Tempat / Tgl Lahir</td>
                        <td>
                            <strong>{{ $nias->TEMPATLAHIR }}</strong>,
                            {{ $nias->TGLLAHIR?->format('d/m/Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">NIK</td>
                        <td>{{ $nias->NIK ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>{{ $nias->EMAIL ?: '—' }}</td>
                    </tr>
                </table>
            </div>

            {{-- Data Klub --}}
            <div class="col-md-6">
                <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 small text-uppercase">
                    <i class="bi bi-people me-1"></i>Data Klub
                </h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="text-muted pe-3" style="width:42%">Nama Klub</td>
                        <td><strong>{{ $nias->NAMACLUB }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kode Klub</td>
                        <td>{{ $nias->KDCLUB ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kota Klub</td>
                        <td>{{ $nias->JENIS }} {{ $nias->NAMAKOTA }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kode Kota Klub</td>
                        <td>{{ $nias->KDKOTA ?: '—' }}</td>
                    </tr>
                </table>
            </div>

            {{-- Domisili --}}
            <div class="col-md-6">
                <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 small text-uppercase">
                    <i class="bi bi-geo-alt me-1"></i>Domisili
                </h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="text-muted pe-3" style="width:42%">Provinsi</td>
                        <td>{{ $nias->NAMAPROPDOM }} ({{ $nias->KDPROPDOM }})</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kota / Kab</td>
                        <td><strong>{{ $nias->JENISDOM }} {{ $nias->NAMAKOTADOM }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kode Kota Dom</td>
                        <td>{{ $nias->KDKOTADOM ?: '—' }}</td>
                    </tr>
                </table>
            </div>

            {{-- Status Keanggotaan --}}
            <div class="col-md-6">
                <h6 class="fw-bold text-primary border-bottom pb-1 mb-3 small text-uppercase">
                    <i class="bi bi-calendar-check me-1"></i>Status Keanggotaan
                </h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="text-muted pe-3" style="width:42%">Tanggal Daftar</td>
                        <td>{{ $nias->TGLDAFTAR?->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Masa Berlaku s/d</td>
                        <td>
                            <span class="{{ $nias->EXPIRED?->isPast() ? 'text-danger fw-bold' : 'text-success fw-bold' }}">
                                {{ $nias->EXPIRED?->format('d/m/Y') }}
                            </span>
                            @if(!$nias->EXPIRED?->isPast())
                                <span class="text-muted ms-1">({{ $nias->EXPIRED?->diffForHumans() }})</span>
                            @else
                                <span class="badge bg-danger ms-1">EXPIRED</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Last Mutasi</td>
                        <td>{{ $nias->LASTMUTASI ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat</td>
                        <td>{{ $nias->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>

        </div>

        {{-- Danger zone --}}
        <div class="d-flex justify-content-end border-top pt-3 mt-2">
            <form method="POST" action="{{ route('nias.destroy', $nias) }}"
                  onsubmit="return confirm('Hapus data {{ addslashes($nias->NAMA) }} secara permanen?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Data Ini
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
