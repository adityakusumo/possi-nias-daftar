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

        // Data untuk tab Lomba
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
            'users', 'akunUsers',
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
        ], [
            'nias_close_date.after_or_equal' => 'Tanggal tutup harus sama atau setelah tanggal buka.',
        ]);

        AppSetting::set('nias_open_date',  $request->nias_open_date);
        AppSetting::set('nias_close_date', $request->nias_close_date);

        // Simpan batas akun per club
        $allClubs = array_keys(Nias::$clubLookup);
        $map = [];
        foreach ($allClubs as $club) {
            $key = 'max_' . md5($club); // key unik per club di form
            if ($request->filled($key)) {
                $map[$club] = (int) $request->input($key);
            }
        }
        AppSetting::set('nias_max_accounts_per_club', json_encode($map));

        return redirect()->route('settings')->with('success', 'Setting NIAS berhasil disimpan.');
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