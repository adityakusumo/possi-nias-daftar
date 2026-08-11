<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kontingen;
use App\Models\LombaUser;
use App\Models\PesertaAtlet;
use App\Models\A3Entry;
use App\Models\PesertaEmail;
use App\Models\MstPeserta;
use App\Models\MstKu;
use App\Models\NolombaAktif;
use App\Models\MstEvent;
use App\Models\MstBiayaExtra;
use App\Models\MstDeposit;
use App\Models\MstDenda;
use App\Models\KwtDaftarDeposit;
use App\Models\Kompetisi;
use App\Mail\FormA3Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LombaController extends Controller
{
    // ── Helper: get current user (NIAS or Lomba) and its ID key ─
    private function resolveUser(): ?array
    {
        if (session()->has('lomba_user_id')) {
            $lombaUser = LombaUser::find(session('lomba_user_id'));
            if ($lombaUser) {
                return [
                    'user'    => $lombaUser,
                    'id_col'  => 'lomba_user_id',
                    'user_id' => $lombaUser->id,
                ];
            }
        }
        if (auth()->check()) {
            return [
                'user'    => auth()->user(),
                'id_col'  => 'user_id',
                'user_id' => auth()->id(),
            ];
        }
        return null;
    }

    private function buildAsal($kontingen): string
    {
        if ($kontingen->jns_kompetisi === 'C') {
            return strtoupper(trim($kontingen->nama_kontingen));
        } elseif ($kontingen->jns_kompetisi === 'P') {
            return strtoupper(trim($kontingen->provinsi));
        }
        $jenis = trim($kontingen->jenis_wilayah);
        $kota = trim($kontingen->nama_wilayah);
        $sep = (strtoupper($jenis) === 'KAB') ? '. ' : ' ';
        return strtoupper($jenis . $sep . $kota);
    }

    private function buildNamaClub($kontingen): string
    {
        // NAMACLUB = same as ASAL in VB6
        return $this->buildAsal($kontingen);
    }

    private function getJenisDom($kontingen): string
    {
        if ($kontingen->jns_kompetisi === 'P') return 'PROP';
        if ($kontingen->jns_kompetisi === 'C') return strtoupper(trim($kontingen->jenis_wilayah ?? 'KOTA'));
        return strtoupper(trim($kontingen->jenis_wilayah ?? 'KOTA'));
    }

    private function getKotaDom($kontingen): string
    {
        if ($kontingen->jns_kompetisi === 'P') return strtoupper(trim($kontingen->provinsi));
        return strtoupper(trim($kontingen->nama_wilayah ?? $kontingen->provinsi));
    }

    // ── Sync kontingens → MstPeserta ────────────────────────────
    private function syncToMstPeserta($kontingen)
    {
        $asal = $this->buildAsal($kontingen);
        $namaClub = $this->buildNamaClub($kontingen);
        $jenisDom = $this->getJenisDom($kontingen);
        $namaKotaDom = $this->getKotaDom($kontingen);

        MstPeserta::updateOrCreate(
            ['ASAL' => $asal],
            [
                'NAMACLUB'     => $namaClub,
                'JENISDOM'     => $jenisDom,
                'NAMAKOTADOM'  => $namaKotaDom,
                'NAMAPROPDOM'  => strtoupper(trim($kontingen->provinsi)),
                'NAMANEGDOM'   => 'INDONESIA',
                'email'        => $kontingen->email ?? null,
            ]
        );
    }

    // ── KU calculation from birth date ──────────────────────────
    private function calculateKu($tglLahir)
    {
        $kuList = MstKu::all();
        foreach ($kuList as $ku) {
            $lahir = \Carbon\Carbon::parse($tglLahir);
            $mulai = $ku->LAHIRMULAI ? \Carbon\Carbon::parse($ku->LAHIRMULAI) : null;
            $sampai = $ku->LAHIRSAMPAI ? \Carbon\Carbon::parse($ku->LAHIRSAMPAI) : null;
            if ($mulai && $sampai && $lahir->between($mulai, $sampai)) {
                return $ku->KU;
            }
        }
        return null;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index()
    {
        $resolved = $this->resolveUser();
        $userName = null;
        $userClub = null;
        $userEmail = null;

        if ($resolved) {
            if ($resolved['id_col'] === 'lomba_user_id') {
                $lu = \App\Models\LombaUser::find($resolved['user_id']);
                if ($lu) {
                    $userName = $lu->nama;
                    $userEmail = $lu->email;
                    // Try to get club from kontingen
                    $kontingen = \App\Models\Kontingen::where('lomba_user_id', $lu->id)->first();
                    if ($kontingen) $userClub = $kontingen->nama_kontingen;
                }
            } else {
                $user = auth()->user();
                if ($user) {
                    $userName = $user->nama;
                    $userClub = $user->namaclub;
                    $userEmail = $user->email;
                }
            }
        }

        return view('lomba.index', compact('userName', 'userClub', 'userEmail'));
    }

    // ── Form A1 (Entri Kontingen) ─────────────────────────────────
    public function formA1()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        }
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        $listKota = \App\Models\MstKota::orderBy('NAMAKOTA', 'asc')->get();
        $isKontingenSaved = $kontingen ? true : false;

        // Club → JENIS|NAMAKOTA lookup for antar-club auto-fill
        $clubLookup = DB::table('NIAS')
            ->whereNotNull('NAMACLUB')
            ->where('NAMACLUB', '!=', '')
            ->select('NAMACLUB', 'JENIS', 'NAMAKOTA')
            ->distinct()
            ->get()
            ->groupBy('NAMACLUB')
            ->map(function ($rows) {
                // Take first match per club
                $r = $rows->first();
                return strtoupper(trim($r->JENIS)) . '|' . strtoupper(trim($r->NAMAKOTA));
            });

        return view('lomba.form_a1_kontingen', compact('kontingen', 'listKota', 'isKontingenSaved', 'clubLookup'));
    }

    // ── Form Nama Atlet ───────────────────────────────────────────
    public function formA1NamaAtlet()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        }
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) {
            return redirect()->route('lomba.form_a1')->with('error', 'Isi data kontingen dulu.');
        }
        $asal = $this->buildAsal($kontingen);
        $atletList = PesertaAtlet::where('ASAL', $asal)->orderBy('NAMAATLET', 'asc')->get();
        $kuList = MstKu::all();
        $kompetisi = Kompetisi::first();

        // NIAS athletes filtered by kompetisi type (including expired, for table display)
        $niasQuery = DB::table('NIAS')
            ->whereNotNull('NAMA')
            ->where('NAMA', '!=', '')
            ->select('NAMA', 'GENDER', 'TGLLAHIR', 'NONIAS', 'EXPIRED', 'NAMACLUB', 'JENISDOM', 'NAMAKOTADOM', 'NAMAPROPDOM');

        if ($kontingen->jns_kompetisi === 'C') {
            $niasQuery->whereRaw('UPPER(TRIM(NAMACLUB)) = ?', [strtoupper(trim($kontingen->nama_kontingen))]);
        } elseif ($kontingen->jns_kompetisi === 'K') {
            $niasQuery->whereRaw('UPPER(TRIM(JENISDOM)) = ?', [strtoupper(trim($kontingen->jenis_wilayah))])
                      ->whereRaw('UPPER(TRIM(NAMAKOTADOM)) = ?', [strtoupper(trim($kontingen->nama_wilayah))]);
        } elseif ($kontingen->jns_kompetisi === 'P') {
            $niasQuery->whereRaw('UPPER(TRIM(NAMAPROPDOM)) = ?', [strtoupper(trim($kontingen->provinsi))]);
        }

        $allNiasAtlets = $niasQuery->distinct()
            ->orderBy('NAMA')
            ->get()
            ->unique('NAMA')
            ->values()
            ->map(function ($a) {
                $a->is_expired = $a->EXPIRED
                    && $a->EXPIRED !== '0000-00-00'
                    && \Carbon\Carbon::parse($a->EXPIRED)->isBefore(now()->startOfDay());
                return $a;
            });

        // Datalist: only active (non-expired) athletes
        $niasAtlets = $allNiasAtlets->reject(fn($a) => $a->is_expired);

        return view('lomba.form_a1_namaatlet', compact('kontingen', 'atletList', 'kuList', 'kompetisi', 'niasAtlets', 'allNiasAtlets'));
    }

    // ── Save Kontingen ────────────────────────────────────────────
    public function saveKontingen(Request $request)
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        }
        $request->validate([
            'jnsKompetisi'   => 'required|in:K,P',
            'nama_kontingen' => 'required|string',
            'jenis'          => 'required_if:jnsKompetisi,K',
            'nama_wilayah'   => 'required_if:jnsKompetisi,K',
            'provinsi'       => 'required',
        ]);
        $kontingen = Kontingen::updateOrCreate(
            [$resolved['id_col'] => $resolved['user_id']],
            [
                'jns_kompetisi'  => $request->jnsKompetisi,
                'nama_kontingen' => strtoupper($request->nama_kontingen),
                'jenis_wilayah'  => strtoupper($request->jenis),
                'nama_wilayah'   => strtoupper($request->nama_wilayah),
                'provinsi'       => strtoupper($request->provinsi),
            ]
        );
        // Sync to MstPeserta for VB6 compatibility
        $this->syncToMstPeserta($kontingen);
        return redirect()->route('lomba.form_a1_namaatlet')
            ->with('success', 'Data Kontingen berhasil disimpan. Sekarang silakan isi daftar atlet.');
    }

    // ── Add Atlet (AJAX or form POST) ────────────────────────────
    public function addAtlet(Request $request)
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) {
            return response()->json(['error' => 'Kontingen not found'], 404);
        }
        $request->validate([
            'nama_atlet' => 'required|string|max:50',
            'gender'     => 'required|in:Pa,Pi',
            'tgl_lahir'  => 'required|date',
            'nonias'     => 'nullable|string|max:20',
        ]);
        $asal = $this->buildAsal($kontingen);
        $ku = $this->calculateKu($request->tgl_lahir);
        $sp = '0';
        // If wajib NIAS and no NIAS, set SP=1
        if (Kompetisi::isWajibNias() && empty($request->nonias)) {
            $sp = '1';
        }
        $jenisDom = $this->getJenisDom($kontingen);
        $kotaDom = $this->getKotaDom($kontingen);
        PesertaAtlet::create([
            'NAMAATLET'    => strtoupper(trim($request->nama_atlet)),
            'ASAL'         => $asal,
            'NAMACLUB'     => $this->buildNamaClub($kontingen),
            'JENISDOM'     => $jenisDom,
            'NAMAKOTADOM'  => $kotaDom,
            'NAMAPROPDOM'  => strtoupper(trim($kontingen->provinsi)),
            'GENDER'       => $request->gender,
            'KU'           => $ku,
            'SP'           => $sp,
            'NONIAS'       => $request->nonias ?? '',
            'TGLLAHIR'     => $request->tgl_lahir,
            'created_by'   => $resolved['user_id'],
        ]);
        return redirect()->route('lomba.form_a1_namaatlet')
            ->with('success', 'Atlet ' . $request->nama_atlet . ' berhasil ditambahkan.');
    }

    public function updateAtlet(Request $request, $id)
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate([
            'nama_atlet' => 'required|string|max:50',
            'gender'     => 'required|in:Pa,Pi',
            'tgl_lahir'  => 'required|date',
            'nonias'     => 'nullable|string|max:20',
        ]);
        $atlet = PesertaAtlet::findOrFail($id);
        $ku = $this->calculateKu($request->tgl_lahir);
        $sp = '0';
        if (Kompetisi::isWajibNias() && empty($request->nonias)) {
            $sp = '1';
        }
        $atlet->update([
            'NAMAATLET'  => strtoupper(trim($request->nama_atlet)),
            'GENDER'     => $request->gender,
            'KU'         => $ku,
            'SP'         => $sp,
            'NONIAS'     => $request->nonias ?? '',
            'TGLLAHIR'   => $request->tgl_lahir,
            'updated_by' => $resolved['user_id'],
        ]);
        return redirect()->route('lomba.form_a1_namaatlet')
            ->with('success', 'Data atlet berhasil diperbarui.');
    }

    public function deleteAtlet($id)
    {
        $atlet = PesertaAtlet::findOrFail($id);
        A3Entry::where('ASAL', $atlet->ASAL)
            ->where('NAMAATLET', $atlet->NAMAATLET)
            ->delete();
        $atlet->delete();
        return redirect()->back()->with('success', 'Atlet berhasil dihapus.');
    }

    // ── API: Atlet list for Select2 ─────────────────────────────
    public function apiAtletList()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return response()->json([], 403);
        }
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return response()->json([]);
        $asal = $this->buildAsal($kontingen);
        $atlets = PesertaAtlet::where('ASAL', $asal)->orderBy('NAMAATLET')->get();
        return response()->json($atlets->map(function ($a) {
            return [
                'id'       => $a->IDATLET,
                'text'     => $a->NAMAATLET,
                'gender'   => $a->GENDER,
                'ku'       => $a->KU,
                'nias'     => $a->NONIAS,
                'tglLahir' => $a->TGLLAHIR ? $a->TGLLAHIR->format('d/m/Y') : '',
            ];
        }));
    }

    // ── Form A3 Perorangan ─────────────────────────────────────────
    public function formA3Perorangan()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) {
            return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        }
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) {
            return redirect()->route('lomba.form_a1')->with('error', 'Isi data kontingen dulu.');
        }
        $asal = $this->buildAsal($kontingen);
        $atletList = PesertaAtlet::where('ASAL', $asal)->orderBy('NAMAATLET')->get();
        $activeStyles = NolombaAktif::where('AKTIF', 'A')->where('KATEGORI', 'Perorangan')->get();
        $kuList = MstKu::all();
        $a3Entries = A3Entry::where('ASAL', $asal)->where(function ($q) {
            $q->whereNull('NOMOR')->orWhere('NOMOR', 'Perorangan');
        })->orderBy('NAMAATLET')->get();
        // Build KU → gaya mapping from tSyaratPrestasi (distinct GAYA per KU)
        $syaratRows = DB::table('tSyaratPrestasi')
            ->select('GAYA', 'KU')
            ->distinct()
            ->orderBy('KU')
            ->orderBy('GAYA')
            ->get();
        $gayaByKu = [];
        foreach ($syaratRows as $r) {
            $gayaByKu[$r->KU][] = $r->GAYA;
        }
        return view('lomba.form_a3_perorangan', compact('kontingen', 'atletList', 'activeStyles', 'kuList', 'a3Entries', 'gayaByKu'));
    }

    public function saveA3Perorangan(Request $request)
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->back()->with('error', 'Unauthorized.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->back()->with('error', 'Kontingen not found.');
        $asal = $this->buildAsal($kontingen);
        $request->validate([
            'atlet_id' => 'required|integer',
            'sp'       => 'in:0,1',
        ]);
        $atlet = PesertaAtlet::findOrFail($request->atlet_id);
        // Collect all style prefix groups that have at least one MM/SS/HS value
        $stylePrefixes = ['MON', 'SUB', 'APN', 'IMM'];
        $distances = [
            'MON' => [50, 100, 200, 400, 800, 1500],
            'SUB' => [50, 100, 200, 400],
            'APN' => [50],
            'IMM' => [100, 400, 800],
        ];
        // Check if entry already exists for this athlete
        $existing = A3Entry::where('ASAL', $asal)
            ->where('NAMAATLET', $atlet->NAMAATLET)
            ->where(function ($q) {
                $q->whereNull('NOMOR')->orWhere('NOMOR', 'Perorangan');
            })
            ->first();
        $data = [
            'GENDER'     => $atlet->GENDER,
            'KU'         => $atlet->KU,
            'NAMAATLET'  => $atlet->NAMAATLET,
            'ASAL'       => $asal,
            'NAMACLUB'   => $this->buildNamaClub($kontingen),
            'JENISDOM'   => strtoupper(trim($kontingen->jenis_wilayah ?? 'KOTA')),
            'NAMAKOTADOM'=> strtoupper(trim($kontingen->nama_wilayah ?? $kontingen->provinsi)),
            'NAMAPROPDOM'=> strtoupper(trim($kontingen->provinsi)),
            'SP'         => $request->input('sp', $atlet->SP ?? '0'),
            'TGLLAHIR'   => $atlet->TGLLAHIR,
            'NOMOR'      => 'Perorangan',
        ];
        $hasAnyTime = false;
        foreach ($stylePrefixes as $prefix) {
            foreach ($distances[$prefix] as $dist) {
                $mmField = $prefix . $dist . 'MM';
                $ssField = $prefix . $dist . 'SS';
                $hsField = $prefix . $dist . 'HS';
                $mm = $request->input($mmField, '');
                $ss = $request->input($ssField, '');
                $hs = $request->input($hsField, '');
                // Pad to 2 digits
                $data[$mmField] = $mm !== '' ? str_pad($mm, 2, '0', STR_PAD_LEFT) : '';
                $data[$ssField] = $ss !== '' ? str_pad($ss, 2, '0', STR_PAD_LEFT) : '';
                $data[$hsField] = $hs !== '' ? str_pad($hs, 2, '0', STR_PAD_LEFT) : '';
                if ($ss !== '') $hasAnyTime = true;
            }
        }
        if (!$hasAnyTime) {
            return redirect()->back()->with('error', 'Isi minimal satu nomor lomba (waktu second/detik).');
        }
        if ($existing) {
            // Merge: only overwrite fields that were actually submitted
            // Keep existing values for styles that weren't submitted
            foreach ($data as $key => $value) {
                if ($request->has($key)) {
                    $existing->$key = $value;
                }
            }
            // Always update the metadata fields
            $existing->GENDER = $data['GENDER'];
            $existing->KU = $data['KU'];
            $existing->SP = $data['SP'];
            $existing->NOMOR = $data['NOMOR'];
            $existing->save();
        } else {
            A3Entry::create($data);
        }
        return redirect()->back()->withInput()->with('success', 'Data A3 Perorangan untuk ' . $atlet->NAMAATLET . ' berhasil disimpan.');
    }

    // ── Form A3 Estafet ────────────────────────────────────────────
    public function formA3Estafet()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->route('lomba.form_a1')->with('error', 'Isi data kontingen dulu.');
        $asal = $this->buildAsal($kontingen);
        $activeStyles = NolombaAktif::where('AKTIF', 'A')->where('KATEGORI', 'Estafet')->get();
        $kuList = MstKu::all();
        $a3Entries = A3Entry::where('ASAL', $asal)->where('NOMOR', 'Estafet')->orderBy('NAMAATLET')->get();
        // Build KU → gaya mapping from tSyaratPrestasi for estafet only
        $syaratRows = DB::table('tSyaratPrestasi')
            ->select('GAYA', 'KU')
            ->distinct()
            ->where('GAYA', 'LIKE', '%estafet%')
            ->orderBy('KU')
            ->orderBy('GAYA')
            ->get();
        $gayaByKu = [];
        foreach ($syaratRows as $r) {
            $gayaByKu[$r->KU][] = $r->GAYA;
        }
        return view('lomba.form_a3_estafet', compact('kontingen', 'activeStyles', 'kuList', 'a3Entries', 'gayaByKu'));
    }

    public function saveA3Estafet(Request $request)
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->back()->with('error', 'Unauthorized.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->back()->with('error', 'Kontingen not found.');
        $asal = $this->buildAsal($kontingen);

        $request->validate([
            'nama_team' => 'required|string|max:50',
            'ku'        => 'required|string|max:10',
            'gender'    => 'required|in:Pa,Pi,Mix',
            'sp'        => 'in:0,1',
        ]);

        $teamName = strtoupper(trim($request->nama_team));

        $existing = A3Entry::where('ASAL', $asal)
            ->where('NAMAATLET', $teamName)
            ->where('NOMOR', 'Estafet')
            ->first();

        $data = [
            'GENDER'     => $request->gender,
            'KU'         => strtoupper(trim($request->ku)),
            'NAMAATLET'  => $teamName,
            'ASAL'       => $asal,
            'NAMACLUB'   => $this->buildNamaClub($kontingen),
            'JENISDOM'   => strtoupper(trim($kontingen->jenis_wilayah ?? 'KOTA')),
            'NAMAKOTADOM'=> strtoupper(trim($kontingen->nama_wilayah ?? $kontingen->provinsi)),
            'NAMAPROPDOM'=> strtoupper(trim($kontingen->provinsi)),
            'SP'         => $request->input('sp', '0'),
            'NOMOR'      => 'Estafet',
        ];

        // Collect estafet time fields — only for the submitted prefix/dist
        // Fields are injected by JS as: {prefix}{dist}MM, {prefix}{dist}SS, {prefix}{dist}HS
        $estafetPrefixes = ['ESTMON', 'ESTSUB', 'ESTMONM', 'ESTSUBM'];
        $estafetDistances = [
            'ESTMON'   => [200, 400, 800],
            'ESTSUB'   => [200, 400],
            'ESTMONM'  => [200, 400],
            'ESTSUBM'  => [200, 400],
        ];
        $hasAnyTime = false;
        foreach ($estafetPrefixes as $prefix) {
            foreach ($estafetDistances[$prefix] as $dist) {
                $mmField = $prefix . $dist . 'MM';
                $ssField = $prefix . $dist . 'SS';
                $hsField = $prefix . $dist . 'HS';
                $mm = $request->input($mmField, '');
                $ss = $request->input($ssField, '');
                $hs = $request->input($hsField, '');
                $data[$mmField] = $mm !== '' ? str_pad($mm, 2, '0', STR_PAD_LEFT) : '';
                $data[$ssField] = $ss !== '' ? str_pad($ss, 2, '0', STR_PAD_LEFT) : '';
                $data[$hsField] = $hs !== '' ? str_pad($hs, 2, '0', STR_PAD_LEFT) : '';
                if ($ss !== '') $hasAnyTime = true;
            }
        }
        if (!$hasAnyTime) {
            return redirect()->back()->withInput()->with('error', 'Isi waktu estafet (detik/SS).');
        }
        if ($existing) {
            foreach ($data as $key => $value) {
                if ($request->has($key)) {
                    $existing->$key = $value;
                }
            }
            $existing->GENDER = $data['GENDER'];
            $existing->KU = $data['KU'];
            $existing->SP = $data['SP'];
            $existing->NOMOR = $data['NOMOR'];
            $existing->save();
        } else {
            A3Entry::create($data);
        }
        return redirect()->back()->withInput()->with('success', 'Data Estafet untuk tim ' . $teamName . ' berhasil disimpan.');
    }

    public function deleteA3Entry($id)
    {
        A3Entry::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Entry A3 berhasil dihapus.');
    }

    // ── Proses Form A3 ─────────────────────────────────────────────
    public function prosesFormA3()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->route('lomba.form_a1')->with('error', 'Isi data kontingen dulu.');
        $asal = $this->buildAsal($kontingen);
        $a3Count = A3Entry::where('ASAL', $asal)->count();
        $atletCount = PesertaAtlet::where('ASAL', $asal)->count();
        $pesertaEmailCount = PesertaEmail::where('ASAL', $asal)->count();
        $event = MstEvent::first();

        // ── Detail perorangan & estafet ───────────────────────────
        $peroranganStyleDefs = [
            ['MON', [50, 100, 200, 400, 800, 1500], '%d m Surface'],
            ['SUB', [50, 100, 200, 400], '%d m Bifin'],
            ['APN', [50], '%d m Apnea'],
            ['IMM', [100, 400, 800], '%d m Immersion'],
        ];
        $estafetStyleDefs = [
            ['ESTMON',  [200, 400, 800], '4 x %d m Estafet Surface'],
            ['ESTSUB',  [200, 400],      '4 x %d m Estafet Bifin'],
            ['ESTMONM', [200, 400],      '4 x %d m Estafet Surface Mix'],
            ['ESTSUBM', [200, 400],      '4 x %d m Estafet Bifin Mix'],
        ];

        $a3Entries = A3Entry::where('ASAL', $asal)->orderBy('NAMAATLET')->get();
        $peroranganDetails = [];
        $estafetDetails = [];

        foreach ($a3Entries as $entry) {
            $category = $entry->NOMOR === 'Estafet' ? 'estafet' : 'perorangan';
            $styleDefs = $category === 'estafet' ? $estafetStyleDefs : $peroranganStyleDefs;
            $gayaList = [];

            foreach ($styleDefs as [$prefix, $dists, $gayaTemplate]) {
                foreach ($dists as $dist) {
                    $ssField = $prefix . $dist . 'SS';
                    $mmField = $prefix . $dist . 'MM';
                    $hsField = $prefix . $dist . 'HS';

                    if (!empty($entry->$ssField)) {
                        $mm = $entry->$mmField ?? '';
                        $ss = $entry->$ssField ?? '';
                        $hs = $entry->$hsField ?? '';
                        $waktu = str_pad($mm, 2, '0', STR_PAD_LEFT) . ':' .
                                 str_pad($ss, 2, '0', STR_PAD_LEFT) . '.' .
                                 str_pad($hs, 2, '0', STR_PAD_LEFT);
                        $gaya = sprintf($gayaTemplate, $dist);
                        $gayaList[] = ['gaya' => $gaya, 'waktu' => $waktu];
                    }
                }
            }

            if ($category === 'estafet') {
                $estafetDetails[] = [
                    'nama'   => $entry->NAMAATLET,
                    'gender' => $entry->GENDER,
                    'ku'     => $entry->KU,
                    'sp'     => $entry->SP,
                    'entries' => $gayaList,
                ];
            } else {
                $peroranganDetails[] = [
                    'nama'   => $entry->NAMAATLET,
                    'gender' => $entry->GENDER,
                    'ku'     => $entry->KU,
                    'sp'     => $entry->SP,
                    'entries' => $gayaList,
                ];
            }
        }

        return view('lomba.proses_form_a3', compact(
            'kontingen', 'a3Count', 'atletCount', 'pesertaEmailCount', 'event',
            'peroranganDetails', 'estafetDetails'
        ));
    }

    public function runProsesFormA3()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->back()->with('error', 'Unauthorized.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->back()->with('error', 'Kontingen not found.');
        $asal = $this->buildAsal($kontingen);
        $event = MstEvent::first();
        if (!$event) return redirect()->back()->with('error', 'Data event belum diisi.');

        DB::beginTransaction();
        try {
            // Step 1: Delete existing PesertaEmail for this ASAL
            PesertaEmail::where('ASAL', $asal)->delete();

            // Step 2: Read A3 entries
            $a3Entries = A3Entry::where('ASAL', $asal)->get();
            if ($a3Entries->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data A3 untuk diproses.');
            }

            $insertCount = 0;
            // Style definitions: [prefix, distances, gayaName template, category]
            $peroranganStyles = [
                ['MON', [50, 100, 200, 400, 800, 1500], '%d m Surface'],
                ['SUB', [50, 100, 200, 400], '%d m Bifin'],
                ['APN', [50], '%d m Apnea'],
                ['IMM', [100, 400, 800], '%d m Immersion'],
            ];
            $estafetStyles = [
                ['ESTMON',  [200, 400, 800], '4 x %d m Estafet Surface'],
                ['ESTSUB',  [200, 400],      '4 x %d m Estafet Bifin'],
                ['ESTMONM', [200, 400],      '4 x %d m Estafet Surface Mix'],
                ['ESTSUBM', [200, 400],      '4 x %d m Estafet Bifin Mix'],
            ];

            foreach ($a3Entries as $entry) {
                $category = $entry->NOMOR === 'Estafet' ? 'estafet' : 'perorangan';
                $styleDefs = $category === 'estafet' ? $estafetStyles : $peroranganStyles;

                foreach ($styleDefs as [$prefix, $dists, $gayaTemplate]) {
                    foreach ($dists as $dist) {
                        $ssField = $prefix . $dist . 'SS';
                        $mmField = $prefix . $dist . 'MM';
                        $hsField = $prefix . $dist . 'HS';

                        if (!empty($entry->$ssField)) {
                            $mm = $entry->$mmField ?? '';
                            $ss = $entry->$ssField ?? '';
                            $hs = $entry->$hsField ?? '';
                            $daftar = str_pad($mm, 2, '0', STR_PAD_LEFT) . ':' . str_pad($ss, 2, '0', STR_PAD_LEFT) . '.' . str_pad($hs, 2, '0', STR_PAD_LEFT);

                            // Determine actual distance for the gaya name
                            // For estafet, distance is the total (e.g. 4x50=200), so use the stored distance value
                            $gayaName = sprintf($gayaTemplate, $dist);

                            // Special: 800m Surface -> KU='OPEN'
                            $ku = $entry->KU;
                            if ($prefix === 'MON' && $dist === 800) {
                                $ku = 'OPEN';
                            }

                            PesertaEmail::create([
                                'KDEVENT'       => $event->KDEVENT,
                                'NAMAEVENT'     => $event->NAMAEVENT,
                                'TGLMULAIEVENT' => $event->TGLMULAIEVENT,
                                'TGLAKHIREVENT' => $event->TGLAKHIREVENT,
                                'LOKASI'        => $event->LOKASI,
                                'ASAL'          => $asal,
                                'NAMACLUB'      => $entry->NAMACLUB,
                                'JENISDOM'      => $entry->JENISDOM,
                                'NAMAKOTADOM'   => $entry->NAMAKOTADOM,
                                'NAMAPROPDOM'   => $entry->NAMAPROPDOM,
                                'NAMANEGDOM'    => 'INDONESIA',
                                'GENDER'        => $entry->GENDER,
                                'KU'            => $ku,
                                'NAMAATLET'     => $entry->NAMAATLET,
                                'NONIAS'        => \App\Models\PesertaAtlet::where('NAMAATLET', $entry->NAMAATLET)->where('ASAL', $asal)->value('NONIAS') ?? '',
                                'TGLLAHIR'      => $entry->TGLLAHIR,
                                'NOMOR'         => $category === 'estafet' ? 'Estafet' : 'Perorangan',
                                'SP'            => $entry->SP,
                                'GAYA'          => $gayaName,
                                'MM'            => str_pad($mm, 2, '0', STR_PAD_LEFT),
                                'MMdes'         => ':',
                                'SS'            => str_pad($ss, 2, '0', STR_PAD_LEFT),
                                'SSdes'         => '.',
                                'HS'            => str_pad($hs, 2, '0', STR_PAD_LEFT),
                                'DAFTAR'        => $daftar,
                            ]);
                            $insertCount++;
                        }
                    }
                }
            }

            // Step 3: Update NIAS & TGLLAHIR from Atlet table
            $atlets = PesertaAtlet::where('ASAL', $asal)->get();
            foreach ($atlets as $atlet) {
                PesertaEmail::where('ASAL', $asal)
                    ->where('NAMAATLET', $atlet->NAMAATLET)
                    ->where('GENDER', $atlet->GENDER)
                    ->update([
                        'NONIAS'   => $atlet->NONIAS ?? '',
                        'TGLLAHIR' => $atlet->TGLLAHIR,
                    ]);
            }

            DB::commit();

            // ── Step 4: Generate Excel file ─────────────────────────
            try {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $headers = [
                    'IDAtlet', 'KDEVENT', 'NAMAEVENT', 'TGLMULAIEVENT', 'TGLAKHIREVENT',
                    'LOKASI', 'ASAL', 'NAMACLUB', 'JENISDOM', 'NAMAKOTADOM', 'NAMAPROPDOM',
                    'NAMANEGDOM', 'GENDER', 'KU', 'NAMAATLET', 'NONISDA', 'TPTLAHIR',
                    'TGLLAHIR', 'NOMOR', 'SP', 'GAYA', 'MM', 'MMdes', 'SS', 'SSdes',
                    'HS', 'DAFTAR', 'CETAKPIAGAMPESERTA',
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }

                $pesertaEmails = PesertaEmail::where('ASAL', $asal)->orderBy('NAMAATLET')->get();
                $rowIdx = 2;
                foreach ($pesertaEmails as $pe) {
                    $tglMulai = $pe->TGLMULAIEVENT ? Date::PHPToExcel(\Carbon\Carbon::parse($pe->TGLMULAIEVENT)) : null;
                    $tglAkhir = $pe->TGLAKHIREVENT ? Date::PHPToExcel(\Carbon\Carbon::parse($pe->TGLAKHIREVENT)) : null;
                    $tglLahir = $pe->TGLLAHIR ? Date::PHPToExcel(\Carbon\Carbon::parse($pe->TGLLAHIR)) : null;

                    $sheet->setCellValue('A' . $rowIdx, $rowIdx - 1);
                    $sheet->setCellValue('B' . $rowIdx, $pe->KDEVENT);
                    $sheet->setCellValue('C' . $rowIdx, $pe->NAMAEVENT);
                    $sheet->setCellValue('D' . $rowIdx, $tglMulai);
                    $sheet->setCellValue('E' . $rowIdx, $tglAkhir);
                    $sheet->setCellValue('F' . $rowIdx, $pe->LOKASI);
                    $sheet->setCellValue('G' . $rowIdx, $pe->ASAL);
                    $sheet->setCellValue('H' . $rowIdx, $pe->NAMACLUB);
                    $sheet->setCellValue('I' . $rowIdx, $pe->JENISDOM);
                    $sheet->setCellValue('J' . $rowIdx, $pe->NAMAKOTADOM);
                    $sheet->setCellValue('K' . $rowIdx, $pe->NAMAPROPDOM);
                    $sheet->setCellValue('L' . $rowIdx, $pe->NAMANEGDOM);
                    $sheet->setCellValue('M' . $rowIdx, $pe->GENDER);
                    $sheet->setCellValue('N' . $rowIdx, $pe->KU);
                    $sheet->setCellValue('O' . $rowIdx, $pe->NAMAATLET);
                    $sheet->setCellValueExplicit('P' . $rowIdx, $pe->NONIAS ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('Q' . $rowIdx, null);
                    $sheet->setCellValue('R' . $rowIdx, $tglLahir);
                    $sheet->setCellValue('S' . $rowIdx, $pe->NOMOR);
                    $sheet->setCellValue('T' . $rowIdx, $pe->SP);
                    $sheet->setCellValue('U' . $rowIdx, $pe->GAYA);
                    $sheet->setCellValue('V' . $rowIdx, $pe->MM);
                    $sheet->setCellValue('W' . $rowIdx, $pe->MMdes);
                    $sheet->setCellValue('X' . $rowIdx, $pe->SS);
                    $sheet->setCellValue('Y' . $rowIdx, $pe->SSdes);
                    $sheet->setCellValue('Z' . $rowIdx, $pe->HS);
                    $sheet->setCellValue('AA' . $rowIdx, $pe->DAFTAR);
                    $sheet->setCellValue('AB' . $rowIdx, 0);
                    $rowIdx++;
                }

                // Apply date format to date columns (D, E, R)
                $lastRow = $rowIdx - 1;
                if ($lastRow >= 2) {
                    $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $sheet->getStyle('R2:R' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                }

                $namaKontingenClean = preg_replace('/[^A-Za-z0-9\-\s.]/', '', $kontingen->nama_kontingen);
                $excelFilename = 'FORM_A3_Panitia - ' . $namaKontingenClean . '.xlsx';
                $excelDir = storage_path('app/private/lomba_a3');
                $excelPath = $excelDir . '/' . $excelFilename;

                if (!is_dir($excelDir)) {
                    mkdir($excelDir, 0755, true);
                }

                $writer = new Xlsx($spreadsheet);
                $writer->save($excelPath);

                // ── Step 5: Send email ────────────────────────────────
                try {
                    Mail::to('it.possijatim@gmail.com')->send(new FormA3Mail(
                        namaKontingen: $kontingen->nama_kontingen,
                        excelPath: $excelPath,
                        excelFilename: $excelFilename,
                    ));
                } catch (\Exception $emailErr) {
                    \Log::error('Gagal kirim email FORM A3: ' . $emailErr->getMessage());
                }

                return redirect()->route('lomba.proses')
                    ->with('success', "Proses Form A3 selesai! {$insertCount} entry berhasil diproses. File Excel telah dikirim ke panitia.");
            } catch (\Exception $excelErr) {
                \Log::error('Gagal generate Excel FORM A3: ' . $excelErr->getMessage());
                return redirect()->route('lomba.proses')
                    ->with('success', "Proses Form A3 selesai! {$insertCount} entry berhasil diproses. (Gagal generate Excel: " . $excelErr->getMessage() . ")");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    // ── Shared biaya calculation ────────────────────────────────────
    private function calculateAndSaveBiaya($kontingen, $asal): array
    {
        $allExtraFees = MstBiayaExtra::all();

        // 1. Delete existing records
        KwtDaftarDeposit::where('ASAL', $asal)->delete();

        // 2. Read PesertaEmail for this ASAL
        $entries = PesertaEmail::where('ASAL', $asal)->orderBy('NAMAATLET')->get();
        $isEmpty = $entries->isEmpty();

        $jmlAtlet = 0;
        $totalNolomba = 0;
        $jmlPerorangan = 0;
        $jmlEstafet = 0;
        $rpTotDaftar = 0;
        $rpDeposit = 0;
        $rpLain = 0;
        $rpTotal = 0;

        if (!$isEmpty) {
            // 3. Group by athlete (perorangan only)
            $athletes = [];
            foreach ($entries as $e) {
                if ($e->NOMOR === 'Perorangan') {
                    $key = $e->NAMAATLET . '|' . $e->GENDER;
                    if (!isset($athletes[$key])) {
                        $athletes[$key] = true;
                    }
                }
                $totalNolomba++;
            }
            $jmlAtlet = count($athletes);

            // 4. Tariffs from AppSetting
            $rpTarifPerorangan = (int) \App\Models\AppSetting::get('lomba_tarif_perorangan', '40000');
            $rpTarifEstafet = (int) \App\Models\AppSetting::get('lomba_tarif_estafet', '200000');

            $jmlPerorangan = $entries->where('NOMOR', 'Perorangan')->count();
            $jmlEstafet = $entries->where('NOMOR', 'Estafet')->count();
            $rpTotDaftar = ($jmlPerorangan * $rpTarifPerorangan) + ($jmlEstafet * $rpTarifEstafet);

            // 5. Deposit
            $rpDeposit = 0;
            $adaDenda = MstDenda::count() > 0;
            if ($adaDenda) {
                $deposit = MstDeposit::where('JMLATLETMULAI', '<=', $jmlAtlet)
                    ->where('JMLATLETSAMPAI', '>=', $jmlAtlet)
                    ->first();
                $rpDeposit = $deposit ? $deposit->RPDEPOSIT : 0;
            }

            // 6. Extra fees
            $rpLain = 0;
            foreach ($allExtraFees as $extra) {
                $rpLain += ($extra->RPBIAYAEXTRA * $jmlAtlet);
            }

            // 7. Total
            $rpTotal = $rpTotDaftar + $rpDeposit + $rpLain;

            // 8. Insert into rKwtDaftarDeposit
            KwtDaftarDeposit::create([
                'NOURUT'        => '1',
                'TGLLUNAS'      => now(),
                'ASAL'          => $asal,
                'NAMACLUB'      => $this->buildNamaClub($kontingen),
                'JENISDOM'      => strtoupper(trim($kontingen->jenis_wilayah ?? 'KOTA')),
                'NAMAKOTADOM'   => strtoupper(trim($kontingen->nama_wilayah ?? $kontingen->provinsi)),
                'NAMAPROPDOM'   => strtoupper(trim($kontingen->provinsi)),
                'NAMANEGDOM'    => 'INDONESIA',
                'NOMOR'         => 'Perorangan',
                'JMLATLET'      => $jmlAtlet,
                'JMLNOLOMBA'    => $totalNolomba,
                'RPTARIF'       => $rpTarifPerorangan,
                'RPTOTDAFTAR'   => $rpTotDaftar,
                'RPDEPOSIT'     => $rpDeposit,
                'RPTOTDAFTDEPO' => $rpTotDaftar + $rpDeposit,
                'RPLAIN'        => $rpLain,
                'RPTOTAL'       => $rpTotal,
                'NOKWT'         => 'KWT-' . strtoupper(substr($asal, 0, 3)) . date('ym'),
            ]);
        }

        return compact(
            'jmlAtlet', 'totalNolomba', 'jmlPerorangan', 'jmlEstafet',
            'rpTotDaftar', 'rpDeposit', 'rpLain', 'rpTotal', 'isEmpty'
        );
    }

    // ── Hitung Biaya (GET — auto-calculate) ──────────────────────────
    public function hitungBiaya()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->route('lomba.login')->with('error', 'Silakan login dulu.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->route('lomba.form_a1')->with('error', 'Isi data kontingen dulu.');
        $asal = $this->buildAsal($kontingen);

        // Auto-calculate before showing the view
        try {
            $result = $this->calculateAndSaveBiaya($kontingen, $asal);
        } catch (\Exception $e) {
            // If calculation fails (e.g. no data), just show empty state
        }

        $feeRecords = KwtDaftarDeposit::where('ASAL', $asal)->orderBy('NOMOR')->get();
        $extraFees = MstBiayaExtra::all();
        $pesertaEmailCount = PesertaEmail::where('ASAL', $asal)->count();
        $entries = PesertaEmail::where('ASAL', $asal)->get();
        $jmlPerorangan = $entries->where('NOMOR', 'Perorangan')->count();
        $jmlEstafet = $entries->where('NOMOR', 'Estafet')->count();
        $adaDenda = MstDenda::count() > 0;
        $deposits = $adaDenda ? MstDeposit::orderBy('JMLATLETMULAI')->get() : collect();
        $lombaTarifPerorangan = (int) \App\Models\AppSetting::get('lomba_tarif_perorangan', '40000');
        $lombaTarifEstafet = (int) \App\Models\AppSetting::get('lomba_tarif_estafet', '200000');
        return view('lomba.hitung_biaya', compact('kontingen', 'feeRecords', 'extraFees', 'pesertaEmailCount', 'jmlPerorangan', 'jmlEstafet', 'adaDenda', 'deposits', 'lombaTarifPerorangan', 'lombaTarifEstafet'));
    }

    public function runHitungBiaya()
    {
        $resolved = $this->resolveUser();
        if (!$resolved) return redirect()->back()->with('error', 'Unauthorized.');
        $kontingen = Kontingen::where($resolved['id_col'], $resolved['user_id'])->first();
        if (!$kontingen) return redirect()->back()->with('error', 'Kontingen not found.');
        $asal = $this->buildAsal($kontingen);

        try {
            $result = $this->calculateAndSaveBiaya($kontingen, $asal);

            if ($result['isEmpty']) {
                return redirect()->back()->with('error', 'Belum ada data di PesertaEmail. Jalankan Proses Form A3 dulu.');
            }

            $rpTarifPerorangan = (int) \App\Models\AppSetting::get('lomba_tarif_perorangan', '40000');
            $rpTarifEstafet = (int) \App\Models\AppSetting::get('lomba_tarif_estafet', '200000');

            return redirect()->route('lomba.biaya')
                ->with('success', "Perhitungan biaya selesai! Jml Atlet: {$result['jmlAtlet']}, Perorangan: {$result['jmlPerorangan']} x Rp " . number_format($rpTarifPerorangan, 0, ',', '.') . ", Estafet: {$result['jmlEstafet']} x Rp " . number_format($rpTarifEstafet, 0, ',', '.') . ", Total: Rp " . number_format($result['rpTotal'], 0, ',', '.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghitung biaya: ' . $e->getMessage());
        }
    }
}
