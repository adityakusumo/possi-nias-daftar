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

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-2"></i>Simpan Semua Perubahan NIAS
                                </button>
                            </div>
                        </form>

                    {{-- Form Tarif NIAS (sibling, BUKAN nested) --}}
                    <form action="{{ route('settings.tarif.save') }}" method="POST">
                        @csrf
                        <div class="card border shadow-sm mb-4">
                            <div class="card-header bg-light fw-bold">
                                <i class="bi bi-cash-coin me-2"></i>Tarif Pendaftaran NIAS
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Biaya Pendaftaran Baru</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="biaya_baru" class="form-control"
                                                   value="{{ $tarifNias['baru'] ?? 60000 }}" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Biaya Update / Perpanjang</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="biaya_update" class="form-control"
                                                   value="{{ $tarifNias['update'] ?? 30000 }}" min="0">
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
                    </div>

                    {{-- Form tersembunyi untuk Reset Jadwal --}}
                    <form id="form_reset_nias" action="{{ route('settings.nias.reset') }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                @endif

                {{-- KONTEN TAB 2: LOMBA (Manajemen User) --}}
                @if(request('tab') === 'lomba')
                    <div class="tab-content-area">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Manajemen Akun Pelatih / Lomba</h6>
                            <form action="{{ route('settings') }}" method="GET" class="d-flex">
                                <input type="hidden" name="tab" value="lomba">
                                <input type="text" name="cari" class="form-control form-control-sm me-2"
                                    placeholder="Cari nama/email..." value="{{ request('cari') }}">
                                <button type="submit" class="btn btn-sm btn-dark">Cari</button>
                            </form>
                        </div>

                        {{-- Tabel daftar user/atlet yang sudah ada di file lama Anda --}}
                        <div class="table-responsive">
                            <table class="table table-sm table-hover border">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Club</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->nama }}</td>
                                            <td>{{ $user->namaclub }}</td>
                                            <td><span class="badge bg-secondary">{{ $user->role }}</span></td>
                                            <td>
                                                {{-- Tombol Reset Password & Delete --}}
                                                <button onclick="confirmReset('{{ $user->id }}')"
                                                    class="btn btn-xs btn-warning">Reset</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $users->links() }}
                        </div>
                    </div>
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
                        $akunSortCol = request('sort_akun', 'nama');
                        $akunSortDir = request('dir_akun', 'asc');
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
                                        {{ $u->created_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $u->updated_at?->format('d/m/Y H:i') ?? '—' }}
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