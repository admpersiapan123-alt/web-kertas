<?php

namespace App\Http\Controllers;

use App\Models\StockKertas;
use Illuminate\Http\Request;

class StockKertasController extends Controller
{
    public function index(Request $request)
{
    // 1. Tangkap semua input dari request
    $search = $request->input('search');
    $gsm = $request->input('gsm');
    $lebar = $request->input('lebar');

    // 2. Inisialisasi query model
    $query = StockKertas::query();

    // 3. Terapkan filter jika input tidak kosong
    if (!empty($search)) {
        $query->where('no_roll', 'LIKE', '%' . $search . '%');
    }

    if (!empty($gsm)) {
        $query->where('gsm', $gsm);
    }

    if (!empty($lebar)) {
        $query->where('lebar', $lebar);
    }

    // 4. Logika penentuan hasil output (Paginasi vs Tampil Semua)
    if (!empty($gsm) && !empty($lebar)) {
        // Jika mencari menggunakan GSM DAN Lebar secara bersamaan:
        // Urutkan berdasarkan 'jenis' (A-Z) dan ambil semua data (tanpa paginasi)
        $data_kertas = $query->orderBy('jenis', 'asc')->get();
    } else {
        // Jika selain itu: 
        // Gunakan urutan default (ID terbaru) dan gunakan paginasi 15 data per halaman
        $data_kertas = $query->orderBy('id', 'desc')->paginate(15)->appends([
            'search' => $search,
            'gsm' => $gsm,
            'lebar' => $lebar
        ]);
    }

    // 5. Kirim data ke view (pastikan semua variabel ikut di-compact)
    return view('master.index', compact('data_kertas', 'search', 'gsm', 'lebar'));
}

    public function scanView()
    {
        return view('master.scan');
    }

    public function checkRollApi($no_roll)
    {
        $kertas = StockKertas::where('no_roll', $no_roll)->first();

        if ($kertas) {
            return response()->json(['success' => true, 'data' => $kertas]);
        }

        return response()->json(['success' => false, 'message' => 'Nomor Roll tidak ditemukan di database!']);
    }
}