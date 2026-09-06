@extends('layouts.app')
@section('title', 'Pengaturan Sistem — Admin')

@section('content')
    <div class="card page-card">
        <div class="card-header d-flex justify-content-between align-items-center bg-white">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-gear-fill me-2 text-primary"></i>Pengaturan Sistem</h5>
            <a href="{{ route('welcome') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Portal
            </a>
        </div>

        <div class="card-body p-0">
            {{-- Navigasi Tab --}}
            <ul class="nav nav-tabs px-3 pt-3 bg-light" id="settingTab">
                <li class="nav-item">
                    <a class="nav-link {{ !request('tab') || request('tab') === 'nias' ? 'active fw-bold border-bottom-0' : '' }}"
                        href="{{ route('settings', ['tab' => 'nias']) }}">
                        <i class="bi bi-person-vcard me-2"></i>Setting NIAS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('tab') === 'lomba' ? 'active fw-bold border-bottom-0' : '' }}"
                        href="{{ route('settings', ['tab' => 'lomba']) }}">
                        <i class="bi bi-trophy me-2"></i>Setting Lomba (User)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('tab') === 'akun' ? 'active fw-bold border-bottom-0' : '' }}"
                        href="{{ route('settings', ['tab' => 'akun']) }}">
                        <i class="bi bi-people me-2"></i>Manajemen Akun
                    </a>
                </li>
            </ul>

            <div class="p-4">
                {{-- KONTEN TAB 1: NIAS --}}
                {{-- KONTEN TAB 1: NIAS --}}
                @if(!request('tab') || request('tab') === 'nias')
                    <div class="tab-content-area">
                        <form action="{{ route('settings.nias.save') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card border shadow-sm">
                                        <div class="card-header bg-light fw-bold">
                                            <i class="bi bi-calendar-range me-2"></i>Jadwal Pendaftaran
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Tanggal Dibuka</label>
                                                <input type="date" name="nias_open_date" class="form-control"
                                                    value="{{ $niasOpenDate }}">
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label small fw-bold">Tanggal Ditutup</label>
                                                <input type="date" name="nias_close_date" class="form-control"
                                                    value="{{ $niasCloseDate }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div
                                        class="alert {{ \App\Models\AppSetting::isNiasOpen() ? 'alert-success' : 'alert-warning' }} h-100 d-flex align-items-center">
                                        <div>
                                            <h6 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Status Saat Ini:</h6>
                                            <p class="mb-0">
                                                @if(\App\Models\AppSetting::isNiasOpen())
                                                    Pendaftaran sedang <strong>TERBUKA</strong>.
                                                @else
                                                    Pendaftaran sedang <strong>TERTUTUP</strong>.
                                                @endif
                                            </p>
                                            <hr>
                                            <button type="button" onclick="confirmResetJadwal()" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x-circle me-1"></i>Reset & Tutup Pendaftaran
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border shadow-sm mb-4">
                                <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-shield-lock me-2"></i>Batas Akun Pelatih per Club</span>
                                    <span class="badge bg-dark">Default: 2 Akun</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th class="ps-3">Nama Club</th>
                                                    <th class="text-center">Akun Terdaftar</th>
                                                    <th style="width: 150px;">Batas Maksimal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($clubStats as $clubName => $stat)
                                                    <tr>
                                                        <td class="ps-3 align-middle">{{ $clubName }}</td>
                                                        <td class="text-center align-middle">
                                                            <span
                                                                class="badge {{ $stat['count'] >= $stat['max'] ? 'bg-danger' : 'bg-info' }}">
                                                                {{ $stat['count'] }} / {{ $stat['max'] }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="max_accounts[{{ $clubName }}]"
                                                                class="form-control form-control-sm text-center"
                                                                value="{{ $stat['max'] }}" min="1">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        <div class="card border shadow-sm mb-4">
                            <div class="card-header bg-light fw-bold">
                                <i class="bi bi-cash-coin me-2"></i>Tarif Pendaftaran NIAS
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Biaya Pendaftaran Baru</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="biaya_baru" class="form-control"
                                                   value="{{ $tarifNias['baru'] ?? 60000 }}" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Biaya Update / Perpanjang</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="biaya_update" class="form-control"
                                                   value="{{ $tarifNias['update'] ?? 30000 }}" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i>Simpan Semua Perubahan NIAS
                        </button>
                    </div>
                    </form>
                    </div>

                    {{-- Form tersembunyi untuk Reset Jadwal --}}
                    <form id="form_reset_nias" action="{{ route('settings.nias.reset') }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                @endif

                {{-- KONTEN TAB 2: LOMBA (Setting + Manajemen User) --}}
                @if(request('tab') === 'lomba')
                    <div class="tab-content-area">

                        {{-- 1. Jenis Kompetisi --}}
                        <form action="{{ route('settings.lomba.save') }}" method="POST" class="mb-4">
                            @csrf
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    <i class="bi bi-trophy me-2"></i>Jenis Kompetisi
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Jenis Kompetisi</label>
                                            <select name="jns_kompetisi" class="form-select" required>
                                                <option value="K" {{ ($kompetisi->JNSKOMPETISI ?? 'K') == 'K' ? 'selected' : '' }}>Kabupaten / Kota</option>
                                                <option value="P" {{ ($kompetisi->JNSKOMPETISI ?? '') == 'P' ? 'selected' : '' }}>Provinsi</option>
                                                <option value="C" {{ ($kompetisi->JNSKOMPETISI ?? '') == 'C' ? 'selected' : '' }}>Club</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Keterangan</label>
                                            <input type="text" name="ket_kompetisi" id="ketKompetisi" class="form-control"
                                                   value="{{ $kompetisi->KETKOMPETISI ?? 'ANTAR KOTA' }}">
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-info-circle me-1"></i>Otomatis berubah saat jenis kompetisi diganti.
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Wajib NIAS</label>
                                            <select name="wajib_nias" class="form-select">
                                                <option value="0" {{ optional($kompetisi)->WAJIBNIAS == '0' ? 'selected' : '' }}>Tidak</option>
                                                <option value="1" {{ optional($kompetisi)->WAJIBNIAS == '1' ? 'selected' : '' }}>Ya</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-save me-1"></i>Simpan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        {{-- 2. Tarif Daftar Lomba --}}
                        <form action="{{ route('settings.lomba.tarif.save') }}" method="POST" class="mb-4">
                            @csrf
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    <i class="bi bi-cash-coin me-2"></i>Tarif Daftar Lomba
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Perorangan (per nomor)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="tarif_perorangan" class="form-control"
                                                       value="{{ $lombaTarifPerorangan }}" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Estafet (per nomor)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="tarif_estafet" class="form-control"
                                                       value="{{ $lombaTarifEstafet }}" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-save me-1"></i>Simpan Tarif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        {{-- 3. Deposit --}}
                        <form action="{{ route('settings.lomba.deposit.save') }}" method="POST" class="mb-4">
                            @csrf
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    <i class="bi bi-piggy-bank me-2"></i>Deposit (per Range Atlet)
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="depositTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:40px">#</th>
                                                    <th>Jml Atlet Mulai</th>
                                                    <th>Jml Atlet Sampai</th>
                                                    <th>Rp Deposit</th>
                                                    <th style="width:50px"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($depositRanges as $i => $d)
                                                <tr>
                                                    <td class="text-muted small">{{ $loop->iteration }}</td>
                                                    <td><input type="number" name="deposit[{{ $i }}][mulai]" class="form-control form-control-sm" value="{{ $d->JMLATLETMULAI }}" min="1" required></td>
                                                    <td><input type="number" name="deposit[{{ $i }}][sampai]" class="form-control form-control-sm" value="{{ $d->JMLATLETSAMPAI }}" min="1" required></td>
                                                    <td><input type="number" name="deposit[{{ $i }}][rp]" class="form-control form-control-sm" value="{{ $d->RPDEPOSIT }}" min="0" required></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td class="text-muted small">1</td>
                                                    <td><input type="number" name="deposit[0][mulai]" class="form-control form-control-sm" value="1" min="1" required></td>
                                                    <td><input type="number" name="deposit[0][sampai]" class="form-control form-control-sm" value="10" min="1" required></td>
                                                    <td><input type="number" name="deposit[0][rp]" class="form-control form-control-sm" value="500000" min="0" required></td>
                                                    <td></td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDepositRow()">
                                        <i class="bi bi-plus me-1"></i>Tambah Range
                                    </button>
                                    <button type="submit" class="btn btn-primary ms-2">
                                        <i class="bi bi-save me-1"></i>Simpan Deposit
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- 4. Denda --}}
                        <form action="{{ route('settings.lomba.denda.save') }}" method="POST" class="mb-4">
                            @csrf
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Denda
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">RPDENDAOL (Over Limit)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="rpdendaol" class="form-control"
                                                       value="{{ $dendaData->RPDENDAOL ?? 0 }}" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">RPDENDADQ (DQ)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="rpdendadq" class="form-control"
                                                       value="{{ $dendaData->RPDENDADQ ?? 0 }}" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">RPDENDANOSWIM (No Swim)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="rpdendanoswim" class="form-control"
                                                       value="{{ $dendaData->RPDENDANOSWIM ?? 0 }}" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Simpan Denda
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        {{-- 5. Biaya Lain-lain --}}
                        <form action="{{ route('settings.lomba.biayaextra.save') }}" method="POST" class="mb-4">
                            @csrf
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    <i class="bi bi-plus-circle me-2"></i>Biaya Lain-lain
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="biayaExtraTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:40px">#</th>
                                                    <th>Keterangan</th>
                                                    <th>Rp Biaya</th>
                                                    <th style="width:50px"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($biayaExtraList as $i => $be)
                                                <tr>
                                                    <td class="text-muted small">{{ $loop->iteration }}</td>
                                                    <td><input type="text" name="biaya_extra[{{ $i }}][keterangan]" class="form-control form-control-sm" value="{{ $be->KETERANGAN }}" required></td>
                                                    <td><input type="number" name="biaya_extra[{{ $i }}][rp]" class="form-control form-control-sm" value="{{ $be->RPBIAYAEXTRA }}" min="0" required></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td class="text-muted small">1</td>
                                                    <td><input type="text" name="biaya_extra[0][keterangan]" class="form-control form-control-sm" placeholder="Mis: Biaya administrasi" required></td>
                                                    <td><input type="number" name="biaya_extra[0][rp]" class="form-control form-control-sm" value="0" min="0" required></td>
                                                    <td></td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBiayaExtraRow()">
                                        <i class="bi bi-plus me-1"></i>Tambah Biaya
                                    </button>
                                    <button type="submit" class="btn btn-primary ms-2">
                                        <i class="bi bi-save me-1"></i>Simpan Biaya Lain-lain
                                    </button>
                                </div>
                            </div>
                        </form>

                        <hr>

                        {{-- 6. Manajemen Lomba Account --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Manajemen Lomba Account</h6>
                            <form action="{{ route('settings') }}" method="GET" class="d-flex">
                                <input type="hidden" name="tab" value="lomba">
                                <input type="text" name="cari_lomba" class="form-control form-control-sm me-2"
                                    placeholder="Cari nama/email..." value="{{ request('cari_lomba') }}">
                                <button type="submit" class="btn btn-sm btn-dark">Cari</button>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover border">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Nama</th>
                                        <th>No. WA</th>
                                        <th>Kompetisi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lombaUsers as $lu)
                                        @php
                                            $jns = optional($lu->kontingen)->jns_kompetisi;
                                            $kompetisiLabel = match($jns) {
                                                'K' => 'Antar Kota',
                                                'P' => 'Antar Provinsi',
                                                'C' => 'Antar Club',
                                                null => '—',
                                                default => $jns,
                                            };
                                        @endphp
                                        <tr>
                                            <td class="small">{{ $lu->email }}</td>
                                            <td>{{ $lu->nama ?? '—' }}</td>
                                            <td>{{ $lu->no_wa ?? '—' }}</td>
                                            <td>
                                                @if($jns)
                                                    <span class="badge bg-info text-dark">{{ $kompetisiLabel }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($lu->isRegistered())
                                                    <span class="badge bg-success">Password Aktif</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Belum Password</span>
                                                @endif
                                            </td>
                                            <td class="text-center" style="white-space:nowrap">
                                                <a href="{{ route('settings.lomba.edit', $lu->id) }}"
                                                   class="btn btn-xs btn-outline-primary" title="Edit kontingen">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-xs btn-danger"
                                                        onclick="confirmDeleteLomba('{{ $lu->id }}', '{{ addslashes($lu->email) }}')"
                                                        title="Hapus akun lomba">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">
                                                <i class="bi bi-inbox me-1"></i>Tidak ada akun lomba ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $lombaUsers->links() }}
                        </div>

                        {{-- Hidden form for deleting lomba user --}}
                        <form id="form_delete_lomba" method="POST" style="display:none;">
                            @csrf @method('DELETE')
                        </form>
                    </div>

                    <script>
                    // ── Auto-fill keterangan berdasarkan jenis kompetisi ──
                    document.addEventListener('DOMContentLoaded', function () {
                        const kompetisiSelect = document.querySelector('select[name="jns_kompetisi"]');
                        const keteranganInput = document.getElementById('ketKompetisi');
                        if (kompetisiSelect && keteranganInput) {
                            kompetisiSelect.addEventListener('change', function () {
                                const labels = { 'K': 'ANTAR KOTA', 'P': 'ANTAR PROPINSI', 'C': 'ANTAR CLUB' };
                                keteranganInput.value = labels[this.value] || '';
                            });
                        }
                    });

                    let depositIdx = {{ count($depositRanges) > 0 ? count($depositRanges) : 1 }};
                    function addDepositRow() {
                        const tbody = document.querySelector('#depositTable tbody');
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="text-muted small">${depositIdx + 1}</td>
                            <td><input type="number" name="deposit[${depositIdx}][mulai]" class="form-control form-control-sm" min="1" required></td>
                            <td><input type="number" name="deposit[${depositIdx}][sampai]" class="form-control form-control-sm" min="1" required></td>
                            <td><input type="number" name="deposit[${depositIdx}][rp]" class="form-control form-control-sm" min="0" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                        `;
                        tbody.appendChild(tr);
                        depositIdx++;
                    }

                    let biayaExtraIdx = {{ count($biayaExtraList) > 0 ? count($biayaExtraList) : 1 }};
                    function addBiayaExtraRow() {
                        const tbody = document.querySelector('#biayaExtraTable tbody');
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="text-muted small">${biayaExtraIdx + 1}</td>
                            <td><input type="text" name="biaya_extra[${biayaExtraIdx}][keterangan]" class="form-control form-control-sm" placeholder="Keterangan" required></td>
                            <td><input type="number" name="biaya_extra[${biayaExtraIdx}][rp]" class="form-control form-control-sm" value="0" min="0" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                        `;
                        tbody.appendChild(tr);
                        biayaExtraIdx++;
                    }
                    </script>
                @endif

                {{-- KONTEN TAB 3: MANAJEMEN AKUN --}}
                @if(request('tab') === 'akun')
                <div class="tab-content-area">

                    {{-- Toolbar: search + bulk action --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <input type="checkbox" id="chk_akun_all" class="form-check-input mt-0" title="Pilih semua">
                            <span id="akun_selected_count" class="text-muted small">0 dipilih</span>
                            <button type="button" id="btn_delete_akun_selected"
                                    class="btn btn-sm btn-outline-danger d-none"
                                    onclick="confirmDeleteAkunSelected()">
                                <i class="bi bi-trash me-1"></i>Hapus Dipilih
                            </button>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="confirmDeleteAkunAll()">
                                <i class="bi bi-trash3 me-1"></i>Hapus Semua Non-Admin
                            </button>
                        </div>
                        <form method="GET" action="{{ route('settings', ['tab' => 'akun']) }}"
                              class="d-flex gap-2">
                            <input type="hidden" name="tab" value="akun">
                            <input type="text" name="cari" class="form-control form-control-sm"
                                   placeholder="Cari nama / email / club…"
                                   value="{{ request('cari') }}" style="min-width:220px">
                            <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                            @if(request('cari'))
                            <a href="{{ route('settings', ['tab' => 'akun']) }}"
                               class="btn btn-sm btn-outline-secondary">Reset</a>
                            @endif
                        </form>
                    </div>

                    @php
                        // Helper sort URL untuk tab akun
                        $akunSortCol = request('sort_akun', 'updated_at');
                        $akunSortDir = request('sort_akun') ? request('dir_akun', 'asc') : 'desc';
                        $akunSortables = ['nama', 'namaclub', 'email', 'role', 'created_at', 'updated_at'];
                        $thAkun = function(string $col, string $label) use ($akunSortCol, $akunSortDir): string {
                            $isActive = $col === $akunSortCol;
                            $nextDir  = ($isActive && $akunSortDir === 'asc') ? 'desc' : 'asc';
                            $url = request()->fullUrlWithQuery([
                                'tab'      => 'akun',
                                'sort_akun'=> $col,
                                'dir_akun' => $nextDir,
                                'page'     => 1,
                            ]);
                            $icon = $isActive
                                ? ($akunSortDir === 'asc'
                                    ? ' <i class="bi bi-caret-up-fill small"></i>'
                                    : ' <i class="bi bi-caret-down-fill small"></i>')
                                : ' <i class="bi bi-chevron-expand small opacity-50"></i>';
                            return '<th><a href="' . $url . '" class="text-white text-decoration-none d-flex align-items-center gap-1 justify-content-between">'
                                . e($label) . $icon . '</a></th>';
                        };
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:36px"></th>
                                    <th>#</th>
                                    {!! $thAkun('nama',       'Nama') !!}
                                    {!! $thAkun('email',      'Email') !!}
                                    {!! $thAkun('namaclub',   'Club') !!}
                                    {!! $thAkun('role',       'Role') !!}
                                    {!! $thAkun('created_at', 'Dibuat') !!}
                                    {!! $thAkun('updated_at', 'Diperbarui') !!}
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($akunUsers as $u)
                                <tr>
                                    <td class="text-center">
                                        @if($u->role !== 'admin')
                                        <input type="checkbox" class="form-check-input chk_akun_row mt-0"
                                               value="{{ $u->id }}">
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $akunUsers->firstItem() + $loop->index }}</td>
                                    <td class="fw-semibold small">{{ $u->nama }}</td>
                                    <td class="small">{{ $u->email }}</td>
                                    <td class="small">{{ $u->namaclub ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $u->role === 'admin' ? 'bg-danger' : 'bg-secondary' }}">
                                            {{ $u->role === 'admin' ? 'Admin' : 'Regular' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $u->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $u->updated_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="text-center" style="white-space:nowrap">
                                        <a href="{{ route('settings.akun.show', $u->id) }}"
                                           class="btn btn-sm btn-outline-primary py-0" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($u->role !== 'admin')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger py-0"
                                                title="Hapus"
                                                onclick="confirmDeleteAkunOne({{ $u->id }}, '{{ addslashes($u->nama) }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        Tidak ada akun ditemukan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                        <small class="text-muted">
                            @if($akunUsers->total())
                                Menampilkan {{ $akunUsers->firstItem() }}–{{ $akunUsers->lastItem() }}
                                dari <strong>{{ $akunUsers->total() }}</strong> akun
                            @endif
                        </small>
                        {{ $akunUsers->links() }}
                    </div>

                    {{-- Hidden forms --}}
                    <form id="form_delete_akun_one" method="POST" style="display:none;">
                        @csrf @method('DELETE')
                    </form>
                    <form id="form_delete_akun_selected" method="POST"
                          action="{{ route('settings.akun.destroySelected') }}" style="display:none;">
                        @csrf @method('DELETE')
                    </form>
                    <form id="form_delete_akun_all" method="POST"
                          action="{{ route('settings.akun.destroyAll') }}" style="display:none;">
                        @csrf @method('DELETE')
                    </form>

                </div>
                @endif

            </div>
        </div>
    </div>

    <style>
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            color: #6c757d;
        }

        .nav-tabs .nav-link.active {
            background-color: #fff !important;
            border-color: #dee2e6 #dee2e6 #fff !important;
            color: #0d6efd !important;
        }

        .btn-xs {
            padding: 0.1rem 0.4rem;
            font-size: 0.75rem;
        }
    </style>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmResetJadwal() {
            Swal.fire({
                title: 'Reset Jadwal?',
                text: "Pendaftaran akan langsung ditutup dan tanggal pendaftaran akan dikosongkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form_reset_nias').submit();
                }
            })
        }

        // ── Tab Akun: checkbox ────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const chkAll = document.getElementById('chk_akun_all');
            if (!chkAll) return;

            chkAll.addEventListener('change', function () {
                document.querySelectorAll('.chk_akun_row').forEach(c => c.checked = this.checked);
                updateAkunCount();
            });
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('chk_akun_row')) updateAkunCount();
            });
        });

        function updateAkunCount() {
            const count = document.querySelectorAll('.chk_akun_row:checked').length;
            const el    = document.getElementById('akun_selected_count');
            const btn   = document.getElementById('btn_delete_akun_selected');
            if (el)  el.textContent  = count + ' dipilih';
            if (btn) btn.classList.toggle('d-none', count === 0);
        }

        // ── Delete satu akun ──────────────────────────────────────
        function confirmDeleteAkunOne(userId, nama) {
            Swal.fire({
                title: 'Hapus Akun?',
                html: 'Akun <strong>' + nama + '</strong> akan dihapus permanen.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form_delete_akun_one');
                    form.action = '/settings/users/' + userId + '/delete';
                    form.submit();
                }
            });
        }

        // ── Delete selected ───────────────────────────────────────
        function confirmDeleteAkunSelected() {
            const ids = [...document.querySelectorAll('.chk_akun_row:checked')].map(c => c.value);
            if (!ids.length) return;
            Swal.fire({
                title: 'Hapus ' + ids.length + ' Akun?',
                text: 'Akun yang dipilih akan dihapus permanen.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form_delete_akun_selected');
                    form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
                    ids.forEach(id => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                        form.appendChild(inp);
                    });
                    form.submit();
                }
            });
        }

        // ── Delete lomba account ──────────────────────────────────
        function confirmDeleteLomba(userId, email) {
            Swal.fire({
                title: 'Hapus Akun Lomba?',
                html: 'Akun lomba <strong>' + email + '</strong> akan dihapus permanen.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form_delete_lomba');
                    form.action = '/settings/lomba/users/' + userId;
                    form.submit();
                }
            });
        }

        // ── Delete all non-admin ──────────────────────────────────
        function confirmDeleteAkunAll() {
            Swal.fire({
                title: 'Hapus Semua Akun Non-Admin?',
                html: 'Seluruh akun <strong>regular</strong> akan dihapus permanen.<br>'
                    + '<span class="text-danger fw-bold">Tindakan ini tidak bisa dibatalkan!</span>',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus Semua!', cancelButtonText: 'Batal',
                input: 'text', inputPlaceholder: 'Ketik HAPUS untuk konfirmasi',
                inputValidator: (value) => { if (value !== 'HAPUS') return 'Ketik HAPUS untuk melanjutkan.'; }
            }).then(result => {
                if (result.isConfirmed) document.getElementById('form_delete_akun_all').submit();
            });
        }
    </script>
@endpush