@extends('layouts.app')
@section('title', 'Data NIAS')

@section('content')
@php $isNiasOpen = $isNiasOpen ?? \App\Models\AppSetting::isNiasOpen(); @endphp

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB NAVIGATION
════════════════════════════════════════════════════════════════════════ --}}
<ul class="nav nav-tabs mb-3" id="niasTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="tab-semua" data-bs-toggle="tab"
                data-bs-target="#panel-semua" type="button" role="tab" aria-controls="panel-semua" aria-selected="true">
            <i class="bi bi-table me-1"></i>Semua
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-baru" data-bs-toggle="tab"
                data-bs-target="#panel-baru" type="button" role="tab" aria-controls="panel-baru" aria-selected="false">
            <i class="bi bi-person-plus me-1"></i>Daftar NIAS Baru
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-update" data-bs-toggle="tab"
                data-bs-target="#panel-update" type="button" role="tab" aria-controls="panel-update" aria-selected="false">
            <i class="bi bi-arrow-repeat me-1"></i>Update NIAS
        </button>
    </li>
</ul>

<div class="tab-content" id="niasTabsContent">

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB 1: SEMUA (Data Pendaftaran)
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="panel-semua" role="tabpanel" aria-labelledby="tab-semua">

    <div class="card page-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Pendaftaran Nias Baru/Update</h5>
            <div class="d-flex gap-2 flex-wrap">
                {{-- Admin: tombol Pengaturan --}}
                @if(Auth::user()->role === 'admin')
                <a href="{{ route('settings') }}" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-gear me-1"></i>Pengaturan
                </a>
                @endif
                <a href="{{ route('nias.existing') }}" class="btn btn-secondary btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-people me-1"></i>NIAS Jatim yang sudah terdaftar
                </a>
                <a href="{{ route('nias.tutorial') }}" class="btn btn-outline-info btn-sm fw-semibold shadow-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Tutorial Pengoperasian
                </a>
            </div>
        </div>

        <div class="card-body p-3">

            {{-- Search bar --}}
            <form method="GET" action="{{ route('nias.index') }}" class="row g-2 mb-3">
                @if(request('jenis'))
                    <input type="hidden" name="jenis" value="{{ request('jenis') }}">
                @endif
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="Cari nama, No. NIAS, atau klub…" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('search') || request('jenis'))
                        <a href="{{ route('nias.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>

            {{-- Filter buttons (inline, bukan tab) --}}
            <div class="btn-group btn-group-sm mb-3" role="group" aria-label="Filter jenis pendaftaran">
                <a href="{{ route('nias.index', request()->only('search')) }}"
                   class="btn {{ !request('jenis') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Semua <span class="badge bg-light text-dark ms-1">{{ $totalSemua }}</span>
                </a>
                <a href="{{ route('nias.index', array_merge(request()->only('search'), ['jenis' => 'baru'])) }}"
                   class="btn {{ request('jenis') === 'baru' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-person-plus me-1"></i>Baru
                    <span class="badge bg-warning text-dark ms-1">{{ $totalBaru }}</span>
                </a>
                <a href="{{ route('nias.index', array_merge(request()->only('search'), ['jenis' => 'update'])) }}"
                   class="btn {{ request('jenis') === 'update' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-arrow-repeat me-1"></i>Update
                    <span class="badge bg-info text-dark ms-1">{{ $totalUpdate }}</span>
                </a>
            </div>

            {{-- Alert jadwal tutup untuk user regular --}}
            @if(!$isNiasOpen && Auth::user()->role !== 'admin')
            <div class="alert alert-warning small mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-lock-fill fs-5 text-warning"></i>
                <div>
                    <strong>Masa pendaftaran NIAS sedang ditutup.</strong>
                    Anda hanya dapat melihat data yang sudah ada dan mengakses data NIAS existing.
                    Untuk informasi jadwal pembukaan, silakan hubungi admin.
                </div>
            </div>
            @endif

            {{-- Toolbar bulk action --}}
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox" id="chk_all" class="form-check-input mt-0" title="Pilih semua">
                    <span id="selected_count" class="text-muted small">0 dipilih</span>
                    <button type="button" id="btn_delete_selected" class="btn btn-sm btn-outline-danger d-none"
                        onclick="confirmDeleteSelected()">
                        <i class="bi bi-trash me-1"></i>Hapus Dipilih
                    </button>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteAll()">
                    <i class="bi bi-trash3 me-1"></i>Hapus Semua
                </button>
            </div>

            {{-- Form untuk delete selected --}}
            <form id="form_delete_selected" method="POST" action="{{ route('nias.destroy-selected') }}"
                style="display:none;">
                @csrf @method('DELETE')
            </form>

            {{-- Form untuk delete all --}}
            <form id="form_delete_all" method="POST" action="{{ route('nias.destroy-all') }}" style="display:none;">
                @csrf @method('DELETE')
            </form>

            <div class="table-responsive">
                <table class="table table-nias table-bordered table-sm align-middle mb-2" id="tbl_nias">
                    <thead>
                        <tr>
                            <th style="width:36px"></th>{{-- checkbox col --}}
                            <th>#</th>
                            <th>No. NIAS</th>
                            <th>Nama</th>
                            <th>L/P</th>
                            <th>Tgl Lahir</th>
                            <th>Klub</th>
                            <th>Kota / Kab Domisili</th>
                            <th>Tgl Daftar</th>
                            <th>Tgl Update</th>
                            <th>Expired</th>
                            <th>Status</th>
                            <th class="text-end">Biaya</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $r)
                            <tr class="{{ $r->is_update ? 'table-info' : '' }}" data-id="{{ $r->ID }}">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input chk_row mt-0" value="{{ $r->ID }}">
                                </td>
                                <td class="text-muted small">{{ $records->firstItem() + $loop->index }}</td>
                                <td><code class="small">{{ $r->NONIAS ?? '—' }}</code></td>
                                <td class="fw-semibold">
                                    {{ $r->NAMA }}
                                    @if($r->is_update)
                                        <span class="badge bg-info text-dark ms-1" title="Perpanjangan / Update NIAS">
                                            <i class="bi bi-arrow-repeat"></i> UPDATE
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->GENDER === 'L')
                                        <span class="badge bg-primary">L</span>
                                    @else
                                        <span class="badge bg-danger">P</span>
                                    @endif
                                </td>
                                <td class="small">{{ $r->TGLLAHIR?->format('d/m/Y') }}</td>
                                <td class="small">{{ $r->NAMACLUB }}</td>
                                <td class="small">
                                    <span class="text-muted">{{ $r->JENISDOM }}</span>
                                    {{ $r->NAMAKOTADOM }}
                                </td>
                                <td class="small">{{ $r->TGLDAFTAR?->format('d/m/Y') ?? '—' }}</td>
                                <td class="small">
                                    @if($r->is_update && $r->TGLDAFTAR_UPDATE)
                                        <span class="text-info fw-semibold">
                                            {{ $r->TGLDAFTAR_UPDATE->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small {{ $r->EXPIRED?->isPast() ? 'text-danger fw-semibold' : '' }}">
                                    {{ $r->EXPIRED?->format('d/m/Y') }}
                                </td>
                                <td>
                                    @if(!$r->is_update && !$r->EXPIRED)
                                        <span class="badge bg-primary">DAFTAR BARU</span>
                                    @elseif($r->is_update && $r->EXPIRED && $r->EXPIRED->isPast())
                                        <span class="badge badge-expired">EXPIRED</span>
                                    @elseif($r->is_update && $r->EXPIRED && !$r->EXPIRED->isPast())
                                        <span class="badge badge-aktif">AKTIF</span>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                                <td class="text-end small fw-semibold text-success">
                                    @php $biaya = $r->is_update ? ($tarifNias['update'] ?? 30000) : ($tarifNias['baru'] ?? 60000); @endphp
                                    Rp {{ number_format($biaya, 0, ',', '.') }}
                                </td>
                                <td class="text-center" style="white-space:nowrap">
                                    <a href="{{ route('nias.show', $r) }}" class="btn btn-sm btn-outline-primary py-0"
                                        title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('nias.edit', $r) }}" class="btn btn-sm btn-outline-warning py-0"
                                        title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0" title="Hapus"
                                        onclick="confirmDeleteOne('{{ addslashes($r->NAMA) }}', '{{ route('nias.destroy', $r) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    Belum ada data NIAS terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Hidden form untuk delete satu data (SweetAlert) --}}
            <form id="form_delete_one" method="POST" style="display:none;">
                @csrf @method('DELETE')
            </form>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    @if($records->total())
                        Menampilkan {{ $records->firstItem() }}–{{ $records->lastItem() }}
                        dari <strong>{{ $records->total() }}</strong> data
                    @endif
                </small>
                {{ $records->links() }}
            </div>

        </div>
    </div>

    {{-- Total Biaya + Bukti Transfer + Tombol Kirim + Export CSV --}}
    @php
        $allPending      = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->get();
        $totalBiayaSemua = $allPending->sum(fn($r) => $r->is_update ? ($tarifNias['update'] ?? 30000) : ($tarifNias['baru'] ?? 60000));
        $jmlBaru         = $allPending->where('is_update', false)->count();
        $jmlUpdate       = $allPending->where('is_update', true)->count();
    @endphp
    <div class="d-flex flex-column align-items-end gap-3 mt-2 mb-4">

        {{-- Total Biaya --}}
        <div class="card border-success" style="min-width:300px">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Total Biaya Pendaftaran</span>
                <span class="fw-bold text-success fs-5">Rp {{ number_format($totalBiayaSemua, 0, ',', '.') }}</span>
            </div>
            <div class="card-footer py-1 px-3 bg-light small text-muted">
                {{ $jmlBaru }} atlet baru + {{ $jmlUpdate }} atlet update
            </div>
        </div>

        {{-- Upload Bukti Transfer --}}
        <div class="card shadow-sm" style="min-width:300px">
            <div class="card-header py-2 px-3 small fw-bold">
                <i class="bi bi-receipt me-1"></i>Bukti Transfer
                @if($hasBukti)
                    <span class="badge bg-success ms-1">Sudah diupload</span>
                @else
                    <span class="badge bg-warning text-dark ms-1">Belum diupload</span>
                @endif
            </div>
            <div class="card-body py-2 px-3">
                <div class="alert alert-info py-2 px-3 small mb-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Transfer biaya pendaftaran ke no rekening BRI: <strong>210101009705502</strong>
                    atas nama: <strong>ahmad fauzi</strong>
                </div>
                <form method="POST" action="{{ route('nias.bukti-transfer') }}" enctype="multipart/form-data"
                      class="d-flex gap-2 align-items-center justify-content-end flex-wrap">
                    @csrf
                    <input type="file" name="bukti_transfer" class="form-control form-control-sm"
                           accept=".pdf,.jpg,.jpeg,.png" style="max-width:200px">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-upload me-1"></i>Upload
                    </button>
                    @if($hasBukti)
                    <a href="{{ route('nias.serve-bukti', Auth::id()) }}" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>Lihat
                    </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Tombol Kirim Email --}}
        <div style="min-width:300px">
            @if(!$hasBukti)
            <div class="alert alert-warning small py-1 px-2 mb-1 text-end">
                <i class="bi bi-exclamation-triangle me-1"></i>Upload bukti transfer dulu sebelum mengirim.
            </div>
            @endif
            <button type="button"
                    {{ !$hasBukti ? 'disabled' : '' }}
                    class="btn btn-primary w-100 {{ !$hasBukti ? 'disabled' : '' }}"
                    data-bs-toggle="modal" data-bs-target="#modalKirimEmail">
                <i class="bi bi-envelope-arrow-up me-1"></i>Kirim ke POSSI Jatim
            </button>
        </div>

        {{-- Tombol Export CSV --}}
        <div style="min-width:300px">
            <a href="{{ route('nias.export') }}" class="btn btn-outline-success w-100">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
        </div>

    </div>

    {{-- SECTION 2: DATA YANG SUDAH DIKIRIM --}}
    @if($sentRecords->total() > 0)
        <div class="card page-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle me-2"></i>List Pendaftaran yang Sudah Dikirim
                </h5>
            </div>

            <div class="card-body p-3">
                <div class="alert alert-success small mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Data berikut sudah dikirim ke POSSI Jatim.
                    @if(Auth::user()->role !== 'admin')
                        Data tidak dapat diedit atau dihapus.
                    @endif
                </div>

                {{-- Toolbar bulk action sent — admin only --}}
                @if(Auth::user()->role === 'admin')
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" id="chk_sent_all" class="form-check-input mt-0" title="Pilih semua">
                        <span id="sent_selected_count" class="text-muted small">0 dipilih</span>
                        <button type="button" id="btn_delete_sent_selected"
                                class="btn btn-sm btn-outline-danger d-none"
                                onclick="confirmDeleteSentSelected()">
                            <i class="bi bi-trash me-1"></i>Hapus Dipilih
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="confirmDeleteSentAll()">
                        <i class="bi bi-trash3 me-1"></i>Hapus Semua Terkirim
                    </button>
                </div>
                <form id="form_delete_sent_selected" method="POST"
                      action="{{ route('nias.destroy-sent-selected') }}" style="display:none;">
                    @csrf @method('DELETE')
                </form>
                <form id="form_delete_sent_all" method="POST"
                      action="{{ route('nias.destroy-sent-all') }}" style="display:none;">
                    @csrf @method('DELETE')
                </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-2">
                        <thead class="table-success">
                            <tr>
                                @if(Auth::user()->role === 'admin')
                                <th style="width:36px"></th>
                                @endif
                                <th>#</th>
                                <th>No. NIAS</th>
                                <th>Nama</th>
                                <th>L/P</th>
                                <th>Tgl Lahir</th>
                                <th>Klub</th>
                                <th>Kota / Kab Domisili</th>
                                <th>Tgl Dikirim</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sentRecords as $r)
                                <tr class="table-light">
                                    @if(Auth::user()->role === 'admin')
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input chk_sent_row mt-0"
                                               value="{{ $r->ID }}">
                                    </td>
                                    @endif
                                    <td class="text-muted small">{{ $sentRecords->firstItem() + $loop->index }}</td>
                                    <td><code class="small">{{ $r->NONIAS ?? '—' }}</code></td>
                                    <td class="fw-semibold">
                                        {{ $r->NAMA }}
                                        @if($r->is_update)
                                            <span class="badge bg-info text-dark ms-1">
                                                <i class="bi bi-arrow-repeat"></i> UPDATE
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->GENDER === 'L')
                                            <span class="badge bg-primary">L</span>
                                        @else
                                            <span class="badge bg-danger">P</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $r->TGLLAHIR?->format('d/m/Y') }}</td>
                                    <td class="small">{{ $r->NAMACLUB }}</td>
                                    <td class="small">
                                        <span class="text-muted">{{ $r->JENISDOM }}</span>
                                        {{ $r->NAMAKOTADOM }}
                                    </td>
                                    <td class="small text-success fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i>
                                        {{ $r->sent_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="bi bi-send-check"></i> TERKIRIM
                                        </span>
                                    </td>
                                    <td class="text-center" style="white-space:nowrap">
                                        <a href="{{ route('nias.show', $r) }}" class="btn btn-sm btn-outline-primary py-0"
                                            title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(Auth::user()->role === 'admin')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger py-0"
                                                title="Hapus"
                                                onclick="confirmDeleteSentOne('{{ addslashes($r->NAMA) }}', '{{ route('nias.destroy', $r) }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination untuk sent records --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Menampilkan {{ $sentRecords->firstItem() }}–{{ $sentRecords->lastItem() }}
                        dari <strong>{{ $sentRecords->total() }}</strong> data terkirim
                    </small>
                    {{ $sentRecords->links() }}
                </div>

            </div>
        </div>
    @endif

    {{-- Modal Kirim Email --}}
    <div class="modal fade" id="modalKirimEmail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('nias.send-email') }}">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-envelope-arrow-up me-2"></i>Kirim Data ke POSSI Jatim
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $jmlBaru = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->where('is_update', false)->count();
                            $jmlUpdate = \App\Models\Nias::where('user_id', Auth::id())->where('is_sent', false)->where('is_update', true)->count();
                        @endphp
                        <div class="alert alert-light border small mb-3">
                            <table class="w-100">
                                <tr>
                                    <td class="text-muted" style="width:130px">Club</td>
                                    <td>: <strong>{{ Auth::user()->namaclub }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email Pelatih</td>
                                    <td>: {{ Auth::user()->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tujuan Email</td>
                                    <td>: <strong>it.possijatim@gmail.com</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Data</td>
                                    <td>:
                                        <span class="badge bg-warning text-dark">Baru: {{ $jmlBaru }}</span>
                                        <span class="badge bg-info text-dark ms-1">Update: {{ $jmlUpdate }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Keterangan <span class="text-muted small">(opsional)</span>
                            </label>
                            <textarea name="keterangan" class="form-control" rows="4" maxlength="1000"
                                placeholder="Contoh: Mohon segera diproses, ada 2 atlet yang expired bulan ini..."></textarea>
                            <div class="form-text text-end">
                                <span id="char_count">0</span>/1000 karakter
                            </div>
                        </div>
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            File ZIP berisi seluruh data (<strong>{{ $jmlBaru + $jmlUpdate }}</strong> atlet)
                            beserta dokumen pendukung akan dilampirkan ke email.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btn_kirim" class="btn btn-primary" onclick="konfirmasiKirim(event)">
                            <i class="bi bi-send me-1"></i>Kirim Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>{{-- /panel-semua --}}

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB 2: DAFTAR NIAS BARU (Create Form)
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="panel-baru" role="tabpanel" aria-labelledby="tab-baru">
    <div class="card page-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Formulir Pendaftaran NIAS</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('nias.store') }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                @include('nias._form')

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('nias.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i>Simpan Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>{{-- /panel-baru --}}

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB 3: UPDATE NIAS (Update Form)
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="panel-update" role="tabpanel" aria-labelledby="tab-update">
    <div class="card page-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Form Update / Perpanjang NIAS</h5>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info small mb-4">
                <i class="bi bi-info-circle me-2"></i>Fitur ini digunakan untuk memperbarui data atlet atau memperpanjang
                masa berlaku NIAS yang akan/sudah habis.
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
                            <option value="" selected disabled>— Pilih Tipe Update —</option>
                            <option value="perpanjangan">Perpanjangan (Club dan Domisili tidak berubah)</option>
                            <option value="update_club">Pindah Club</option>
                            <option value="update_domisili">Pindah Domisili (KK)</option>
                            <option value="update_all">Pindah Club dan Domisili</option>
                        </select>
                    </div>

                    {{-- No NIAS Jatim --}}
                    <div class="col-md-4">
                        <label class="form-label">
                            No NIAS Jatim <span class="text-danger">*</span>
                        </label>
                        <select name="NONIAS" id="NONIAS" class="form-select select2-nonias" required>
                            <option value="">— Pilih No NIAS —</option>
                            @foreach($existingNiasMyClub as $en)
                                <option value="{{ $en->NONIAS }}" class="opt-myclub" data-nama="{{ $en->NAMA }}"
                                    data-gender="{{ $en->GENDER }}" data-tptlahir="{{ $en->TPTLAHIR }}"
                                    data-tgllahir="{{ $en->TGLLAHIR ? \Carbon\Carbon::parse($en->TGLLAHIR)->format('Y-m-d') : '' }}"
                                    {{ old('NONIAS') == $en->NONIAS ? 'selected' : '' }}>
                                    {{ $en->NONIAS }} — {{ $en->NAMA }}
                                </option>
                            @endforeach
                            @foreach($existingNias->whereNotIn('NONIAS', $existingNiasMyClub->pluck('NONIAS')) as $en)
                                <option value="{{ $en->NONIAS }}" class="opt-otherclub" style="display:none"
                                    data-nama="{{ $en->NAMA }}" data-gender="{{ $en->GENDER }}"
                                    data-tptlahir="{{ $en->TPTLAHIR }}"
                                    data-tgllahir="{{ $en->TGLLAHIR ? \Carbon\Carbon::parse($en->TGLLAHIR)->format('Y-m-d') : '' }}"
                                    {{ old('NONIAS') == $en->NONIAS ? 'selected' : '' }}>
                                    {{ $en->NONIAS }} — {{ $en->NAMA }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Pilih No NIAS dari data existing.</div>
                    </div>

                    {{-- Nama --}}
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <select name="NAMA" id="NAMA" class="form-select select2-nama" required>
                            <option value="">— Pilih atau ketik nama —</option>
                            @foreach($existingNamesMyClub as $nm)
                                <option value="{{ $nm }}" class="opt-myclub" {{ old('NAMA') == $nm ? 'selected' : '' }}>
                                    {{ $nm }}
                                </option>
                            @endforeach
                            @foreach(array_diff($existingNames, $existingNamesMyClub) as $nm)
                                <option value="{{ $nm }}" class="opt-otherclub" style="display:none" {{ old('NAMA') == $nm ? 'selected' : '' }}>
                                    {{ $nm }}
                                </option>
                            @endforeach
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
                        <input type="date" name="TGLLAHIR" class="form-control" value="{{ old('TGLLAHIR') }}" required>
                    </div>

                    {{-- Gender & Club --}}
                    <div class="col-md-3">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="GENDER" class="form-select" required>
                            <option value="L" {{ old('GENDER') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ old('GENDER') === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Club Terkini</label>
                        <input type="text" name="NAMACLUB" class="form-control bg-light" value="{{ $userClub }}" readonly>
                        <div class="form-text">Otomatis mengikuti club akun pelatih.</div>
                    </div>

                    {{-- Domisili KK --}}
                    <div class="col-12" id="wrapper_domisili" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" id="label_jenisdom">
                                    Jenis Wilayah (KK) <span class="text-danger">*</span>
                                </label>
                                <select name="JENISDOM" id="JENISDOM" class="form-select">
                                    <option value="">— Pilih Jenis —</option>
                                    <option value="KOTA" {{ old('JENISDOM') === 'KOTA' ? 'selected' : '' }}>KOTA</option>
                                    <option value="KAB" {{ old('JENISDOM') === 'KAB' ? 'selected' : '' }}>KABUPATEN</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" id="label_kotadom">
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
                            <div class="col-12" id="wrapper_mutasi_luar_jatim">
                                <label class="form-label fw-semibold">
                                    Mutasi dari luar Jawa Timur?
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mutasi_luar_jatim" id="mutasi_ya"
                                            value="ya" {{ old('mutasi_luar_jatim') === 'ya' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="mutasi_ya">Ya</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mutasi_luar_jatim"
                                            id="mutasi_tidak" value="tidak" {{ old('mutasi_luar_jatim', 'tidak') === 'tidak' ? 'checked' : '' }}>
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

                    <div class="col-md-4">
                        <label class="form-label">File Foto <span class="text-danger">*</span></label>
                        <input type="file" name="file_foto" class="form-control" accept=".jpg,.jpeg,.png" required>
                        <div class="form-text">Format: JPG/PNG, Maks: 5MB</div>
                    </div>
                    <div class="col-md-4" id="wrapper_file_kk" style="display:none;">
                        <label class="form-label">File Kartu Keluarga <span class="text-danger">*</span></label>
                        <input type="file" name="file_kk" id="file_kk" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text text-danger fw-bold">Wajib untuk update Domisili</div>
                    </div>
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
                                    <input type="hidden" name="EXPIRED" value="{{ $expiredDate->format('Y-m-d') }}">
                                    <input type="hidden" name="TGLDAFTAR" value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- end .row --}}

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary px-4" onclick="switchTab('tab-semua')">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" id="btn_submit_update" class="btn btn-primary px-4">
                        <i class="bi bi-arrow-repeat me-1"></i>Simpan Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>{{-- /panel-update --}}

</div>{{-- /tab-content --}}
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ── Helper: switch tab by id ──────────────────────────────────
        function switchTab(tabId) {
            const tab = document.getElementById(tabId);
            if (tab) {
                const trigger = new bootstrap.Tab(tab);
                trigger.show();
            }
        }

        // ── Filter Nama Kota/Kab berdasarkan Jenis Wilayah (KK) ──────
        let jenisDomFilter = '';
        function makeKotaDomMatcher() {
            return function (params, data) {
                if (jenisDomFilter) {
                    const v = String(data.id || '').toUpperCase();
                    const match = jenisDomFilter === 'KOTA'
                        ? v.indexOf('KOTA') > -1
                        : v.indexOf('KAB.') > -1;
                    if (!match) return null;
                }
                if (!params.term || params.term.trim() === '') return data;
                if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) return data;
                return null;
            };
        }
        function initKotaDomSelect2() {
            const $kd = $('#panel-update #NAMAKOTADOM');
            if ($kd.hasClass('select2-hidden-accessible')) $kd.select2('destroy');
            $kd.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '— Pilih Kota/Kab —',
                allowClear: true,
                matcher: makeKotaDomMatcher(),
            });
        }

        // ── Re-init Select2 when a tab is shown ──────────────────────
        document.querySelectorAll('#niasTabs button[data-bs-toggle="tab"]').forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (e) {
                const targetId = e.target.getAttribute('data-bs-target');
                const panel = document.querySelector(targetId);
                if (!panel) return;

                // Re-init Select2 inside this panel
                $(panel).find('select.select2, select.select2-nonias, select.select2-nama').each(function () {
                    const $el = $(this);
                    // NAMAKOTADOM di panel-update di-init khusus dengan matcher filter
                    if ($el.is('#panel-update #NAMAKOTADOM')) return;
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: $el.find('option:first').text() || '— Pilih —',
                        allowClear: true,
                    });
                });

                // Panel update: init NAMAKOTADOM dengan matcher filter Jenis Wilayah
                if (panel.id === 'panel-update') {
                    initKotaDomSelect2();
                }

                // Re-init uppercase input behaviour
                $(panel).find('input[name="NAMA"], input[name="TEMPATLAHIR"]').off('input.toUpper').on('input.toUpper', function () {
                    const pos = this.selectionStart;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(pos, pos);
                });

                // Re-init file info display
                $(panel).find('input[type="file"]').off('change.fileInfo').on('change.fileInfo', function () {
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
        });

        // ── Update form: tipe_update logic ────────────────────────────
        $(function () {
            // Inisialisasi Select2 di panel yang aktif (Semua)
            const activePanel = document.querySelector('.tab-pane.active');
            if (activePanel) {
                $(activePanel).find('select.select2').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: $(this).find('option:first').text() || '— Pilih —',
                        allowClear: true,
                    });
                });
            }

            // ── Search bar: retain jenis param on submit ──────────────
            // (already handled by hidden input)

            // ── Select2 di create form (NAMACLUB, NAMAKOTADOM) ──────────
            $('#NAMACLUB').select2({
                theme: 'bootstrap-5',
                placeholder: '— Pilih —',
                allowClear: true,
                width: '100%',
            });

            // ── Update form: tipe_update logic ────────────────────────
            const userClub = '{{ $userClub }}';

            // ── Filter Kota/Kab di panel update berdasarkan JENISDOM ──
            jenisDomFilter = $('#panel-update #JENISDOM').val() || '';
            $('#panel-update #JENISDOM').on('change', function () {
                jenisDomFilter = $(this).val() || '';
                const $kd = $('#panel-update #NAMAKOTADOM');
                const cur = $kd.val();
                if (cur && jenisDomFilter) {
                    const v = cur.toUpperCase();
                    const ok = jenisDomFilter === 'KOTA' ? v.indexOf('KOTA') > -1 : v.indexOf('KAB.') > -1;
                    if (!ok) $kd.val('').trigger('change');
                }
            });

            function makeNoniasMatcher(filterByClub) {
                return function (params, data) {
                    const $el = $(data.element);
                    if (filterByClub && $el.hasClass('opt-otherclub')) return null;
                    if (!params.term || params.term.trim() === '') return data;
                    if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) return data;
                    return null;
                };
            }

            function initNoniasSelect2(filterByClub) {
                const $sel = $('#panel-update #NONIAS');
                if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
                $sel.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '— Pilih No NIAS —',
                    allowClear: true,
                    matcher: makeNoniasMatcher(filterByClub),
                });
                bindNoniasChange();
            }

            function initNamaSelect2(filterByClub) {
                const $sel = $('#panel-update #NAMA');
                if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
                $sel.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '— Pilih atau ketik nama —',
                    allowClear: true,
                    tags: true,
                    matcher: makeNoniasMatcher(filterByClub),
                    createTag: function (params) {
                        const term = $.trim(params.term).toUpperCase();
                        if (!term) return null;
                        return { id: term, text: term, newTag: true };
                    },
                    language: { noResults: function () { return 'Ketik nama baru jika belum terdaftar'; } }
                });
            }

            function bindNoniasChange() {
                $('#panel-update #NONIAS').off('change.autofill').on('change.autofill', function () {
                    const selected = $(this).find('option:selected');
                    if (!selected.val()) return;
                    const nama = selected.data('nama') || '';
                    const gender = selected.data('gender') || '';
                    const tptlahir = selected.data('tptlahir') || '';
                    const tgllahir = selected.data('tgllahir') || '';

                    const namaSelect = $('#panel-update #NAMA');
                    if (namaSelect.find("option[value='" + nama + "']").length === 0) {
                        namaSelect.append(new Option(nama, nama, true, true)).trigger('change');
                    } else {
                        namaSelect.val(nama).trigger('change');
                    }
                    const genderMapped = (['PA', 'L'].includes(gender.toUpperCase())) ? 'L' : 'P';
                    $('select[name="GENDER"]').val(genderMapped).trigger('change');
                    $('input[name="TEMPATLAHIR"]').val(tptlahir.toUpperCase());
                    $('input[name="TGLLAHIR"]').val(tgllahir);
                });
            }

            $('#panel-update #NAMA').on('select2:open', function () {
                setTimeout(function () {
                    $('.select2-search__field').on('input', function () {
                        this.value = this.value.toUpperCase();
                    });
                }, 100);
            });

            $('input[name="TEMPATLAHIR"]').on('input', function () {
                this.value = this.value.toUpperCase();
            });

            function applyTipe(tipe) {
                const domisiliRequired = (tipe === 'update_domisili' || tipe === 'update_all');
                const clubRequired = (tipe === 'update_club' || tipe === 'update_all');
                const filterByClub = (tipe === 'perpanjangan' || tipe === 'update_domisili');

                initNoniasSelect2(filterByClub);
                initNamaSelect2(filterByClub);

                $('#panel-update #NONIAS').val('').trigger('change');
                $('#panel-update #NAMA').val('').trigger('change');

                const wrapperDom = $('#panel-update #wrapper_domisili');
                if (domisiliRequired) {
                    wrapperDom.show();
                    wrapperDom.find('#JENISDOM, #NAMAKOTADOM').prop('required', true);
                    wrapperDom.find('input[name="mutasi_luar_jatim"]').prop('required', true);
                    initKotaDomSelect2();
                } else {
                    wrapperDom.hide();
                    jenisDomFilter = '';
                    wrapperDom.find('#JENISDOM').val('');
                    wrapperDom.find('#NAMAKOTADOM').val('').trigger('change');
                    wrapperDom.find('#JENISDOM, #NAMAKOTADOM').prop('required', false);
                    wrapperDom.find('input[name="mutasi_luar_jatim"]').prop('required', false).prop('checked', false);
                    $('#panel-update #mutasi_tidak').prop('checked', true);
                }

                const wrapperKk = $('#panel-update #wrapper_file_kk');
                wrapperKk.toggle(domisiliRequired);
                wrapperKk.find('#file_kk').prop('required', domisiliRequired).val('');

                const wrapperSk = $('#panel-update #wrapper_file_sk_mutasi');
                wrapperSk.toggle(clubRequired);
                wrapperSk.find('#file_sk_mutasi').prop('required', clubRequired).val('');

                const domisiliSuffix = domisiliRequired ? ' (KK Terbaru)' : ' (KK)';
                $('#panel-update #label_jenisdom').html('Jenis Wilayah' + domisiliSuffix + ' <span class="text-danger">*</span>');
                $('#panel-update #label_kotadom').html('Nama Kota/Kab' + domisiliSuffix + ' <span class="text-danger">*</span>');
            }

            applyTipe('');

            $('#panel-update #tipe_update').on('change', function () {
                applyTipe($(this).val());
            });

            $('#panel-update form').on('submit', function (e) {
                const tipe = $('#panel-update #tipe_update').val();
                if (!tipe) {
                    e.preventDefault();
                    $('#panel-update #tipe_update').addClass('is-invalid').focus();
                    Swal.fire({
                        title: 'Tipe Update Belum Dipilih',
                        text: 'Silakan pilih tipe update terlebih dahulu sebelum melanjutkan.',
                        icon: 'warning',
                        confirmButtonColor: '#0d6efd',
                        confirmButtonText: 'Pilih Sekarang',
                    });
                    return;
                }
                $('#panel-update #tipe_update').removeClass('is-invalid');

                if (!e.isDefaultPrevented()) {
                    e.preventDefault();
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
                            $('#btn_submit_update').prop('disabled', true).html(
                                '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...'
                            );
                            $(e.target).off('submit').submit();
                        }
                    });
                }
            });
        });{{-- close first $(function() --}}

        $(function () {
            // ── Checkbox: select all / per baris ─────────────────────
            const chkAll = document.getElementById('chk_all');
            if (chkAll) {
                chkAll.addEventListener('change', function () {
                    document.querySelectorAll('.chk_row').forEach(c => c.checked = this.checked);
                    updateSelectedCount();
                });
                document.addEventListener('change', function (e) {
                    if (e.target.classList.contains('chk_row')) updateSelectedCount();
                });
            }

            function updateSelectedCount() {
                const checked = document.querySelectorAll('.chk_row:checked');
                const count = checked.length;
                const el = document.getElementById('selected_count');
                const btn = document.getElementById('btn_delete_selected');
                if (el) el.textContent = count + ' dipilih';
                if (btn) btn.classList.toggle('d-none', count === 0);
                if (chkAll) {
                    chkAll.indeterminate = count > 0 && count < document.querySelectorAll('.chk_row').length;
                    chkAll.checked = count > 0 && count === document.querySelectorAll('.chk_row').length;
                }
            }

            // ── Delete functions ─────────────────────────────────────
            window.confirmDeleteOne = function (nama, url) {
                Swal.fire({
                    title: 'Hapus Data?',
                    html: 'Data <strong>' + nama + '</strong> akan dihapus permanen.',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                }).then(result => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form_delete_one');
                        form.action = url;
                        form.submit();
                    }
                });
            };

            window.confirmDeleteSelected = function () {
                const ids = [...document.querySelectorAll('.chk_row:checked')].map(c => c.value);
                if (!ids.length) return;
                Swal.fire({
                    title: 'Hapus ' + ids.length + ' Data?',
                    text: 'Data yang dipilih akan dihapus permanen.',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                }).then(result => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form_delete_selected');
                        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
                            form.appendChild(input);
                        });
                        form.submit();
                    }
                });
            };

            window.confirmDeleteAll = function () {
                const total = {{ $records->total() ?? 0 }};
                if (!total) { Swal.fire('Tidak Ada Data', 'Tidak ada data yang bisa dihapus.', 'info'); return; }
                Swal.fire({
                    title: 'Hapus SEMUA Data?',
                    html: 'Seluruh <strong>' + total + '</strong> data akan dihapus permanen.',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Semua!', cancelButtonText: 'Batal',
                    input: 'text', inputPlaceholder: 'Ketik HAPUS untuk konfirmasi',
                    inputValidator: (value) => { if (value !== 'HAPUS') return 'Ketik HAPUS untuk melanjutkan.'; }
                }).then(result => {
                    if (result.isConfirmed) document.getElementById('form_delete_all').submit();
                });
            };

            // ── Sent records functions ─────────────────────────────
            const chkSentAll = document.getElementById('chk_sent_all');
            if (chkSentAll) {
                chkSentAll.addEventListener('change', function () {
                    document.querySelectorAll('.chk_sent_row').forEach(c => c.checked = this.checked);
                    updateSentSelectedCount();
                });
                document.addEventListener('change', function (e) {
                    if (e.target.classList.contains('chk_sent_row')) updateSentSelectedCount();
                });
            }

            function updateSentSelectedCount() {
                const checked = document.querySelectorAll('.chk_sent_row:checked');
                const count = checked.length;
                const el = document.getElementById('sent_selected_count');
                const btn = document.getElementById('btn_delete_sent_selected');
                if (el) el.textContent = count + ' dipilih';
                if (btn) btn.classList.toggle('d-none', count === 0);
            }

            window.confirmDeleteSentOne = function (nama, url) {
                Swal.fire({
                    title: 'Hapus Data Terkirim?',
                    html: 'Data <strong>' + nama + '</strong> akan dihapus permanen.',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                }).then(result => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form_delete_one');
                        form.action = url; form.submit();
                    }
                });
            };

            window.confirmDeleteSentSelected = function () {
                const ids = [...document.querySelectorAll('.chk_sent_row:checked')].map(c => c.value);
                if (!ids.length) return;
                Swal.fire({
                    title: 'Hapus ' + ids.length + ' Data Terkirim?',
                    text: 'Data yang dipilih akan dihapus permanen.', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                }).then(result => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form_delete_sent_selected');
                        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
                            form.appendChild(input);
                        });
                        form.submit();
                    }
                });
            };

            window.confirmDeleteSentAll = function () {
                const total = {{ $sentRecords->total() ?? 0 }};
                if (!total) { Swal.fire('Tidak Ada Data', 'Tidak ada data terkirim.', 'info'); return; }
                Swal.fire({
                    title: 'Hapus SEMUA Data Terkirim?',
                    html: 'Seluruh <strong>' + total + '</strong> data terkirim akan dihapus permanen.',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Semua!', cancelButtonText: 'Batal',
                    input: 'text', inputPlaceholder: 'Ketik HAPUS untuk konfirmasi',
                    inputValidator: (value) => { if (value !== 'HAPUS') return 'Ketik HAPUS untuk melanjutkan.'; }
                }).then(result => {
                    if (result.isConfirmed) document.getElementById('form_delete_sent_all').submit();
                });
            };

            // ── Email modal ──────────────────────────────────────────
            document.querySelector('textarea[name="keterangan"]')?.addEventListener('input', function () {
                document.getElementById('char_count').textContent = this.value.length;
            });

            window.konfirmasiKirim = function (e) {
                e.preventDefault();
                const btn = document.getElementById('btn_kirim');
                const form = btn.closest('form');
                Swal.fire({
                    title: 'Kirim Data ke POSSI Jatim?',
                    html: 'Data dan dokumen akan dikirim ke <strong>it.possijatim@gmail.com</strong>.',
                    icon: 'question', showCancelButton: true,
                    confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Kirim!', cancelButtonText: 'Batal',
                }).then(result => {
                    if (result.isConfirmed) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';
                        form.submit();
                    }
                });
            };

            // ── Flash messages ──────────────────────────────────────
            @if(session('success'))
                Swal.fire({
                    title: 'Berhasil!', text: '{{ addslashes(session("success")) }}',
                    icon: 'success', confirmButtonColor: '#0d6efd'
                });
            @endif
            @if(session('error'))
                Swal.fire({
                    title: 'Gagal!', text: '{{ addslashes(session("error")) }}',
                    icon: 'error', confirmButtonColor: '#dc3545'
                });
            @endif
            @if(session('nias_closed'))
                Swal.fire({
                    title: 'Pendaftaran Ditutup', text: 'Masa pendaftaran NIAS sedang ditutup.',
                    icon: 'warning', confirmButtonColor: '#f0a500'
                });
            @endif
        });

        // ── Guard: tab Daftar NIAS Baru & Update NIAS cek periode buka ──
        $(function () {
            const niasOpen = @json($isNiasOpen);
            const isAdmin = @json(auth()->user()->role === 'admin');
            document.querySelectorAll('#tab-baru, #tab-update').forEach(function (tabBtn) {
                tabBtn.addEventListener('show.bs.tab', function (e) {
                    if (niasOpen || isAdmin) return;
                    e.preventDefault();
                    Swal.fire({
                        title: 'Pendaftaran Ditutup',
                        text: 'Masa pendaftaran NIAS sedang ditutup.',
                        icon: 'warning',
                        confirmButtonColor: '#f0a500',
                    });
                });
            });
        });
    </script>
@endpush
