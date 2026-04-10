<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Halaman existing untuk admin - menampilkan semua club
     */
    public function existing(Request $request)
    {
        // Cek apakah user adalah admin
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized access');
        }

        // Default sort: EXPIRED (terlama di atas), lalu NAMA
        $sortCol = $request->get('sort', 'EXPIRED');
        $sortDir = $request->get('dir', 'asc');

        // Validasi kolom sort yang diizinkan
        $allowedSort = ['NAMACLUB', 'NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS', 'EXPIRED'];
        if (!in_array($sortCol, $allowedSort)) {
            $sortCol = 'EXPIRED';
        }

        // Query builder
        $query = DB::table('NIAS')->whereNotNull('NONIAS');

        // Search: nama, NONIAS, atau nama club
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('NAMA', 'LIKE', "%{$search}%")
                ->orWhere('NONIAS', 'LIKE', "%{$search}%")
                ->orWhere('NAMACLUB', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        if ($sortCol === 'EXPIRED') {
            // Expired: null terakhir, kemudian sort by date
            $query->orderByRaw("EXPIRED IS NULL, EXPIRED {$sortDir}");
        } else {
            $query->orderBy($sortCol, $sortDir);
        }

        // Tambah secondary sort by nama kalau bukan sort by nama
        if ($sortCol !== 'NAMA') {
            $query->orderBy('NAMA', 'asc');
        }

        // Paginate
        $records = $query->paginate(15)->withQueryString();

        return view('admin.existing', compact('records', 'sortCol', 'sortDir'));
    }
}
