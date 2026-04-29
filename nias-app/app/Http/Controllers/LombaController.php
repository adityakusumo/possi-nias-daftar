<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Kontingen; // Pastikan Anda membuat model ini nanti

class LombaController extends Controller
{
    // Halaman Utama Pendaftaran Lomba (Portal Lomba)
    public function index()
    {
        return view('lomba.index');
    }

    // Menampilkan Form A1 (Entri Kontingen)
    public function formA1()
    {
        $user = Auth::user();
        // Cari data kontingen milik user ini
        $kontingen = \App\Models\Kontingen::where('user_id', $user->id)->first();
        $listKota = \App\Models\MstKota::orderBy('NAMAKOTA', 'asc')->get();

        // Status: Apakah data sudah pernah disimpan sebelumnya?
        $isKontingenSaved = $kontingen ? true : false;

        return view('lomba.form_a1_kontingen', compact('kontingen', 'listKota', 'isKontingenSaved'));
    }

    // Fungsi untuk menampilkan form nama atlet (buat jika belum ada)
    // Di dalam LombaController.php
    public function formA1NamaAtlet()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $kontingen = \App\Models\Kontingen::where('user_id', $user->id)->first();

        if (!$kontingen) {
            return redirect()->route('lomba.form_a1')->with('error', 'Isi data kontingen dulu.');
        }

        return view('lomba.form_a1_namaatlet', compact('kontingen'));
    }

    // Menyimpan Data Form A1
    public function saveKontingen(Request $request)
    {
        $request->validate([
            'jnsKompetisi'   => 'required|in:K,P',
            'nama_kontingen' => 'required|string',
            'jenis'          => 'required_if:jnsKompetisi,K',
            'nama_wilayah'   => 'required_if:jnsKompetisi,K',
            'provinsi'       => 'required',
        ]);

        // Simpan atau Update data berdasarkan user_id
        Kontingen::updateOrCreate(
            ['user_id' => Auth::id()],
                                  [
                                      'jns_kompetisi'  => $request->jnsKompetisi,
                                  'nama_kontingen' => $request->nama_kontingen,
                                  'jenis_wilayah'  => $request->jenis,
                                  'nama_wilayah'   => strtoupper($request->nama_wilayah),
                                  'provinsi'       => strtoupper($request->provinsi),
                                  ]
        );

        return redirect()->route('lomba.form_a1_namaatlet')
        ->with('success', 'Data Kontingen berhasil disimpan. Sekarang silakan isi daftar atlet.');
    }
}
