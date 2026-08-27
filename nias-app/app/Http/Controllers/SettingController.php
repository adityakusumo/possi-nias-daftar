<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\MstTarifNias;
use App\Models\Nias;
use App\Models\User;
use App\Models\Kompetisi;
use App\Models\MstDeposit;
use App\Models\MstDenda;
use App\Models\MstBiayaExtra;
use App\Models\LombaUser;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // ── Tampilkan halaman setting ─────────────────────────────────
    public function index()
    {
        // Setting NIAS
        $niasOpenDate  = AppSetting::get('nias_open_date');
        $niasCloseDate = AppSetting::get('nias_close_date');
        $maxAccountsJson = AppSetting::get('nias_max_accounts_per_club', '{}');
        $maxAccountsMap  = json_decode($maxAccountsJson, true) ?? [];

        // Daftar semua club dari lookup + jumlah akun aktif per club
        $allClubs = array_keys(Nias::$clubLookup);
        sort($allClubs);

        $clubStats = [];
        foreach ($allClubs as $club) {
            $clubStats[$club] = [
                'count' => User::where('namaclub', $club)->where('role', 'regular')->count(),
                'max'   => $maxAccountsMap[$club] ?? 2, // default 2
            ];
        }

        // Filter lomba accounts by current kompetisi setting
        $activeKompetisi = optional(Kompetisi::first())->JNSKOMPETISI;

        // Data lomba accounts (lomba_users)
        $lombaSearch = request('cari_lomba');
        $lombaUsers  = LombaUser::with('kontingen')
                                ->when($activeKompetisi, fn($q) => $q->whereHas('kontingen', fn($k) => $k->where('jns_kompetisi', $activeKompetisi)))
                                ->when($lombaSearch, fn($q) => $q->where(function($sq) use ($lombaSearch) {
                                    $sq->where('nama',  'like', "%{$lombaSearch}%")
                                       ->orWhere('email', 'like', "%{$lombaSearch}%");
                                }))
                                ->orderBy('email')
                                ->paginate(20, ['*'], 'lomba_page')
                                ->withQueryString();

        // Data untuk tab Lomba (NIAS users — kept for legacy reset)
        $search = request('cari');
        $users  = User::when($search, fn($q) => $q->where('nama', 'like', "%{$search}%")
                                                   ->orWhere('email', 'like', "%{$search}%"))
                      ->orderBy('nama')
                      ->paginate(20)
                      ->withQueryString();

        // Data untuk tab Akun — dengan sort
        $akunSortables = ['nama', 'namaclub', 'email', 'role', 'created_at', 'updated_at'];
        $akunSortCol   = in_array(request('sort_akun'), $akunSortables) ? request('sort_akun') : 'nama';
        $akunSortDir   = request('dir_akun') === 'desc' ? 'desc' : 'asc';
        $akunSearch    = request('cari');
        $akunUsers = User::when($akunSearch, fn($q) => $q->where('nama',     'like', "%{$akunSearch}%")
                                                          ->orWhere('email',    'like', "%{$akunSearch}%")
                                                          ->orWhere('namaclub', 'like', "%{$akunSearch}%"))
                         ->orderBy($akunSortCol, $akunSortDir)
                         ->paginate(20, ['*'], 'akun_page')
                         ->withQueryString();

        // Tarif NIAS
        $tarifNias = MstTarifNias::getAllTarif();

        // Data untuk tab Lomba — settings
        $kompetisi      = Kompetisi::first();
        $lombaTarifPerorangan = AppSetting::get('lomba_tarif_perorangan', '40000');
        $lombaTarifEstafet   = AppSetting::get('lomba_tarif_estafet', '200000');
        $depositRanges = MstDeposit::orderBy('JMLATLETMULAI')->get();
        $dendaData     = MstDenda::first();
        $biayaExtraList = MstBiayaExtra::orderBy('KETERANGAN')->get();

        return view('settings', compact(
            'niasOpenDate', 'niasCloseDate',
            'clubStats', 'allClubs',
            'users', 'akunUsers', 'lombaUsers',
            'tarifNias',
            'kompetisi', 'lombaTarifPerorangan', 'lombaTarifEstafet',
            'depositRanges', 'dendaData', 'biayaExtraList'
        ));
    }

    // ── Simpan setting jadwal & batas akun NIAS ───────────────────
    public function saveNias(Request $request)
    {
        $request->validate([
            'nias_open_date'  => 'nullable|date',
            'nias_close_date' => 'nullable|date|after_or_equal:nias_open_date',
            'biaya_baru'      => 'nullable|integer|min:0',
            'biaya_update'    => 'nullable|integer|min:0',
        ], [
            'nias_close_date.after_or_equal' => 'Tanggal tutup harus sama atau setelah tanggal buka.',
        ]);

        AppSetting::set('nias_open_date',  $request->nias_open_date);
        AppSetting::set('nias_close_date', $request->nias_close_date);

        // Simpan batas akun per club (field: max_accounts[NAMA CLUB])
        $map = json_decode(AppSetting::get('nias_max_accounts_per_club', '{}'), true) ?? [];
        foreach ($request->input('max_accounts', []) as $club => $val) {
            if ($val !== '' && $val !== null) {
                $map[$club] = (int) $val;
            }
        }
        AppSetting::set('nias_max_accounts_per_club', json_encode($map));

        // Simpan tarif NIAS (satu tombol simpan)
        if ($request->filled('biaya_baru')) {
            MstTarifNias::where('tipe', 'baru')->update(['biaya' => (int) $request->biaya_baru]);
        }
        if ($request->filled('biaya_update')) {
            MstTarifNias::where('tipe', 'update')->update(['biaya' => (int) $request->biaya_update]);
        }

        return redirect()->route('settings', ['tab' => 'nias'])->with('success', 'Setting NIAS berhasil disimpan.');
    }

    // ── Reset jadwal NIAS (tutup pendaftaran) ─────────────────────
    public function resetNiasSchedule()
    {
        AppSetting::set('nias_open_date',  null);
        AppSetting::set('nias_close_date', null);

        return redirect()->route('settings')->with('success', 'Jadwal NIAS direset. Pendaftaran sekarang tertutup.');
    }


    // ── Reset Password User ───────────────────────────────────────
    public function resetUserPassword(\App\Models\User $user)
    {
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make('Possi@1234')]);
        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', "Password {$user->nama} berhasil direset menjadi: Possi@1234");
    }

    // ── Hapus User ────────────────────────────────────────────────
    public function deleteUser(\App\Models\User $user)
    {
        if ($user->role === 'admin') {
            return redirect()->route('settings', ['tab' => 'lomba'])
                ->with('error', 'Akun admin tidak bisa dihapus.');
        }
        $user->delete();
        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', "Akun {$user->nama} berhasil dihapus.");
    }

    // ── Simpan tarif NIAS ────────────────────────────────────────
    public function saveTarifNias(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'biaya_baru'   => 'required|integer|min:0',
            'biaya_update' => 'required|integer|min:0',
        ]);

        MstTarifNias::where('tipe', 'baru')->update(['biaya'   => (int) $request->biaya_baru]);
        MstTarifNias::where('tipe', 'update')->update(['biaya' => (int) $request->biaya_update]);

        return redirect()->route('settings', ['tab' => 'nias'])->with('success', 'Tarif NIAS berhasil disimpan.');
    }

    // ── Detail akun (tab Akun) ────────────────────────────────────
    public function showAkun(User $user)
    {
        $statNias = [
            'total'    => \App\Models\Nias::where('user_id', $user->id)->count(),
            'pending'  => \App\Models\Nias::where('user_id', $user->id)->where('STATUS', 2)->count(),
            'terkirim' => \App\Models\Nias::where('user_id', $user->id)->where('is_sent', true)->count(),
        ];
        return view('setting_akun_show', compact('user', 'statNias'));
    }

    // ── Hapus akun terpilih (tab Akun) ───────────────────────────
    public function destroySelectedAkun(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->route('settings', ['tab' => 'akun'])->with('error', 'Tidak ada yang dipilih.');
        }
        $count = User::whereIn('id', $ids)->where('role', '!=', 'admin')->delete();
        return redirect()->route('settings', ['tab' => 'akun'])
            ->with('success', "{$count} akun berhasil dihapus.");
    }

    // ── Hapus semua akun non-admin (tab Akun) ────────────────────
    public function destroyAllAkun()
    {
        $count = User::where('role', '!=', 'admin')->delete();
        return redirect()->route('settings', ['tab' => 'akun'])
            ->with('success', "Semua {$count} akun regular berhasil dihapus.");
    }

    // ── Show/edit lomba user kontingen ────────────────────────────
    public function editLombaKontingen($id)
    {
        $lombaUser = LombaUser::with('kontingen')->findOrFail($id);

        // Data for dropdowns
        $rawKota = \App\Models\MstKota::orderBy('NAMAKOTA', 'asc')
            ->get(['KDJENIS', 'JENIS', 'NAMAKOTA']);
        $listKota = $rawKota
            ->reject(fn($k) => str_starts_with(strtoupper(trim($k->NAMAKOTA)), 'JAKARTA'))
            ->map(fn($k) => (object)[
                'value' => strtoupper(trim($k->JENIS)) . '|' . strtoupper(trim($k->NAMAKOTA)),
                'label' => strtoupper(trim($k->JENIS)) . ' ' . strtoupper(trim($k->NAMAKOTA)),
            ])->sortBy('label');

        $clubList = \App\Models\NiasExisting::select('NAMACLUB')
            ->whereNotNull('NAMACLUB')->where('NAMACLUB', '!=', '')
            ->distinct()->orderBy('NAMACLUB')->pluck('NAMACLUB');

        $provinsiList = [
            'ACEH', 'SUMATERA UTARA', 'SUMATERA BARAT', 'RIAU', 'JAMBI',
            'SUMATERA SELATAN', 'BENGKULU', 'LAMPUNG', 'KEP. BANGKA BELITUNG',
            'KEP. RIAU', 'DKI JAKARTA', 'JAWA BARAT', 'JAWA TENGAH',
            'DI YOGYAKARTA', 'JAWA TIMUR', 'BANTEN', 'BALI',
            'NUSA TENGGARA BARAT', 'NUSA TENGGARA TIMUR', 'KALIMANTAN BARAT',
            'KALIMANTAN TENGAH', 'KALIMANTAN SELATAN', 'KALIMANTAN TIMUR',
            'KALIMANTAN UTARA', 'SULAWESI UTARA', 'SULAWESI TENGAH',
            'SULAWESI SELATAN', 'SULAWESI TENGGARA', 'GORONTALO',
            'SULAWESI BARAT', 'MALUKU', 'MALUKU UTARA',
            'PAPUA', 'PAPUA BARAT', 'PAPUA SELATAN', 'PAPUA TENGAH',
            'PAPUA PEGUNUNGAN', 'PAPUA BARAT DAYA',
        ];

        $jnsKompetisi = optional(Kompetisi::first())->JNSKOMPETISI ?? 'K';

        return view('settings_lomba_edit', compact(
            'lombaUser', 'listKota', 'clubList', 'provinsiList', 'jnsKompetisi'
        ));
    }

    // ── Save lomba user kontingen ──────────────────────────────────
    public function updateLombaKontingen(Request $request, $id)
    {
        $lombaUser = LombaUser::with('kontingen')->findOrFail($id);
        $jnsKompetisi = Kompetisi::getJenis();

        $rules = ['nama' => 'nullable|string|max:100', 'no_wa' => 'nullable|string|max:20'];

        if ($jnsKompetisi === 'K') {
            $rules['kota_kab'] = 'required|string|max:100';
            $rules['provinsi'] = 'nullable|string|max:50';
        } elseif ($jnsKompetisi === 'C') {
            $rules['nama_kontingen'] = 'required|string|max:100';
        } else {
            $rules['provinsi'] = 'required|string|max:50';
        }

        $validated = $request->validate($rules);

        // Update lomba user name/phone
        $lombaUser->update([
            'nama'  => strtoupper(trim($validated['nama'] ?? $lombaUser->nama)),
            'no_wa' => trim($validated['no_wa'] ?? $lombaUser->no_wa),
        ]);

        // Build kontingen data
        $kontingenData = ['jns_kompetisi' => $jnsKompetisi];

        if ($jnsKompetisi === 'K') {
            $raw = strtoupper(trim($validated['kota_kab']));
            if (str_contains($raw, '|')) {
                $parts = explode('|', $raw, 2);
                $jenis = trim($parts[0] ?? 'KOTA');
                $kota  = trim($parts[1] ?? '');
            } else {
                $parts = explode(' ', $raw, 2);
                $jenis = trim($parts[0] ?? 'KOTA');
                $kota  = trim($parts[1] ?? $raw);
                if (!in_array($jenis, ['KOTA', 'KAB', 'KAB.', 'KABUPATEN'])) {
                    $kota  = $raw;
                    $jenis = 'KOTA';
                }
            }
            $sep = ($jenis === 'KAB' || $jenis === 'KAB.' || $jenis === 'KABUPATEN') ? '. ' : ' ';
            $jenisNorm = ($jenis === 'KAB.' || $jenis === 'KABUPATEN') ? 'KAB' : $jenis;
            $kontingenData['jenis_wilayah']  = $jenisNorm;
            $kontingenData['nama_wilayah']   = $kota;
            $kontingenData['nama_kontingen'] = $jenisNorm . $sep . $kota;
            $kontingenData['provinsi']       = strtoupper(trim($validated['provinsi'] ?? 'JAWA TIMUR'));
        } elseif ($jnsKompetisi === 'P') {
            $kontingenData['jenis_wilayah'] = 'PROP';
            $kontingenData['nama_wilayah']  = strtoupper(trim($validated['provinsi'] ?? 'JAWA TIMUR'));
            $kontingenData['provinsi']      = strtoupper(trim($validated['provinsi'] ?? 'JAWA TIMUR'));
            $kontingenData['nama_kontingen'] = strtoupper(trim($validated['nama_kontingen'] ?? ''));
        } else {
            $kontingenData['jenis_wilayah'] = null;
            $kontingenData['nama_wilayah']  = null;
            $kontingenData['provinsi']      = 'JAWA TIMUR';
            $kontingenData['nama_kontingen'] = strtoupper(trim($validated['nama_kontingen'] ?? ''));
        }

        \App\Models\Kontingen::updateOrCreate(
            ['lomba_user_id' => $lombaUser->id],
            $kontingenData
        );

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', "Kontingen untuk {$lombaUser->email} berhasil diperbarui.");
    }

    // ── Hapus lomba user ──────────────────────────────────────────
    public function deleteLombaUser($id)
    {
        $user = LombaUser::findOrFail($id);
        $email = $user->email;
        // Also clean up kontingen and tokens
        \App\Models\Kontingen::where('lomba_user_id', $id)->delete();
        \App\Models\LombaToken::where('email', $email)->delete();
        $user->delete();

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', "Akun lomba {$email} berhasil dihapus.");
    }

    // ── ── Lomba Settings ──────────────────────────────────────────

    public function saveLomba(Request $request)
    {
        $request->validate([
            'jns_kompetisi' => 'required|in:K,P,C',
            'wajib_nias'    => 'in:0,1',
            'ket_kompetisi' => 'nullable|string|max:255',
        ]);

        $kompetisi = Kompetisi::first();
        if ($kompetisi) {
            $kompetisi->update([
                'JNSKOMPETISI' => $request->jns_kompetisi,
                'KETKOMPETISI' => $request->ket_kompetisi ?? '',
                'WAJIBNIAS'    => $request->input('wajib_nias', '0'),
            ]);
        }

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', 'Setting Lomba (Jenis Kompetisi) berhasil disimpan.');
    }

    public function saveLombaTarif(Request $request)
    {
        $request->validate([
            'tarif_perorangan' => 'required|integer|min:0',
            'tarif_estafet'    => 'required|integer|min:0',
        ]);

        AppSetting::set('lomba_tarif_perorangan', (string) $request->tarif_perorangan);
        AppSetting::set('lomba_tarif_estafet',    (string) $request->tarif_estafet);

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', 'Tarif Lomba berhasil disimpan.');
    }

    public function saveLombaDeposit(Request $request)
    {
        $request->validate([
            'deposit.*.mulai'  => 'required|integer|min:1',
            'deposit.*.sampai' => 'required|integer|min:1',
            'deposit.*.rp'     => 'required|integer|min:0',
        ]);

        // Hapus semua data deposit lama, insert ulang
        MstDeposit::truncate();
        foreach ($request->deposit as $row) {
            if (!empty($row['mulai']) && !empty($row['sampai'])) {
                MstDeposit::create([
                    'JMLATLETMULAI' => (int) $row['mulai'],
                    'JMLATLETSAMPAI'=> (int) $row['sampai'],
                    'RPDEPOSIT'     => (int) $row['rp'],
                ]);
            }
        }

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', 'Deposit Lomba berhasil disimpan.');
    }

    public function saveLombaDenda(Request $request)
    {
        $request->validate([
            'rpdendaol'       => 'required|integer|min:0',
            'rpdendadq'       => 'required|integer|min:0',
            'rpdendanoswim'   => 'required|integer|min:0',
        ]);

        $denda = MstDenda::first();
        if ($denda) {
            $denda->update([
                'RPDENDAOL'     => (int) $request->rpdendaol,
                'RPDENDADQ'     => (int) $request->rpdendadq,
                'RPDENDANOSWIM' => (int) $request->rpdendanoswim,
            ]);
        } else {
            MstDenda::create([
                'RPDENDAOL'     => (int) $request->rpdendaol,
                'RPDENDADQ'     => (int) $request->rpdendadq,
                'RPDENDANOSWIM' => (int) $request->rpdendanoswim,
            ]);
        }

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', 'Denda Lomba berhasil disimpan.');
    }

    public function saveBiayaExtra(Request $request)
    {
        $request->validate([
            'biaya_extra.*.keterangan' => 'required|string|max:255',
            'biaya_extra.*.rp'         => 'required|integer|min:0',
        ]);

        // Replace all records
        MstBiayaExtra::truncate();
        foreach ($request->biaya_extra as $row) {
            if (!empty($row['keterangan'])) {
                MstBiayaExtra::create([
                    'KETERANGAN'   => $row['keterangan'],
                    'RPBIAYAEXTRA' => (int) $row['rp'],
                ]);
            }
        }

        return redirect()->route('settings', ['tab' => 'lomba'])
            ->with('success', 'Biaya Lain-lain berhasil disimpan.');
    }
}