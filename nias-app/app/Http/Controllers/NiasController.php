<?php

namespace App\Http\Controllers;

use App\Mail\NiasDataMail;
use App\Models\Nias;
use App\Models\NiasExisting;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class NiasController extends Controller
{
    /** STATUS numerik di NIAS_STRUCT */
    public const STATUS_DITOLAK    = 0; // ditolak / expired
    public const STATUS_DISETUJUI  = 1; // disetujui (ACC)
    public const STATUS_PENDING    = 2; // pending acc (belum dikirim)
    public const STATUS_TERKIRIM   = 3; // sudah dikirim, menunggu acc
    public const STATUS_DIBATALKAN = 4; // dibatalkan (terkonfirmasi duplikat)

    /**
     * Perpanjang diblokir bila masa berlaku masih tersisa LEBIH dari X hari.
     * Sesuai duplicate-check.txt: "prevent unnecessary early extension".
     * Catatan di dokumen menyarankan buffer 30–60 hari sebelum habis masa berlaku.
     */
    public const PERPANJANG_EARLY_DAYS = 60;

    // -------------------------------------------------------------------------
    // DUPLICATE CHECK — kandidat duplikat di tabel master NIAS
    // -------------------------------------------------------------------------
    /**
     * Cari atlet di database NIAS (tabel master, read-only) yang cocok PERSIS:
     * LOWER(NAMA) sama, gender sama, tanggal lahir sama.
     *
     * @param string|null $nama     Nama dari pendaftaran (sudah UPPER/trim di store)
     * @param string|null $gender   'L' / 'P' (format pendaftaran)
     * @param string|null $tgllahir Tanggal lahir (Y-m-d / Carbon)
     * @return \Illuminate\Support\Collection
     */
    public static function possibleDuplicates(?string $nama, ?string $gender, $tgllahir)
    {
        if (!$nama || !$gender || !$tgllahir) {
            return collect();
        }

        // Master NIAS memakai gender 'Pa'/'Pi'; pendaftaran memakai 'L'/'P'
        $genderSet = strtoupper($gender) === 'L'
            ? ['Pa', 'L']
            : ['Pi', 'P'];

        $dob = $tgllahir instanceof Carbon
            ? $tgllahir->format('Y-m-d')
            : (string) $tgllahir;

        return NiasExisting::query()
            ->whereRaw('LOWER(TRIM(NAMA)) = ?', [mb_strtolower(trim($nama))])
            ->whereIn('GENDER', $genderSet)
            ->whereDate('TGLLAHIR', $dob)
            ->select('ID', 'NONIAS', 'NAMA', 'GENDER', 'NAMACLUB', 'TGLLAHIR', 'EXPIRED')
            ->orderBy('NAMA')
            ->get();
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search');
        $jenis = $request->get('jenis'); // 'baru' | 'update'

        // 1. Logika Query untuk data yang BELUM dikirim
        // Jika admin, jangan filter berdasarkan user_id. Jika regular, filter milik sendiri.
        if ($user->role === 'admin') {
            $query = Nias::where('is_sent', false);
        } else {
            $query = Nias::where('user_id', $user->id)
                ->where('is_sent', false);
        }

        // Filter search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('NAMA', 'LIKE', "%{$search}%")
                    ->orWhere('NONIAS', 'LIKE', "%{$search}%")
                    ->orWhere('NAMACLUB', 'LIKE', "%{$search}%");
            });
        }

        // Filter jenis (baru/update)
        if ($jenis === 'baru') {
            $query->where('is_update', false);
        } elseif ($jenis === 'update') {
            $query->where('is_update', true);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(15, ['*'], 'records_page');

        // 2. Logika Query untuk data yang SUDAH dikirim
        if ($user->role === 'admin') {
            $sentQuery = Nias::where('is_sent', true);
        } else {
            $sentQuery = Nias::where('user_id', $user->id)
                ->where('is_sent', true);
        }

        $sentRecords = $sentQuery->orderBy('sent_at', 'desc')
            ->paginate(10, ['*'], 'sent_page');

        // Hitung total untuk tab badge — admin tanpa filter user_id
        $isAdmin    = $user->role === 'admin';
        $baseQuery  = $isAdmin ? Nias::where('is_sent', false) : Nias::where('user_id', $user->id)->where('is_sent', false);
        $totalSemua  = (clone $baseQuery)->count();
        $totalBaru   = (clone $baseQuery)->where('is_update', false)->count();
        $totalUpdate = (clone $baseQuery)->where('is_update', true)->count();

        $isNiasOpen = \App\Models\AppSetting::isNiasOpen();
        $tarifNias  = \App\Models\MstTarifNias::getAllTarif();
        $buktiPath  = $user->bukti_transfer_path ?? null;
        $hasBukti   = $buktiPath && Storage::disk('local')->exists($buktiPath);

        $financeSort = $request->input('finance_sort', 'date');
        $financeDir  = strtolower($request->input('finance_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $financeClub = $request->input('finance_club');
        $financeRole = $request->input('finance_role');

        $financialQuery = \App\Models\User::whereNotNull('bukti_transfer_path')
            ->where('bukti_transfer_path', '!=', '')
            ->where(function ($query) {
                $query->where('role', 'regular')->orWhere('role', 'admin');
            });

        if ($financeClub) {
            $financialQuery->where('namaclub', $financeClub);
        }

        if ($financeRole && in_array($financeRole, ['regular', 'admin'], true)) {
            $financialQuery->where('role', $financeRole);
        }

        if ($financeSort === 'club') {
            $financialQuery->orderBy('namaclub', $financeDir)
                ->orderBy('updated_at', $financeDir);
        } else {
            $financialQuery->orderBy('updated_at', $financeDir);
        }

        $financialRecords = $financialQuery->get()->filter(function ($u) {
            $path = $u->bukti_transfer_path ?? null;
            return $path && Storage::disk('local')->exists($path);
        });

        $financeClubOptions = \App\Models\User::query()
            ->whereIn('role', ['regular', 'admin'])
            ->whereNotNull('namaclub')
            ->where('namaclub', '!=', '')
            ->distinct()
            ->orderBy('namaclub', 'asc')
            ->pluck('namaclub');

        // ── Data untuk tab Daftar NIAS Baru (create form) ─────────
        $domisilis = array_keys(Nias::$domisiliLookup);
        sort($domisilis);
        $userClub = $user->namaclub;
        $allClubs = [];
        if ($user->role === 'admin') {
            $allClubs = array_keys(Nias::$clubLookup);
            sort($allClubs);
        }

        // ── Data untuk tab Update NIAS (update form) ──────────────
        $userRole   = $user->role;
        $expiredDate = now()->day(28)->addMonth()->addYears(2);

        $existingNias = NiasExisting::whereNotNull('NONIAS')
            ->select('NONIAS', 'NAMA', 'GENDER', 'TGLLAHIR', 'TPTLAHIR', 'NAMACLUB')
            ->orderBy('NAMA')
            ->get();

        $existingNames = NiasExisting::distinct()
            ->orderBy('NAMA')
            ->pluck('NAMA')
            ->toArray();

        $existingNiasMyClub = NiasExisting::whereNotNull('NONIAS')
            ->where('NAMACLUB', $userClub)
            ->select('NONIAS', 'NAMA', 'GENDER', 'TGLLAHIR', 'TPTLAHIR', 'NAMACLUB')
            ->orderBy('NAMA')
            ->get();

        $existingNamesMyClub = NiasExisting::distinct()
            ->where('NAMACLUB', $userClub)
            ->orderBy('NAMA')
            ->pluck('NAMA')
            ->toArray();

        return view('nias.index', compact(
            'records', 'sentRecords', 'totalSemua', 'totalBaru', 'totalUpdate',
            'isNiasOpen', 'tarifNias', 'hasBukti', 'financialRecords', 'financeSort', 'financeDir', 'financeClub', 'financeRole', 'financeClubOptions',
            'domisilis', 'userClub', 'allClubs', 'userRole', 'expiredDate',
            'existingNias', 'existingNames', 'existingNiasMyClub', 'existingNamesMyClub'
        ));
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------
    public function create()
    {
        $user = Auth::user();
        $domisilis = array_keys(Nias::$domisiliLookup);
        sort($domisilis);

        $userClub = $user->namaclub;

        // Inisialisasi daftar klub kosong
        $allClubs = [];

        // Jika admin, ambil semua kunci dari lookup club di Model Nias
        if ($user->role === 'admin') {
            $allClubs = array_keys(Nias::$clubLookup);
            sort($allClubs);
        }

        return view('nias.create', compact('domisilis', 'userClub', 'allClubs'));
    }

    // -------------------------------------------------------------------------
    // STORE  (digunakan untuk Daftar Baru DAN Update/Perpanjang)
    // -------------------------------------------------------------------------
    public function store(Request $request)
    {
        $isUpdate = (bool) $request->input('is_update', false);
        $user = Auth::user();

        $rules = [
            // Perpanjangan wajib memilih No. NIAS atlet existing
            'NONIAS' => $isUpdate ? 'nullable|required_if:tipe_update,perpanjangan|digits:14' : 'nullable|digits:14',
            'NAMA' => 'required|string|max:100',
            'GENDER' => 'required|in:L,P',
            'TGLLAHIR' => 'required|date|before:today',
            'TEMPATLAHIR' => 'required|string|max:100',
            'NIK' => 'nullable|digits:16',
            'EMAIL' => 'nullable|email|max:100',
            'NAMAKOTADOM' => ($isUpdate ? 'required_if:tipe_update,update_domisili,update_all' : 'required') . '|nullable|string|max:100',
            'JENISDOM' => 'nullable|string|max:10',
            'tipe_update' => $isUpdate ? 'required|in:perpanjangan,update_club,update_domisili,update_all' : 'nullable',
            'file_kk' => ($isUpdate ? 'required_if:tipe_update,update_domisili,update_all' : 'required') . '|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_foto' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_akte' => ($isUpdate ? 'nullable' : 'required') . '|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_ijazah' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_sk_mutasi' => ($isUpdate ? 'required_if:tipe_update,update_club,update_all' : 'nullable') . '|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mutasi_luar_jatim' => $isUpdate ? 'required_if:tipe_update,update_domisili,update_all|nullable|in:ya,tidak' : 'nullable',
        ];

        // ✅ Tambahan validasi NAMACLUB khusus Admin (karena Admin menggunakan Select dropdown)
        if ($user->role === 'admin') {
            $rules['NAMACLUB'] = 'required|string';
        }

        $validated = $request->validate($rules, [
            'NAMACLUB.required' => 'Nama Klub wajib dipilih.',
            'file_kk.required' => 'File Kartu Keluarga wajib diupload.',
            'file_foto.required' => 'File Foto wajib diupload.',
            'file_akte.required' => 'File Akte Lahir wajib diupload.',
            'file_sk_mutasi.required_if' => 'File SK Mutasi wajib diupload jika Anda merubah Club.',
            'file_kk.mimes' => 'File KK harus berformat PDF, JPG, atau PNG.',
            'file_foto.mimes' => 'File Foto harus berformat PDF, JPG, atau PNG.',
            'file_akte.mimes' => 'File Akte harus berformat PDF, JPG, atau PNG.',
            'file_ijazah.mimes' => 'File Ijazah harus berformat PDF, JPG, atau PNG.',
            'file_kk.max' => 'Ukuran file KK maksimal 5MB.',
            'file_foto.max' => 'Ukuran file Foto maksimal 5MB.',
            'file_akte.max' => 'Ukuran file Akte maksimal 5MB.',
            'file_ijazah.max' => 'Ukuran file Ijazah maksimal 5MB.',
            'NONIAS.digits' => 'No NIAS Jatim harus tepat 14 digit angka.',
            'NIK.digits' => 'NIK harus 16 digit angka.',
        ]);

        // ✅ LOGIKA PENENTUAN KLUB: Admin ambil dari input, Regular ambil dari data user
        if ($user->role === 'admin') {
            $namaclub = $validated['NAMACLUB'];
        } else {
            $namaclub = $user->namaclub;
        }

        $clubInfo = Nias::$clubLookup[$namaclub] ?? null;
        $clubCode = Nias::$clubCodeLookup[$namaclub] ?? null;
        $domInfo = !empty($validated['NAMAKOTADOM']) ? (Nias::$domisiliLookup[$validated['NAMAKOTADOM']] ?? null) : null;

        $today = Carbon::today();
        $expired = $today->copy()->day(28)->addMonth()->addYears(2);

        $folder = 'nias/' . Auth::id();
        $fileKk = $request->hasFile('file_kk') ? $request->file('file_kk')->store($folder, 'local') : null;
        $fileFoto = $request->hasFile('file_foto') ? $request->file('file_foto')->store($folder, 'local') : null;
        $fileAkte = $request->hasFile('file_akte') ? $request->file('file_akte')->store($folder, 'local') : null;
        $fileIjazah = $request->hasFile('file_ijazah') ? $request->file('file_ijazah')->store($folder, 'local') : null;
        $fileSkMutasi = $request->hasFile('file_sk_mutasi') ? $request->file('file_sk_mutasi')->store($folder, 'local') : null;

        // ── Guard PERPANJANGAN (server-side): cegah perpanjang terlalu awal ──
        // Berlaku hanya mode perpanjangan — mode mutasi (pindah club/KK) dilewati.
        if ($isUpdate && ($validated['tipe_update'] ?? null) === 'perpanjangan' && !empty($validated['NONIAS'])) {
            $athlete = NiasExisting::where('NONIAS', $validated['NONIAS'])->first();
            if ($athlete && $athlete->EXPIRED) {
                $expiredDate = Carbon::parse($athlete->EXPIRED);
                $remainingDays = (int) Carbon::today()->startOfDay()->diffInDays($expiredDate->copy()->startOfDay(), false);
                if ($remainingDays > self::PERPANJANG_EARLY_DAYS) {
                    return back()->withInput()->withErrors([
                        'NONIAS' => sprintf(
                            'NIAS atlet ini masih aktif sampai %s (sisa %d hari). Perpanjangan hanya bisa diajukan paling cepat %d hari sebelum masa berlaku habis.',
                            $expiredDate->format('d/m/Y'),
                            $remainingDays,
                            self::PERPANJANG_EARLY_DAYS
                        ),
                    ]);
                }
            }
        }

        // ── DETECT DUPLIKAT (hanya pendaftaran BARU, is_update=false) ──
        // Cek tabel master NIAS: LOWER(NAMA) + GENDER + TGLLAHIR cocok persis.
        // Jika cocok → has_possible_duplicate=true (status Caution), status default tetap pending.
        $hasPossibleDuplicate = false;
        if (!$isUpdate) {
            $hasPossibleDuplicate = self::possibleDuplicates(
                $validated['NAMA'],
                $validated['GENDER'],
                $validated['TGLLAHIR']
            )->isNotEmpty();
        }

        DB::transaction(function () use ($validated, $namaclub, $clubInfo, $clubCode, $domInfo, $today, $expired, $fileKk, $fileFoto, $fileAkte, $fileIjazah, $fileSkMutasi, $isUpdate, $hasPossibleDuplicate) {
            Nias::create([
                'user_id' => Auth::id(),
                'NONIAS' => $validated['NONIAS'] ?? null,
                'NAMA' => strtoupper(trim($validated['NAMA'])),
                'GENDER' => $validated['GENDER'],
                'TGLLAHIR' => $validated['TGLLAHIR'],
                'TEMPATLAHIR' => strtoupper(trim($validated['TEMPATLAHIR'])),
                'NIK' => $validated['NIK'] ?? null,
                'EMAIL' => $validated['EMAIL'] ?? null,
                'NAMACLUB' => $namaclub,
                'KDCLUB' => $clubCode,
                'KDJENIS' => $clubInfo[0] ?? null,
                'JENIS' => $clubInfo[1] ?? null,
                'KDKOTA' => $clubInfo[2] ?? null,
                'NAMAKOTA' => $clubInfo[3] ?? null,
                'KDJENISDOM' => $domInfo[0] ?? null,
                'JENISDOM' => $validated['JENISDOM'] ?? ($domInfo[1] ?? null),
                'KDPROPDOM' => '05',
                'NAMAPROPDOM' => 'JAWA TIMUR',
                'KDKOTADOM' => $domInfo[2] ?? null,
                'NAMAKOTADOM' => $this->stripWilayahPrefix($validated['NAMAKOTADOM'] ?? null) ?: ($validated['NAMAKOTADOM'] ?? null),
                'STATUS' => self::STATUS_PENDING, // 2 = pending acc
                'TGLDAFTAR' => $today->toDateString(),
                'TGLDAFTAR_UPDATE' => $isUpdate ? $today->toDateString() : null,
                'EXPIRED' => $expired->toDateString(),
                'LASTMUTASI' => $today->format('Ym'),
                'MUTASI' => $isUpdate ? 'P' : 'A',
                'is_update' => $isUpdate,
                'has_possible_duplicate' => $hasPossibleDuplicate,
                'file_kk' => $fileKk,
                'file_foto' => $fileFoto,
                'file_akte' => $fileAkte,
                'file_ijazah' => $fileIjazah,
                'tipe_update' => $validated['tipe_update'] ?? null,
                'file_sk_mutasi' => $fileSkMutasi,
                'mutasi_luar_jatim' => $validated['mutasi_luar_jatim'] ?? null,
            ]);
        });

        if ($isUpdate) {
            return redirect()->route('nias.index')
                ->with('success', 'Data Update Nias anda sudah berhasil tersimpan.');
        }

        return redirect()->route('nias.index')
            ->with('success', 'Data Pendaftaran Nias Baru anda sudah berhasil tersimpan.');
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------
    public function show($id)
    {
        $nias = Nias::findOrFail($id);
        $this->authorizeNias($nias);

        // Kandidat duplikat (rekomputasi saat halaman dibuka, hanya jika berflag Caution)
        $possibleDuplicates = $nias->has_possible_duplicate
            ? self::possibleDuplicates($nias->NAMA, $nias->GENDER, $nias->TGLLAHIR)
            : collect();

        return view('nias.show', compact('nias', 'possibleDuplicates'));
    }

    // -------------------------------------------------------------------------
    // RESOLVE DUPLICATE — keputusan admin atas data berstatus Caution
    // -------------------------------------------------------------------------
    public function resolveDuplicate(Request $request, $id)
    {
        $nias = Nias::findOrFail($id);
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Hanya admin yang dapat menyelesaikan status duplikat.');
        }

        $decision = $request->input('decision');
        if (!in_array($decision, ['not_duplicate', 'not_duplicate_acc', 'duplicate'], true)) {
            return back()->with('error', 'Keputusan duplikat tidak valid.');
        }

        if ($decision === 'duplicate') {
            // Option B: konfirmasi duplikat → batalkan (override status apapun)
            $nias->update([
                'STATUS' => self::STATUS_DIBATALKAN, // 4 = dibatalkan
                'has_possible_duplicate' => false,
            ]);
            return redirect()->back()
                ->with('error', "Data {$nias->NAMA} DIBATALKAN karena terkonfirmasi duplikat.");
        }

        // Option A: bukan duplikat → hapus flag, kembali ke status pending standar
        if ($decision === 'not_duplicate_acc') {
            $nias->update([
                'STATUS' => self::STATUS_DISETUJUI, // 1 = langsung disetujui
                'has_possible_duplicate' => false,
            ]);
            return redirect()->back()
                ->with('success', "Flag duplikat untuk {$nias->NAMA} dihapus. Data langsung DISETUJUI.");
        }

        $nias->update([
            'STATUS' => $nias->is_sent ? self::STATUS_TERKIRIM : self::STATUS_PENDING,
            'has_possible_duplicate' => false,
        ]);
        return redirect()->back()
            ->with('success', "Flag duplikat untuk {$nias->NAMA} dihapus. Status kembali menunggu proses (pending).");
    }

    // -------------------------------------------------------------------------
    // EDIT
    // -------------------------------------------------------------------------
    public function edit($id)
    {
        $nias = Nias::findOrFail($id);
        $this->authorizeNias($nias);

        $domisilis = array_keys(Nias::$domisiliLookup);
        sort($domisilis);
        $userClub = Auth::user()->namaclub;

        // $allClubs diperlukan di _form.blade.php untuk dropdown admin
        $allClubs = [];
        if (Auth::user()->role === 'admin') {
            $allClubs = array_keys(Nias::$clubLookup);
            sort($allClubs);
        }

        return view('nias.edit', compact('nias', 'domisilis', 'userClub', 'allClubs'));
    }

    // -------------------------------------------------------------------------
    // UPDATE (edit data yang sudah ada)
    // -------------------------------------------------------------------------
    public function update(Request $request, $id)
    {
        $nias = Nias::findOrFail($id);
        $this->authorizeNias($nias);

        $validated = $request->validate([
            'NAMA' => 'required|string|max:100',
            'GENDER' => 'required|in:L,P',
            'TGLLAHIR' => 'required|date|before:today',
            'TEMPATLAHIR' => 'required|string|max:100',
            'NIK' => 'nullable|digits:16',
            'EMAIL' => 'nullable|email|max:100',
            'NAMAKOTADOM' => 'required|string|max:100',
            'file_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_foto' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_akte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_ijazah' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $namaclub = Auth::user()->namaclub;
        $clubInfo = Nias::$clubLookup[$namaclub] ?? null;
        $clubCode = Nias::$clubCodeLookup[$namaclub] ?? null;
        $domInfo = !empty($validated['NAMAKOTADOM']) ? (Nias::$domisiliLookup[$validated['NAMAKOTADOM']] ?? null) : null;

        $folder = 'nias/' . Auth::id();

        $fileKk = $request->hasFile('file_kk')
            ? $request->file('file_kk')->store($folder, 'local')
            : $nias->file_kk;
        $fileFoto = $request->hasFile('file_foto')
            ? $request->file('file_foto')->store($folder, 'local')
            : $nias->file_foto;
        $fileAkte = $request->hasFile('file_akte')
            ? $request->file('file_akte')->store($folder, 'local')
            : $nias->file_akte;
        $fileIjazah = $request->hasFile('file_ijazah')
            ? $request->file('file_ijazah')->store($folder, 'local')
            : $nias->file_ijazah;

        $nias->update([
            'NAMA' => strtoupper(trim($validated['NAMA'])),
            'GENDER' => $validated['GENDER'],
            'TGLLAHIR' => $validated['TGLLAHIR'],
            'TEMPATLAHIR' => strtoupper(trim($validated['TEMPATLAHIR'])),
            'NIK' => $validated['NIK'] ?? null,
            'EMAIL' => $validated['EMAIL'] ?? null,
            'NAMACLUB' => $namaclub,
            'KDCLUB' => $clubCode,
            'KDJENIS' => $clubInfo[0] ?? null,
            'JENIS' => $clubInfo[1] ?? null,
            'KDKOTA' => $clubInfo[2] ?? null,
            'NAMAKOTA' => $clubInfo[3] ?? null,
            'KDJENISDOM' => $domInfo[0] ?? null,
            'JENISDOM' => $domInfo[1] ?? null,
            'KDKOTADOM' => $domInfo[2] ?? null,
            'NAMAKOTADOM' => $validated['NAMAKOTADOM'],
            'MUTASI' => 'P',
            'LASTMUTASI' => now()->format('Ym'),
            'file_kk' => $fileKk,
            'file_foto' => $fileFoto,
            'file_akte' => $fileAkte,
            'file_ijazah' => $fileIjazah,
        ]);

        // Jaga akurasi flag duplikat bila data registrasi BARU diubah admin/owner
        // (mis. nama salah ketik yang memicu false positive). Mode update tidak dicek.
        if (!$nias->is_update) {
            $recheck = self::possibleDuplicates($validated['NAMA'], $validated['GENDER'], $validated['TGLLAHIR']);
            if ($recheck->isNotEmpty() !== (bool) $nias->has_possible_duplicate) {
                $nias->update(['has_possible_duplicate' => $recheck->isNotEmpty()]);
            }
        }

        return redirect()->route('nias.show', $nias->ID)
            ->with('success', 'Data NIAS berhasil diperbarui.');
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------
    public function destroy($id)
    {
        $nias = Nias::findOrFail($id);
        $this->authorizeNias($nias);

        foreach (['file_kk', 'file_foto', 'file_akte', 'file_ijazah'] as $col) {
            if ($nias->$col)
                Storage::disk('local')->delete($nias->$col);
        }

        $nias->delete();

        return redirect()->route('nias.index')
            ->with('success', 'Data NIAS berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // SERVE FILE — tampilkan file dokumen (admin bisa lihat semua)
    // -------------------------------------------------------------------------
    public function serveFile($id, string $col)
    {
        $allowed = ['file_kk','file_foto','file_akte','file_ijazah','file_sk_mutasi'];
        if (!in_array($col, $allowed)) {
            abort(404);
        }

        $nias = Nias::findOrFail($id);
        $this->authorizeNias($nias);

        $path = $nias->$col;
        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->file(Storage::disk('local')->path($path));
    }

    // -------------------------------------------------------------------------
    // DESTROY SELECTED
    // -------------------------------------------------------------------------
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('nias.index')->with('error', 'Tidak ada data yang dipilih.');
        }

        $query = Nias::whereIn('ID', $ids);
        // Admin bisa hapus data siapapun, regular hanya milik sendiri
        if (Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }
        $records = $query->get();

        foreach ($records as $nias) {
            foreach (['file_kk', 'file_foto', 'file_akte', 'file_ijazah', 'file_sk_mutasi'] as $col) {
                if ($nias->$col)
                    Storage::disk('local')->delete($nias->$col);
            }
            $nias->delete();
        }

        return redirect()->route('nias.index')
            ->with('success', count($records) . ' data NIAS berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // DESTROY ALL
    // -------------------------------------------------------------------------
    public function destroyAll()
    {
        // Admin hapus semua, regular hanya milik sendiri
        $query = Auth::user()->role === 'admin'
            ? Nias::query()
            : Nias::where('user_id', Auth::id());

        $records = $query->get();

        foreach ($records as $nias) {
            foreach (['file_kk', 'file_foto', 'file_akte', 'file_ijazah', 'file_sk_mutasi'] as $col) {
                if ($nias->$col)
                    Storage::disk('local')->delete($nias->$col);
            }
            $nias->delete();
        }

        return redirect()->route('nias.index')
            ->with('success', 'Semua data NIAS (' . count($records) . ' data) berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // DESTROY SENT SELECTED — admin only
    // -------------------------------------------------------------------------
    public function destroySentSelected(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->route('nias.index')->with('error', 'Tidak ada data yang dipilih.');
        }

        $records = Nias::whereIn('ID', $ids)->where('is_sent', true)->get();

        foreach ($records as $nias) {
            foreach (['file_kk','file_foto','file_akte','file_ijazah','file_sk_mutasi'] as $col) {
                if ($nias->$col) Storage::disk('local')->delete($nias->$col);
            }
            $nias->delete();
        }

        return redirect()->route('nias.index')
            ->with('success', count($records) . ' data terkirim berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // DESTROY SENT ALL — admin only
    // -------------------------------------------------------------------------
    public function destroySentAll()
    {
        $records = Nias::where('is_sent', true)->get();

        foreach ($records as $nias) {
            foreach (['file_kk','file_foto','file_akte','file_ijazah','file_sk_mutasi'] as $col) {
                if ($nias->$col) Storage::disk('local')->delete($nias->$col);
            }
            $nias->delete();
        }

        return redirect()->route('nias.index')
            ->with('success', 'Semua data terkirim (' . count($records) . ' data) berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // ACC — Admin setujui data NIAS
    // -------------------------------------------------------------------------
    public function acc($id)
    {
        $nias = Nias::findOrFail($id);
        $nias->update(['STATUS' => 1]); // 1 = disetujui
        return redirect()->back()
            ->with('success', "Data {$nias->NAMA} berhasil di-ACC. Status menjadi DISETUJUI.");
    }

    // -------------------------------------------------------------------------
    // REJECT — Admin tolak data NIAS
    // -------------------------------------------------------------------------
    public function reject(Request $request, $id)
    {
        $nias = Nias::findOrFail($id);
        $nias->update(['STATUS' => 0]); // 0 = ditolak/expired
        $alasan = $request->input('alasan', '');
        $msg = "Data {$nias->NAMA} ditolak. Status menjadi DITOLAK.";
        if ($alasan) $msg .= " Alasan: {$alasan}";
        return redirect()->back()->with('error', $msg);
    }

    // -------------------------------------------------------------------------
    // HELPER
    // -------------------------------------------------------------------------
// Helper: strip prefix kota/kab dari nama wilayah untuk CSV
    private function stripWilayahPrefix(?string $nama): string
    {
        if (!$nama) return '';
        return trim(preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $nama));
    }

    private function authorizeNias(Nias $nias): void
    {
        // Admin bisa akses semua data tanpa filter user_id
        if (Auth::user()->role === 'admin') {
            return;
        }
        if ((int) $nias->user_id !== (int) Auth::id()) {
            abort(403, 'Kamu tidak punya akses ke data ini.');
        }
    }

    // -------------------------------------------------------------------------
    // EXPORT CSV (dipisah: Daftar Baru vs Update) + ZIP dokumen
    // -------------------------------------------------------------------------
    public function export()
    {
        // Hanya data BELUM dikirim
        $allRecords = Nias::where('user_id', Auth::id())
            ->where('is_sent', false)
            ->orderBy('NAMA')
            ->get();

        if ($allRecords->isEmpty()) {
            return back()->with('error', 'Tidak ada data yang belum dikirim untuk diekspor.');
        }

        $clubSlug = preg_replace('/[^A-Za-z0-9_]/', '_', Auth::user()->namaclub);
        $timestamp = now()->format('Ymd_His');
        $baseFilename = "DataNIAS_{$clubSlug}_{$timestamp}";

        // ── Buat satu CSV gabungan (Baru + Update) ─────────────────
        $tmpCsv = tempnam(sys_get_temp_dir(), 'nias_csv_');
        $out = fopen($tmpCsv, 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'NO',
            'Club',
            'NAMA LENGKAP ATLET',
            'SUB CABANG OLAHRAGA',
            'EMAIL',
            'DOMISILI [SESUAI KK/KTP]',
            '',
            '',
            'GENDER [Pa/Pi]',
            'TEMPAT LAHIR',
            'TGL LAHIR',
            'NIK',
            'STATUS NIAS [BARU / UPDATE]',
            'NO. NIAS JATIM (UPDATE)',
            'Daftar NIAS',
            'Jenis Daftar',
            'Keterangan',
        ], ';');
        fputcsv($out, [
            '',
            '',
            '',
            '',
            '',
            '[PROVINSI]',
            '[KOTA/KAB]',
            'NAMA KOTA/KAB',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ], ';');

        foreach ($allRecords as $i => $r) {
            fputcsv($out, [
                $i + 1,
                $r->NAMACLUB,
                $r->NAMA,
                'Finswimming',
                $r->EMAIL ?? '',
                ($r->mutasi_luar_jatim === 'ya') ? '' : 'Jawa Timur',
                $r->JENISDOM ?? '',
                $this->stripWilayahPrefix($r->NAMAKOTADOM),
                $r->GENDER === 'L' ? 'Pa' : 'Pi',
                $r->TEMPATLAHIR,
                $r->TGLLAHIR?->format('m/d/Y') ?? '',
                $r->NIK    ? "'" . $r->NIK    : '',
                $r->is_update ? 'UPDATE' : 'BARU',
                $r->NONIAS ? "'" . $r->NONIAS : '',
                'JTM',
                $r->tipe_update ?? '',
                '',
            ], ';');
        }
        fclose($out);

        // ── Buat ZIP ────────────────────────────────────────────────
        $tmpZip = tempnam(sys_get_temp_dir(), 'nias_zip_') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        $zip->addFile($tmpCsv, "{$baseFilename}.csv");

        // Masukkan dokumen tiap atlet (dari semua records)
        foreach ($allRecords as $i => $r) {
            $folderAtlet = ($i + 1) . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $r->NAMA);

            foreach (['file_kk' => 'KK', 'file_foto' => 'Foto', 'file_akte' => 'Akte', 'file_ijazah' => 'Ijazah'] as $col => $label) {
                if (!$r->$col)
                    continue;

                $storagePath = Storage::disk('local')->path($r->$col);
                if (!Storage::disk('local')->exists($r->$col))
                    continue;

                $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
                $namaFile = $label . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $r->NAMA) . '.' . $ext;
                $zip->addFile($storagePath, 'dokumen/' . $folderAtlet . '/' . $namaFile);
            }
        }

        $zip->close();

        // Hapus CSV sementara setelah ZIP ditutup
        register_shutdown_function(function () use ($tmpCsv) {
            @unlink($tmpCsv);
        });

        return response()->download($tmpZip, "{$baseFilename}.zip", [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // -------------------------------------------------------------------------
    // EXPORT BUKTI TRANSFER — ZIP semua file bukti transfer dengan ringkasan
    // -------------------------------------------------------------------------
    public function exportBuktiTransferZip(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Hanya admin yang dapat mengekspor bukti transfer.');
        }

        $query = \App\Models\User::query()
            ->whereIn('role', ['regular', 'admin'])
            ->whereNotNull('bukti_transfer_path')
            ->where('bukti_transfer_path', '!=', '');

        if ($club = $request->input('finance_club')) {
            $query->where('namaclub', $club);
        }

        if ($role = $request->input('finance_role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('updated_at', 'desc')->get();
        $eligibleUsers = $users->filter(function ($user) {
            $path = $user->bukti_transfer_path ?? null;
            return $path && Storage::disk('local')->exists($path);
        });

        if ($eligibleUsers->isEmpty()) {
            return redirect()->route('nias.index')->with('error', 'Tidak ada bukti transfer yang bisa diekspor dengan filter saat ini.');
        }

        $timestamp = now()->format('Ymd_His');
        $tmpZip = tempnam(sys_get_temp_dir(), 'bukti_zip_') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('nias.index')->with('error', 'Gagal membuat file ZIP bukti transfer.');
        }

        $summaryRows = [[
            'No',
            'Nama User',
            'Email',
            'Club',
            'Role',
            'Tanggal Upload',
            'Jumlah NIAS Baru',
            'Jumlah NIAS Update',
            'Total NIAS',
            'Nominal Transfer (Rp)',
            'File Bukti Transfer',
        ]];
        $tarifNias = \App\Models\MstTarifNias::getAllTarif();
        $tarifBaru = (int) ($tarifNias['baru'] ?? 60000);
        $tarifUpdate = (int) ($tarifNias['update'] ?? 30000);
        $totalNew = 0;
        $totalUpdate = 0;
        $totalAmount = 0;

        foreach ($eligibleUsers as $index => $user) {
            $buktiPath = $user->bukti_transfer_path;
            $storagePath = Storage::disk('local')->path($buktiPath);
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
            $clubSlug = preg_replace('/[^A-Za-z0-9_]/', '_', $user->namaclub ?? 'club');
            $safeName = sprintf('%02d_%s_%s', $index + 1, $clubSlug, preg_replace('/[^A-Za-z0-9_]/', '_', ($user->nama ?? 'user')));
            $zip->addFile($storagePath, 'bukti_transfer/' . $safeName . '.' . $ext);

            $newCount = \App\Models\Nias::where('user_id', $user->id)->where('is_update', false)->count();
            $updateCount = \App\Models\Nias::where('user_id', $user->id)->where('is_update', true)->count();
            $amount = ($newCount * $tarifBaru) + ($updateCount * $tarifUpdate);
            $totalNew += $newCount;
            $totalUpdate += $updateCount;
            $totalAmount += $amount;

            $summaryRows[] = [
                $index + 1,
                $user->nama ?? '-',
                $user->email ?? '-',
                $user->namaclub ?? '-',
                $user->role === 'admin' ? 'Admin' : 'Regular',
                $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : '-',
                $newCount,
                $updateCount,
                $newCount + $updateCount,
                $amount,
                $safeName . '.' . $ext,
            ];
        }

        $summaryRows[] = [];
        $summaryRows[] = ['SUMMARY', 'TOTAL FILTERED TRANSFERS', '', '', '', '', $totalNew, $totalUpdate, $totalNew + $totalUpdate, $totalAmount, ''];
        $summaryRows[] = ['SUMMARY', 'TARIF NIAS BARU', '', '', '', '', '', '', '', $tarifBaru, ''];
        $summaryRows[] = ['SUMMARY', 'TARIF NIAS UPDATE', '', '', '', '', '', '', '', $tarifUpdate, ''];

        $tmpCsv = tempnam(sys_get_temp_dir(), 'bukti_summary_');
        $out = fopen($tmpCsv, 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($summaryRows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);

        $zip->addFile($tmpCsv, 'ringkasan_bukti_transfer.csv');
        $zip->close();
        @unlink($tmpCsv);

        $filename = 'BuktiTransfer_' . now()->format('Ymd_His') . '.zip';

        return response()->download($tmpZip, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    // -------------------------------------------------------------------------
    // SEND EMAIL — Kirim ZIP ke it.possijatim@gmail.com
    // -------------------------------------------------------------------------
    public function sendEmail(Request $request)
    {
        $request->validate([
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        // Email user/pelatih yang sedang login untuk Cc
        $userEmail = $user->email;

        // Ambil hanya data yang belum dikirim untuk pengecekan awal
        // (data berstatus DIBATALKAN/duplikat tidak ikut dikirim)
        $records = Nias::where('user_id', $user->id)
            ->where('is_sent', false)
            ->where('STATUS', '!=', self::STATUS_DIBATALKAN)
            ->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'Tidak ada data baru untuk dikirim.');
        }

        // Hanya data belum dikirim untuk ZIP & CSV
        $allRecords = Nias::where('user_id', $user->id)
            ->where('is_sent', false)
            ->where('STATUS', '!=', self::STATUS_DIBATALKAN)
            ->orderBy('NAMA')
            ->get();

        if ($allRecords->isEmpty()) {
            return redirect()->route('nias.index')->with('error', 'Tidak ada data untuk dikirim.');
        }

        $namaclub = $user->namaclub;
        $clubSlug = preg_replace('/[^A-Za-z0-9_]/', '_', $namaclub);
        $timestamp = now()->format('Ymd_His');
        $baseFilename = "DataNIAS_{$clubSlug}_{$timestamp}";

        // ── Buat satu CSV gabungan (Baru + Update) ─────────────────
        $tmpCsv = tempnam(sys_get_temp_dir(), 'nias_csv_');
        $out = fopen($tmpCsv, 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'NO',
            'Club',
            'NAMA LENGKAP ATLET',
            'SUB CABANG OLAHRAGA',
            'EMAIL',
            'DOMISILI [SESUAI KK/KTP]',
            '',
            '',
            'GENDER [Pa/Pi]',
            'TEMPAT LAHIR',
            'TGL LAHIR',
            'NIK',
            'STATUS NIAS [BARU / UPDATE]',
            'NO. NIAS JATIM (UPDATE)',
            'Daftar NIAS',
            'Jenis Daftar',
            'Keterangan',
        ], ';');
        fputcsv($out, [
            '',
            '',
            '',
            '',
            '',
            '[PROVINSI]',
            '[KOTA/KAB]',
            'NAMA KOTA/KAB',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ], ';');

        foreach ($allRecords as $i => $r) {
            fputcsv($out, [
                $i + 1,
                $r->NAMACLUB,
                $r->NAMA,
                'Finswimming',
                $r->EMAIL ?? '',
                ($r->mutasi_luar_jatim === 'ya') ? '' : 'Jawa Timur',
                $r->JENISDOM ?? '',
                $this->stripWilayahPrefix($r->NAMAKOTADOM),
                $r->GENDER === 'L' ? 'Pa' : 'Pi',
                $r->TEMPATLAHIR,
                $r->TGLLAHIR?->format('m/d/Y') ?? '',
                $r->NIK    ? "'" . $r->NIK    : '',
                $r->is_update ? 'UPDATE' : 'BARU',
                $r->NONIAS ? "'" . $r->NONIAS : '',
                'JTM',
                $r->tipe_update ?? '',
                '',
            ], ';');
        }
        fclose($out);

        // ── Buat ZIP ───────────────────────────────────────────────
        $tmpZip = tempnam(sys_get_temp_dir(), 'nias_zip_') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('nias.index')->with('error', 'Gagal membuat file ZIP.');
        }

        $zip->addFile($tmpCsv, "{$baseFilename}.csv");

        foreach ($allRecords as $i => $r) {
            $folderAtlet = ($i + 1) . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $r->NAMA);
            $fileCols = [
                'file_kk' => 'KK',
                'file_foto' => 'Foto',
                'file_akte' => 'Akte',
                'file_ijazah' => 'Ijazah',
                'file_sk_mutasi' => 'SKMutasi'
            ];
            foreach ($fileCols as $col => $label) {
                if (!$r->$col || !\Storage::disk('local')->exists($r->$col))
                    continue;
                $storagePath = \Storage::disk('local')->path($r->$col);
                $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
                $namaFile = $label . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $r->NAMA) . '.' . $ext;
                $zip->addFile($storagePath, 'dokumen/' . $folderAtlet . '/' . $namaFile);
            }
        }

        // Sertakan bukti transfer jika ada
        $buktPath = $user->bukti_transfer_path ?? null;
        if ($buktPath && Storage::disk('local')->exists($buktPath)) {
            $buktExt = pathinfo(Storage::disk('local')->path($buktPath), PATHINFO_EXTENSION);
            $zip->addFile(Storage::disk('local')->path($buktPath), "BuktiTransfer_{$clubSlug}.{$buktExt}");
        }

        $zip->close();
        @unlink($tmpCsv);

        $keterangan = (string) $request->input('keterangan', '-');

        // ── Kirim Email dengan Cc ke Pelatih ───────────────────────
        try {
            // Alamat tujuan utama (Admin)
            $recipient = config('mail.nias_recipient', 'it.possijatim@gmail.com');

            \Mail::to($recipient)
                ->cc($userEmail) // Menambahkan Cc ke alamat email user yang sedang login
                ->send(new \App\Mail\NiasDataMail(
                    namaclub: $namaclub,
                    emailPelatih: $userEmail ?? '-',
                    jumlahBaru: $allRecords->where('is_update', false)->count(),
                    jumlahUpdate: $allRecords->where('is_update', true)->count(),
                    keterangan: $keterangan,
                    zipPath: $tmpZip,
                    zipFilename: "{$baseFilename}.zip",
                ));
        } catch (\Exception $e) {
            @unlink($tmpZip);
            return redirect()->route('nias.index')
                ->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }

        @unlink($tmpZip);

        // Tandai data sebagai sudah dikirim (kecuali yang DIBATALKAN/duplikat)
        Nias::where('user_id', $user->id)
            ->where('is_sent', false)
            ->where('STATUS', '!=', self::STATUS_DIBATALKAN)
            ->update([
                'is_sent' => true,
                'sent_at' => now(),
                'STATUS'  => self::STATUS_TERKIRIM, // 3 = sudah dikirim, menunggu acc
            ]);

        return redirect()->route('nias.index')
            ->with('success', "Data berhasil dikirim ke {$recipient} dan Cc ke {$userEmail}!");
    }

    // -------------------------------------------------------------------------
    // EXISTING — Data atlet dari tabel NIAS (database existing)
    // -------------------------------------------------------------------------
    public function existing(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $namaclub = $user->namaclub;

        $sortableColumns = ['NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS', 'JENISDOM', 'NAMAKOTADOM', 'EXPIRED'];
        $sortCol = in_array($request->sort, $sortableColumns) ? $request->sort : 'EXPIRED';
        $sortDir = $request->has('dir') ? ($request->dir === 'desc' ? 'desc' : 'asc') : 'desc';

        $query = NiasExisting::orderBy($sortCol, $sortDir)
            ->orderBy('NAMA', 'asc');

        // Admin: tampilkan semua, bisa filter by club via dropdown
        // User regular: filter by club sendiri
        if ($isAdmin) {
            $filterClub = $request->filled('club') ? $request->club : null;
            if ($filterClub) {
                $query->where('NAMACLUB', $filterClub);
            }
            $allClubs = NiasExisting::distinct()->orderBy('NAMACLUB')->pluck('NAMACLUB');
        } else {
            $query->where('NAMACLUB', $namaclub);
            $allClubs = collect();
            $filterClub = null;
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('NAMA', 'like', "%{$s}%")
                    ->orWhere('NONIAS', 'like', "%{$s}%");
            });
        }

        $records = $query->paginate(20)->withQueryString();

        return view('nias.existing', compact(
            'records',
            'namaclub',
            'sortCol',
            'sortDir',
            'isAdmin',
            'allClubs',
            'filterClub'
        ));
    }

    // -------------------------------------------------------------------------
    // EXISTING EXPORT CSV — Export data atlet existing dengan opsi filter/sort
    // -------------------------------------------------------------------------
    public function exportExisting(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $namaclub = $user->namaclub;

        // Format export: CSV atau XLSX (PhpSpreadsheet — gratis, lisensi MIT)
        $format = $request->get('format', 'xlsx');
        if (!in_array($format, ['csv', 'xlsx'])) {
            return back()->with('error', 'Format export tidak didukung.');
        }

        // Kolom sortable sama dengan halaman existing + tambahan untuk export
        $sortableColumns = [
            'NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS',
            'JENISDOM', 'NAMAKOTADOM', 'EXPIRED', 'NAMACLUB', 'TGLDAFTAR',
        ];
        $sortCol = in_array($request->sort, $sortableColumns) ? $request->sort : 'EXPIRED';
        $sortDir = $request->has('dir') ? ($request->dir === 'desc' ? 'desc' : 'asc') : 'desc';

        $query = NiasExisting::orderBy($sortCol, $sortDir)
            ->orderBy('NAMA', 'asc');

        // ── Filter dasar: club & search (mengikuti filter halaman) ──────────
        if ($isAdmin) {
            if ($request->filled('club')) {
                $query->where('NAMACLUB', $request->club);
            }
        } else {
            $query->where('NAMACLUB', $namaclub);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('NAMA', 'like', "%{$s}%")
                    ->orWhere('NONIAS', 'like', "%{$s}%");
            });
        }

        // ── Filter khusus export: status kadaluwarsa ────────────────────────
        // all     = semua (termasuk yang sudah expired)
        // active  = belum expired per hari ini
        // expired = sudah expired per hari ini
        // expiring= akan expired dalam N hari ke depan
        $today = Carbon::today()->toDateString();
        $expiredStatus = $request->get('expired_status', 'all');

        if ($expiredStatus === 'active') {
            $query->where(function ($q) use ($today) {
                $q->whereNull('EXPIRED')->orWhere('EXPIRED', '>=', $today);
            });
        } elseif ($expiredStatus === 'expired') {
            $query->whereNotNull('EXPIRED')->where('EXPIRED', '<', $today);
        } elseif ($expiredStatus === 'expiring') {
            $days = max(1, min((int) $request->get('expiring_days', 30), 3650));
            $cutoff = Carbon::today()->addDays($days)->toDateString();
            $query->whereNotNull('EXPIRED')
                ->where('EXPIRED', '>=', $today)
                ->where('EXPIRED', '<=', $cutoff);
        }

        // Rentang tanggal EXPIRED (dari–sampai)
        if ($request->filled('expired_from')) {
            $query->where('EXPIRED', '>=', $request->expired_from);
        }
        if ($request->filled('expired_to')) {
            $query->where('EXPIRED', '<=', $request->expired_to);
        }

        // Filter jenis kelamin
        if ($request->filled('gender') && in_array($request->gender, ['L', 'P'])) {
            $query->where('GENDER', $request->gender);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'Tidak ada data yang cocok dengan filter untuk diekspor.');
        }

        // ── Bangun data baris (dipakai untuk CSV maupun XLSX) ────────────────
        $scope = $isAdmin ? ($request->club ?: 'SemuaClub') : $namaclub;
        $scopeSlug = preg_replace('/[^A-Za-z0-9_]/', '_', $scope);

        $header = [
            'NO',
            'NAMA',
            'GENDER [L/P]',
            'TEMPAT LAHIR',
            'TGL LAHIR',
            'NIK',
            'EMAIL',
            'NO. NIAS JATIM',
            'CLUB',
            'JENIS DOM',
            'KOTA/KAB DOM',
            'TGL DAFTAR',
            'TGL KADALUWARSA',
            'STATUS',
        ];

        $rows = [];
        foreach ($records as $i => $r) {
            $expired = $r->EXPIRED ? Carbon::parse($r->EXPIRED) : null;
            $status = !$expired ? '' : ($expired->isPast() ? 'EXPIRED' : 'AKTIF');

            $rows[] = [
                $i + 1,
                $r->NAMA ?? '',
                $r->GENDER ?? '',
                $r->TPTLAHIR ?? $r->TEMPATLAHIR ?? '',
                $r->TGLLAHIR ? Carbon::parse($r->TGLLAHIR)->format('m/d/Y') : '',
                $r->NIK    ? "'" . $r->NIK    : '',
                $r->EMAIL ?? '',
                $r->NONIAS ? "'" . $r->NONIAS : '',
                $r->NAMACLUB ?? '',
                $r->JENISDOM ?? '',
                $this->stripWilayahPrefix($r->NAMAKOTADOM),
                $r->TGLDAFTAR ? Carbon::parse($r->TGLDAFTAR)->format('m/d/Y') : '',
                $expired ? $expired->format('m/d/Y') : '',
                $status,
            ];
        }

        if ($format === 'xlsx') {
            // ── Export XLSX (PhpSpreadsheet — gratis, tanpa biaya tambahan) ──
            $filename = "DataNIASExisting_{$scopeSlug}_" . now()->format('Ymd_His') . '.xlsx';
            $tmpXlsx = tempnam(sys_get_temp_dir(), 'nias_existing_xlsx_') . '.xlsx';

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray(array_merge([$header], $rows), null, 'A1');
            $sheet->getStyle('A1:N1')->getFont()->setBold(true);
            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tmpXlsx);
            $spreadsheet->disconnectWorksheets();

            return response()->download($tmpXlsx, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // ── Export CSV (delimiter ;, UTF-8 BOM, tanggal m/d/Y) ─────────────
        $filename = "DataNIASExisting_{$scopeSlug}_" . now()->format('Ymd_His') . '.csv';

        $tmpCsv = tempnam(sys_get_temp_dir(), 'nias_existing_csv_');
        $out = fopen($tmpCsv, 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($out, $header, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }
        fclose($out);

        return response()->download($tmpCsv, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }

    // -------------------------------------------------------------------------
    // SHOW UPDATE FORM
    // -------------------------------------------------------------------------
    public function showUpdateForm()
    {
        $user = Auth::user();
        $domisilis = array_keys(Nias::$domisiliLookup);
        sort($domisilis);

        $userClub = $user->namaclub;
        $userRole = $user->role;
        $expiredDate = now()->day(28)->addMonth()->addYears(2);

        // 1. Data NONIAS & NAMA untuk tipe yg butuh semua club (update_club, update_all)
        // EXPIRED diikutkan agar mode Perpanjangan bisa cek "masih aktif?" di frontend.
        $existingNias = NiasExisting::whereNotNull('NONIAS')
            ->select('NONIAS', 'NAMA', 'GENDER', 'TGLLAHIR', 'TPTLAHIR', 'NAMACLUB', 'EXPIRED')
            ->orderBy('NAMA')
            ->get();

        $existingNames = NiasExisting::distinct()
            ->orderBy('NAMA')
            ->pluck('NAMA')
            ->toArray();

        // 2. Data NONIAS & NAMA HANYA club sendiri (perpanjangan, update_domisili)
        $existingNiasMyClub = NiasExisting::whereNotNull('NONIAS')
            ->where('NAMACLUB', $userClub)
            ->select('NONIAS', 'NAMA', 'GENDER', 'TGLLAHIR', 'TPTLAHIR', 'NAMACLUB', 'EXPIRED')
            ->orderBy('NAMA')
            ->get();

        $existingNamesMyClub = NiasExisting::distinct()
            ->where('NAMACLUB', $userClub)
            ->orderBy('NAMA')
            ->pluck('NAMA')
            ->toArray();

        $allClubs = [];
        if ($userRole === 'admin') {
            $allClubs = array_keys(Nias::$clubLookup);
            sort($allClubs);
        }

        return view('nias.update_nias', compact(
            'domisilis',
            'userClub',
            'userRole',
            'expiredDate',
            'allClubs',
            'existingNias',
            'existingNames',
            'existingNiasMyClub',
            'existingNamesMyClub'
        ));
    }

    // -------------------------------------------------------------------------
    // UPLOAD BUKTI TRANSFER
    // -------------------------------------------------------------------------
    public function uploadBuktiTransfer(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'bukti_transfer' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'bukti_transfer.required' => 'File bukti transfer wajib dipilih.',
            'bukti_transfer.mimes'    => 'Format file harus PDF, JPG, atau PNG.',
            'bukti_transfer.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        $user     = Auth::user();
        $namaSlug = preg_replace('/[^A-Za-z0-9_]/', '_', strtoupper($user->nama));
        $ts       = now()->format('Ymd_Hi');
        $ext      = $request->file('bukti_transfer')->getClientOriginalExtension();
        $filename = "{$namaSlug}_{$ts}.{$ext}";

        // Hapus file lama jika ada
        if ($user->bukti_transfer_path && Storage::disk('local')->exists($user->bukti_transfer_path)) {
            Storage::disk('local')->delete($user->bukti_transfer_path);
        }

        $path = $request->file('bukti_transfer')->storeAs('bukti_transfer', $filename, 'local');
        $user->update(['bukti_transfer_path' => $path]);

        return redirect()->route('nias.index')->with('success', 'Bukti transfer berhasil diupload.');
    }

    // ── Serve bukti transfer untuk preview ───────────────────────
    public function serveBuktiTransfer($userId)
    {
        if (Auth::user()->role !== 'admin' && Auth::id() != $userId) abort(403);

        $targetUser = \App\Models\User::findOrFail($userId);
        $path       = $targetUser->bukti_transfer_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'File bukti transfer tidak ditemukan.');
        }

        return response()->file(Storage::disk('local')->path($path));
    }
}
