@extends('layouts.app')
@section('title', 'Detail Data NIAS')

@section('content')
<div class="card page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Detail Data NIAS</h5>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Tombol Acc / Reject — hanya admin, hanya saat STATUS pending (2) atau terkirim (3).
                 Saat berstatus Caution (duplikat), ACC/Reject disembunyikan: admin wajib
                 selesaikan dulu lewat modal pemeriksaan duplikat. --}}
            @if(Auth::user()->role === 'admin' && $nias->has_possible_duplicate)
            <button type="button" class="btn btn-warning btn-sm"
                    onclick="openDupModal()">
                <i class="bi bi-exclamation-triangle me-1"></i>Selesaikan Duplikat
            </button>
            @elseif(Auth::user()->role === 'admin' && in_array((int)$nias->STATUS, [2, 3]))
            <button type="button" class="btn btn-success btn-sm"
                    onclick="confirmAcc()">
                <i class="bi bi-check-circle me-1"></i>Terima / ACC
            </button>
            <button type="button" class="btn btn-danger btn-sm"
                    onclick="confirmReject()">
                <i class="bi bi-x-circle me-1"></i>Tolak / Reject
            </button>
            @endif
            {{-- Tombol edit: admin bisa edit semua, regular hanya yang belum dikirim --}}
            @if(Auth::user()->role === 'admin' || !$nias->is_sent)
            <a href="{{ route('nias.edit', $nias->ID) }}"
               class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('nias.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>

        {{-- Hidden forms untuk acc/reject --}}
        <form id="form_acc"    method="POST" action="{{ route('nias.acc',    $nias->ID) }}" style="display:none">@csrf</form>
        <form id="form_reject" method="POST" action="{{ route('nias.reject', $nias->ID) }}" style="display:none">@csrf</form>
    </div>

    <div class="card-body p-4">

        {{-- Status badge --}}
        <div class="mb-4">
            @php
                $statusLabel = match((int)$nias->STATUS) {
                    1 => ['label' => 'DISETUJUI', 'class' => 'bg-success'],
                    2 => ['label' => 'PENDING ACC', 'class' => 'bg-warning text-dark'],
                    3 => ['label' => 'SUDAH DIKIRIM', 'class' => 'bg-info text-dark'],
                    0 => ['label' => 'EXPIRED', 'class' => 'bg-danger'],
                    4 => ['label' => 'DIBATALKAN (DUPLIKAT)', 'class' => 'bg-dark'],
                    default => ['label' => 'TIDAK DIKETAHUI', 'class' => 'bg-secondary'],
                };
            @endphp
            <span class="badge {{ $statusLabel['class'] }} fs-6 px-3 py-2">
                <i class="bi bi-circle-fill me-1 small"></i>{{ $statusLabel['label'] }}
            </span>
            @if($nias->has_possible_duplicate)
                <span class="badge badge-caution ms-2 fs-6 px-3 py-2" title="Terindikasi duplikat dengan atlet yang sudah terdaftar di database NIAS">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>CAUTION
                </span>
            @endif
            @if($nias->is_update)
                <span class="badge bg-info text-dark ms-2 fs-6 px-3 py-2">
                    <i class="bi bi-arrow-repeat me-1"></i>UPDATE / PERPANJANG
                </span>
            @endif
        </div>

        {{-- Peringatan Caution: admin → modal resolusi (auto-open via JS); pelatih → info menunggu admin --}}
        @if($nias->has_possible_duplicate)
            @if(Auth::user()->role === 'admin')
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    <div>
                        <strong>Data berstatus CAUTION.</strong> Sistem menemukan atlet dengan nama,
                        jenis kelamin, dan tanggal lahir yang sama di database NIAS.
                        Periksa kandidat duplikat di bawah lalu pilih keputusan.
                    </div>
                </div>
            @else
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    <div>
                        <strong>Data berstatus CAUTION.</strong> Terindikasi ada atlet dengan data
                        yang sama di database NIAS. Pendaftaran ini menunggu pemeriksaan admin
                        untuk memastikan bukan duplikat.
                    </div>
                </div>
            @endif
        @endif

        <div class="row g-4">

            {{-- ── Data Pribadi ─────────────────────────────────── --}}
            <div class="col-md-6">
                <div class="card section-card h-100">
                    <div class="card-header py-2 px-3">
                        <span class="fw-bold text-primary small">
                            <i class="bi bi-person me-1"></i>DATA PRIBADI
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted small" style="width:140px">Nama</td>
                                <td class="fw-semibold">{{ $nias->NAMA }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Jenis Kelamin</td>
                                <td>{{ $nias->GENDER === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Tempat Lahir</td>
                                <td>{{ $nias->TEMPATLAHIR }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Tanggal Lahir</td>
                                <td>{{ $nias->TGLLAHIR?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">NIK</td>
                                <td><code>{{ $nias->NIK ?? '—' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Email</td>
                                <td>{{ $nias->EMAIL ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Data Club & Domisili ─────────────────────────── --}}
            <div class="col-md-6">
                <div class="card section-card h-100">
                    <div class="card-header py-2 px-3">
                        <span class="fw-bold text-primary small">
                            <i class="bi bi-people me-1"></i>CLUB & DOMISILI
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted small" style="width:140px">Club</td>
                                <td class="fw-semibold text-primary">{{ $nias->NAMACLUB }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Kota Club</td>
                                <td>{{ ($nias->JENIS ?? '') . ' ' . ($nias->NAMAKOTA ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Domisili</td>
                                <td>{{ ($nias->JENISDOM ?? '') . ' ' . ($nias->NAMAKOTADOM ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Provinsi</td>
                                <td>{{ $nias->NAMAPROPDOM ?? 'JAWA TIMUR' }}</td>
                            </tr>
                            @if($nias->is_update)
                            <tr>
                                <td class="text-muted small">Tipe Update</td>
                                <td>
                                    @php
                                        $tipeLabel = match($nias->tipe_update) {
                                            'perpanjangan'  => 'Perpanjangan',
                                            'update_club'   => 'Pindah Club',
                                            'update_domisili' => 'Pindah Domisili',
                                            'update_all'    => 'Pindah Club & Domisili',
                                            default => $nias->tipe_update ?? '—',
                                        };
                                    @endphp
                                    <span class="badge bg-secondary">{{ $tipeLabel }}</span>
                                </td>
                            </tr>
                            @if($nias->mutasi_luar_jatim)
                            <tr>
                                <td class="text-muted small">Mutasi Luar Jatim</td>
                                <td>
                                    <span class="badge {{ $nias->mutasi_luar_jatim === 'ya' ? 'bg-warning text-dark' : 'bg-light text-dark border' }}">
                                        {{ strtoupper($nias->mutasi_luar_jatim) }}
                                    </span>
                                </td>
                            </tr>
                            @endif
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Data NIAS ────────────────────────────────────── --}}
            <div class="col-md-6">
                <div class="card section-card h-100">
                    <div class="card-header py-2 px-3">
                        <span class="fw-bold text-primary small">
                            <i class="bi bi-card-text me-1"></i>DATA NIAS
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted small" style="width:140px">No. NIAS Jatim</td>
                                <td><code>{{ $nias->NONIAS ?? '—' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Tgl Daftar</td>
                                <td>{{ $nias->TGLDAFTAR?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Tgl Update</td>
                                <td>{{ $nias->TGLDAFTAR_UPDATE?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Expired</td>
                                <td class="{{ $nias->EXPIRED?->isPast() ? 'text-danger fw-semibold' : '' }}">
                                    {{ $nias->EXPIRED?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Tgl Dikirim</td>
                                <td class="text-success">
                                    {{ $nias->sent_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Dokumen Upload ───────────────────────────────── --}}
            <div class="col-md-6">
                <div class="card section-card h-100">
                    <div class="card-header py-2 px-3">
                        <span class="fw-bold text-primary small">
                            <i class="bi bi-paperclip me-1"></i>DOKUMEN UPLOAD
                        </span>
                    </div>
                    <div class="card-body">
                        @php
                            $docs = [
                                'file_kk'       => 'Kartu Keluarga (KK)',
                                'file_foto'     => 'Foto Atlet',
                                'file_akte'     => 'Akte Lahir',
                                'file_ijazah'   => 'Ijazah / Raport',
                                'file_sk_mutasi'=> 'SK Mutasi',
                            ];
                        @endphp
                        <div class="list-group list-group-flush">
                            @foreach($docs as $col => $label)
                            @php $fileUrl = $nias->$col ? route('nias.file', ['id' => $nias->ID, 'col' => $col]) : null; @endphp
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-semibold">{{ $label }}</span>
                                    @if($fileUrl)
                                        <div class="d-flex gap-1">
                                            <a href="{{ $fileUrl }}" target="_blank"
                                               class="btn btn-sm btn-outline-primary py-0">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka
                                            </a>
                                            @if(Auth::user()->role === 'admin')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary py-0"
                                                    onclick="togglePreview('preview_{{ $col }}')">
                                                <i class="bi bi-eye me-1"></i>Preview
                                            </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted border">Tidak ada</span>
                                    @endif
                                </div>
                                {{-- Preview inline — hanya admin --}}
                                @if($fileUrl && Auth::user()->role === 'admin')
                                @php $ext = strtolower(pathinfo($nias->$col, PATHINFO_EXTENSION)); @endphp
                                <div id="preview_{{ $col }}" style="display:none" class="mt-2">
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <img src="{{ $fileUrl }}" alt="{{ $label }}"
                                             class="img-fluid rounded border"
                                             style="max-height:400px;width:100%;object-fit:contain;">
                                    @elseif($ext === 'pdf')
                                        <iframe src="{{ $fileUrl }}"
                                                class="w-100 rounded border"
                                                style="height:450px;"
                                                title="{{ $label }}"></iframe>
                                    @else
                                        <div class="alert alert-secondary small py-2">
                                            <i class="bi bi-file-earmark me-1"></i>
                                            Format file tidak bisa dipreview langsung. Klik "Buka" untuk mengunduh.
                                        </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end .row --}}
    </div>
</div>

{{-- ═══ Modal Pemeriksaan Duplikat (Caution) — khusus admin ═══ --}}
@if(Auth::user()->role === 'admin' && $nias->has_possible_duplicate)
<div class="modal fade" id="modalDupCheck" tabindex="-1" aria-labelledby="modalDupCheckLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-gradient">
                <h5 class="modal-title fw-bold" id="modalDupCheckLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Pemeriksaan Duplikat — CAUTION
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    <i class="bi bi-person-badge me-1"></i>
                    Registrasi ini: <strong>{{ $nias->NAMA }}</strong> ({{ $nias->GENDER === 'L' ? 'Laki-laki' : 'Perempuan' }},
                    lahir {{ $nias->TGLLAHIR?->format('d/m/Y') ?? '—' }}),
                    klub <strong>{{ $nias->NAMACLUB }}</strong>.
                </div>

                @if($possibleDuplicates->isNotEmpty())
                    <p class="text-muted small mb-2">
                        Kandidat duplikat berikut ditemukan di database NIAS:
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>L/P</th>
                                    <th>Klub Terkini</th>
                                    <th>Tgl Lahir</th>
                                    <th>Expired NIAS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($possibleDuplicates as $c)
                                    @php
                                        $gCand = in_array(strtoupper((string)$c->GENDER), ['PA', 'L'], true) ? 'L' : 'P';
                                        $dobCand = $c->TGLLAHIR ? \Carbon\Carbon::parse($c->TGLLAHIR) : null;
                                        $expCand = $c->EXPIRED ? \Carbon\Carbon::parse($c->EXPIRED) : null;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $c->NAMA }}
                                            @if($c->NONIAS)
                                                <div class="small text-muted"><code>{{ $c->NONIAS }}</code></div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($gCand === 'L')
                                                <span class="badge bg-primary">L</span>
                                            @else
                                                <span class="badge bg-danger">P</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $c->NAMACLUB ?? '—' }}</td>
                                        <td class="small">{{ $dobCand?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="small {{ $expCand && $expCand->isPast() ? 'text-danger' : '' }}">
                                            {{ $expCand?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-secondary small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Saat ini tidak ada kandidat yang cocok di database NIAS (kemungkinan data
                        master berubah). Jika Anda yakin data ini bukan duplikat, pilih
                        <strong>"Bukan Duplikat"</strong> untuk menghapus flag CAUTION.
                    </div>
                @endif
            </div>
            <div class="modal-footer d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i>Periksa Nanti
                </button>
                <button type="button" class="btn btn-outline-success" onclick="resolveDup('not_duplicate')">
                    <i class="bi bi-check-lg me-1"></i>Bukan Duplikat
                </button>
                <button type="button" class="btn btn-success" onclick="resolveDup('not_duplicate_acc')">
                    <i class="bi bi-check-circle me-1"></i>Bukan Duplikat &amp; Langsung ACC
                </button>
                <button type="button" class="btn btn-danger" onclick="resolveDup('duplicate')">
                    <i class="bi bi-x-octagon me-1"></i>Duplikat — Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden forms resolusi duplikat (admin) --}}
<form id="form_dup_not_dup"     method="POST" action="{{ route('nias.resolve-duplicate', $nias->ID) }}" style="display:none">@csrf<input type="hidden" name="decision" value="not_duplicate"></form>
<form id="form_dup_not_dup_acc" method="POST" action="{{ route('nias.resolve-duplicate', $nias->ID) }}" style="display:none">@csrf<input type="hidden" name="decision" value="not_duplicate_acc"></form>
<form id="form_dup_duplicate"   method="POST" action="{{ route('nias.resolve-duplicate', $nias->ID) }}" style="display:none">@csrf<input type="hidden" name="decision" value="duplicate"></form>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function togglePreview(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

@if(Auth::user()->role === 'admin' && $nias->has_possible_duplicate)
const dupModalEl = document.getElementById('modalDupCheck');
function openDupModal() {
    if (dupModalEl) new bootstrap.Modal(dupModalEl).show();
}

function resolveDup(decision) {
    const conf = {
        not_duplicate: {
            title: 'Bukan Duplikat?',
            html: 'Flag CAUTION akan dihapus dan data kembali ke status PENDING untuk diproses (ACC/Reject) seperti biasa.',
            icon: 'question',
            color: '#198754',
            btn: 'Ya, Bukan Duplikat',
            form: 'form_dup_not_dup',
        },
        not_duplicate_acc: {
            title: 'Bukan Duplikat & Langsung ACC?',
            html: 'Flag CAUTION akan dihapus dan data langsung berubah menjadi <span class="badge bg-success">DISETUJUI</span>.',
            icon: 'question',
            color: '#198754',
            btn: 'Ya, ACC!',
            form: 'form_dup_not_dup_acc',
        },
        duplicate: {
            title: 'Konfirmasi Duplikat?',
            html: 'Data <strong>{{ addslashes($nias->NAMA) }}</strong> akan DIBATALKAN. Status berubah menjadi <span class="badge bg-dark">DIBATALKAN (DUPLIKAT)</span>.',
            icon: 'warning',
            color: '#dc3545',
            btn: 'Ya, Batalkan!',
            form: 'form_dup_duplicate',
        },
    };
    const c = conf[decision];
    if (!c) return;
    Swal.fire({
        title: c.title,
        html: c.html,
        icon: c.icon,
        showCancelButton: true,
        confirmButtonColor: c.color,
        cancelButtonColor: '#6c757d',
        confirmButtonText: c.btn,
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) document.getElementById(c.form).submit();
    });
}

// Auto-open warning modal saat admin membuka detail data berstatus Caution
document.addEventListener('DOMContentLoaded', function () {
    openDupModal();
});
@endif

function confirmAcc() {
    Swal.fire({
        title: 'Terima / ACC Data Ini?',
        html: 'Data <strong>{{ addslashes($nias->NAMA) }}</strong> akan disetujui.<br>'
            + 'Status akan berubah menjadi <span class="badge bg-success">DISETUJUI</span>.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, ACC!',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('form_acc').submit();
    });
}

function confirmReject() {
    Swal.fire({
        title: 'Tolak / Reject Data Ini?',
        html: 'Data <strong>{{ addslashes($nias->NAMA) }}</strong> akan ditolak.<br>'
            + 'Status akan berubah menjadi <span class="badge bg-danger">DITOLAK</span>.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Tolak!',
        cancelButtonText: 'Batal',
        input: 'textarea',
        inputPlaceholder: 'Alasan penolakan (opsional)…',
        inputAttributes: { rows: 3 },
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('form_reject');
            if (result.value) {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'alasan'; input.value = result.value;
                form.appendChild(input);
            }
            form.submit();
        }
    });
}
</script>
@endpush
