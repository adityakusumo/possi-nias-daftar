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

class NiasController extends Controller
{
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

        return view('nias.index', compact('records', 'sentRecords'));
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
            'NONIAS' => 'nullable|digits:14',
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
        $expired = $today->copy()->day(28)->addYears(2);

        $folder = 'nias/' . Auth::id();
        $fileKk = $request->hasFile('file_kk') ? $request->file('file_kk')->store($folder, 'local') : null;
        $fileFoto = $request->hasFile('file_foto') ? $request->file('file_foto')->store($folder, 'local') : null;
        $fileAkte = $request->hasFile('file_akte') ? $request->file('file_akte')->store($folder, 'local') : null;
        $fileIjazah = $request->hasFile('file_ijazah') ? $request->file('file_ijazah')->store($folder, 'local') : null;
        $fileSkMutasi = $request->hasFile('file_sk_mutasi') ? $request->file('file_sk_mutasi')->store($folder, 'local') : null;

        DB::transaction(function () use ($validated, $namaclub, $clubInfo, $clubCode, $domInfo, $today, $expired, $fileKk, $fileFoto, $fileAkte, $fileIjazah, $fileSkMutasi, $isUpdate) {
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
                'JENISDOM' => $domInfo[1] ?? null,
                'KDPROPDOM' => '05',
                'NAMAPROPDOM' => 'JAWA TIMUR',
                'KDKOTADOM' => $domInfo[2] ?? null,
                'NAMAKOTADOM' => $validated['NAMAKOTADOM'],
                'STATUS' => 1,
                'TGLDAFTAR' => $today->toDateString(),
                'TGLDAFTAR_UPDATE' => $isUpdate ? $today->toDateString() : null,
                'EXPIRED' => $expired->toDateString(),
                'LASTMUTASI' => $today->format('Ym'),
                'MUTASI' => $isUpdate ? 'P' : 'A',
                'is_update' => $isUpdate,
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
                ->with('success', 'Update NIAS berhasil! Masa berlaku diperpanjang s/d: ' . $expired->format('d/m/Y'));
        }

        return redirect()->route('nias.index')
            ->with('success', 'Pendaftaran NIAS berhasil! Masa berlaku s/d: ' . $expired->format('d/m/Y'));
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------
    public function show($id)
    {
        $nias = Nias::findOrFail($id);
        $this->authorizeNias($nias);
        return view('nias.show', compact('nias'));
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

        return view('nias.edit', compact('nias', 'domisilis', 'userClub'));
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
    // DESTROY SELECTED
    // -------------------------------------------------------------------------
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('nias.index')->with('error', 'Tidak ada data yang dipilih.');
        }

        $records = Nias::whereIn('ID', $ids)
            ->where('user_id', Auth::id())
            ->get();

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
        $records = Nias::where('user_id', Auth::id())->get();

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
    // HELPER
    // -------------------------------------------------------------------------
    private function authorizeNias(Nias $nias): void
    {
        if ((int) $nias->user_id !== (int) Auth::id()) {
            abort(403, 'Kamu tidak punya akses ke data ini.');
        }
    }

    // -------------------------------------------------------------------------
    // EXPORT CSV (dipisah: Daftar Baru vs Update) + ZIP dokumen
    // -------------------------------------------------------------------------
    public function export()
    {
        $allRecords = Nias::where('user_id', Auth::id())
            ->orderBy('NAMA')
            ->get();

        $clubSlug = preg_replace('/[^A-Za-z0-9_]/', '_', Auth::user()->namaclub);
        $timestamp = now()->format('Ymd_His');
        $baseFilename = "DataNIAS_{$clubSlug}_{$timestamp}";

        // ── Buat satu CSV gabungan (Baru + Update) ─────────────────
        $tmpCsv = tempnam(sys_get_temp_dir(), 'nias_csv_');
        $out    = fopen($tmpCsv, 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'NO', 'Club', 'NAMA LENGKAP ATLET', 'SUB CABANG OLAHRAGA', 'EMAIL',
            'DOMISILI [SESUAI KK/KTP]', '', '',
            'GENDER [Pa/Pi]', 'TEMPAT LAHIR', 'TGL LAHIR', 'NIK',
            'STATUS NIAS [BARU / UPDATE]', 'NO. NIAS JATIM (UPDATE)', 'Daftar NIAS', 'Keterangan',
        ], ';');
        fputcsv($out, [
            '', '', '', '', '',
            '[PROVINSI]', '[KOTA/KAB]', 'NAMA KOTA/KAB',
            '', '', '', '', '', '', '', '',
        ], ';');

        foreach ($allRecords as $i => $r) {
            fputcsv($out, [
                $i + 1,
                $r->NAMACLUB,
                $r->NAMA,
                'Finswimming',
                $r->EMAIL ?? '',
                ($r->mutasi_luar_jatim === 'ya') ? '' : 'Jawa Timur',
                $r->JENISDOM    ?? '',
                $r->NAMAKOTADOM ?? '',
                $r->GENDER === 'L' ? 'Pa' : 'Pi',
                $r->TEMPATLAHIR,
                $r->TGLLAHIR?->format('d/m/Y') ?? '',
                $r->NIK ?? '',
                $r->is_update ? 'UPDATE' : 'BARU',
                $r->NONIAS ?? '',
                'JTM',
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
    // SEND EMAIL — Kirim ZIP ke it.possijatim@gmail.com
    // -------------------------------------------------------------------------
    public function sendEmail(Request $request)
    {
        $request->validate([
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Ambil hanya data yang belum dikirim
        $records = Nias::where('user_id', $user->id)
            ->where('is_sent', false)  // ← TAMBAHKAN KONDISI INI
            ->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'Tidak ada data baru untuk dikirim.');
        }

        $namaclub = $user->namaclub;

        $allRecords = Nias::where('user_id', Auth::id())->orderBy('NAMA')->get();

        if ($allRecords->isEmpty()) {
            return redirect()->route('nias.index')->with('error', 'Tidak ada data untuk dikirim.');
        }

        $clubSlug = preg_replace('/[^A-Za-z0-9_]/', '_', $namaclub);
        $timestamp = now()->format('Ymd_His');
        $baseFilename = "DataNIAS_{$clubSlug}_{$timestamp}";

        // ── Buat satu CSV gabungan (Baru + Update) ─────────────────
        $tmpCsv = tempnam(sys_get_temp_dir(), 'nias_csv_');
        $out    = fopen($tmpCsv, 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'NO', 'Club', 'NAMA LENGKAP ATLET', 'SUB CABANG OLAHRAGA', 'EMAIL',
            'DOMISILI [SESUAI KK/KTP]', '', '',
            'GENDER [Pa/Pi]', 'TEMPAT LAHIR', 'TGL LAHIR', 'NIK',
            'STATUS NIAS [BARU / UPDATE]', 'NO. NIAS JATIM (UPDATE)', 'Daftar NIAS', 'Keterangan',
        ], ';');
        fputcsv($out, [
            '', '', '', '', '',
            '[PROVINSI]', '[KOTA/KAB]', 'NAMA KOTA/KAB',
            '', '', '', '', '', '', '', '',
        ], ';');

        foreach ($allRecords as $i => $r) {
            fputcsv($out, [
                $i + 1,
                $r->NAMACLUB,
                $r->NAMA,
                'Finswimming',
                $r->EMAIL ?? '',
                ($r->mutasi_luar_jatim === 'ya') ? '' : 'Jawa Timur',
                $r->JENISDOM    ?? '',
                $r->NAMAKOTADOM ?? '',
                $r->GENDER === 'L' ? 'Pa' : 'Pi',
                $r->TEMPATLAHIR,
                $r->TGLLAHIR?->format('d/m/Y') ?? '',
                $r->NIK ?? '',
                $r->is_update ? 'UPDATE' : 'BARU',
                $r->NONIAS ?? '',
                'JTM',
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
                if (!$r->$col || !Storage::disk('local')->exists($r->$col))
                    continue;
                $storagePath = Storage::disk('local')->path($r->$col);
                $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
                $namaFile = $label . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $r->NAMA) . '.' . $ext;
                $zip->addFile($storagePath, 'dokumen/' . $folderAtlet . '/' . $namaFile);
            }
        }

        $zip->close();
        @unlink($tmpCsv);

        $keterangan = (string) $request->input('keterangan', '-');

        // ── Kirim Email ────────────────────────────────────────────
        try {
            Mail::to(config('mail.nias_recipient', 'it.possijatim@gmail.com'))
                ->send(new NiasDataMail(
                    namaclub: $namaclub,
                    emailPelatih: $user->email ?? '-',
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

        Nias::where('user_id', $user->id)
            ->where('is_sent', false)
            ->update([
                'is_sent' => true,
                'sent_at' => now(),
            ]);

        return redirect()->route('nias.index')
            ->with('success', 'Data berhasil dikirim ke it.possijatim@gmail.com!');
    }

    // -------------------------------------------------------------------------
    // EXISTING — Data atlet dari tabel NIAS (database existing)
    // -------------------------------------------------------------------------
    public function existing(Request $request)
    {
        $namaclub = Auth::user()->namaclub;

        // Kolom yang boleh di-sort
        $sortableColumns = ['NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS', 'EXPIRED'];
        $sortCol = in_array($request->sort, $sortableColumns) ? $request->sort : 'EXPIRED';
        $sortDir = $request->has('dir') ? ($request->dir === 'desc' ? 'desc' : 'asc') : 'desc';

        $query = NiasExisting::where('NAMACLUB', $namaclub)
            ->orderBy($sortCol, $sortDir)
            ->orderBy('NAMA', 'asc'); // secondary sort selalu nama asc

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('NAMA', 'like', "%{$s}%")
                    ->orWhere('NONIAS', 'like', "%{$s}%");
            });
        }

        $records = $query->paginate(20)->withQueryString();

        return view('nias.existing', compact('records', 'namaclub', 'sortCol', 'sortDir'));
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
        $expiredDate = now()->day(28)->addYears(2);

        // 1. Ambil semua NAMA unik (Tetap ambil semua agar pelatih bisa cari nama atlet)
        $existingNames = NiasExisting::distinct()->orderBy('NAMA')->pluck('NAMA');

        // 2. Ambil data No NIAS (Sertakan NAMACLUB untuk filter di view)
        $existingNias = NiasExisting::whereNotNull('NONIAS')
            ->select('NONIAS', 'NAMA', 'GENDER', 'TGLLAHIR', 'TPTLAHIR', 'NAMACLUB')
            ->get();

        $allClubs = [];
        if ($userRole === 'admin') {
            $allClubs = array_keys(Nias::$clubLookup);
            sort($allClubs);
        }

        return view('nias.update_nias', compact(
            'domisilis',
            'userClub',
            'userRole', // Kirim role ke view
            'expiredDate',
            'allClubs',
            'existingNames',
            'existingNias'
        ));
    }
}