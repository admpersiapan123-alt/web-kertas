<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftV3Controller extends Controller
{
    // FUNGSI TAMPILKAN DAFTAR SHIFT V3
    public function index()
    {
        // Ambil data shift V3 dari database
        $shifts = DB::table('shifts_v3')->orderBy('id', 'desc')->get();
        return view('shift_v3.index', compact('shifts'));
    }
    // 1. FUNGSI CETAK LAPORAN (GROUPING PER LEBAR)
    public function printReport($id)
    {
        $shift = DB::table('shifts_v3')->where('id', $id)->first();
        if(!$shift) return abort(404, 'Shift tidak ditemukan!');

        // Tarik data transaksi V3 dan gabungkan (JOIN) dengan master kertas untuk ambil GSM
        $transaksiRaw = DB::table('transaksi_roll_v3')
            ->join('stock_kertas', 'transaksi_roll_v3.no_roll', '=', 'stock_kertas.no_roll')
            ->select('transaksi_roll_v3.*', 'stock_kertas.gsm', 'stock_kertas.lebar as lebar_master')
            ->where('shift_v3_id', $id)
            ->orderBy('lebar_jalan', 'desc') // Urutkan Lebar Jalan dari besar ke kecil (180, 150, dst)
            ->orderBy('waktu_ambil', 'asc')  // Lalu urutkan waktu scan
            ->get();

        // Lakukan Grouping berdasarkan 'lebar_jalan'
        // Hasilnya: [ 180 => [roll1, roll2], 150 => [roll3, roll4] ]
        $groupedTransaksi = $transaksiRaw->groupBy('lebar_jalan');

        return view('shift_v3.print', compact('shift', 'groupedTransaksi'));
    }

    // 2. FUNGSI AJAX: UPDATE SISA KILO DARI HALAMAN PRINT (Versi V3)
    public function updateSisaKilo(Request $request)
    {
        $id = $request->id;
        
        DB::table('transaksi_roll_v3')->where('id', $id)->update([
            'sisa_kilo_akhir' => $request->sisa_kilo_akhir,
            'status' => 'kembali',
            'waktu_kembali' => DB::raw('IFNULL(waktu_kembali, NOW())'),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    // FUNGSI BUAT SHIFT BARU
    public function store(Request $request)
    {
        DB::table('shifts_v3')->insert([
            'kepala_shift' => $request->kepala_shift,
            'shift_ke' => $request->shift_ke,
            'tanggal' => $request->tanggal,
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return back()->with('success', '✅ Shift V3 berhasil dibuat!');
    }

    
// FUNGSI TAMPILKAN HALAMAN SCAN HP (V3)
    public function scan($id)
    {
        $shift = DB::table('shifts_v3')->where('id', $id)->first();
        if(!$shift) return abort(404);

        // Kita JOIN dengan stock_kertas agar Jenis, GSM, & Lebar Master ikut terbawa (Tanpa Model)
        $scans = DB::table('transaksi_roll_v3')
                    ->leftJoin('stock_kertas', 'transaksi_roll_v3.no_roll', '=', 'stock_kertas.no_roll')
                    ->select('transaksi_roll_v3.*', 'stock_kertas.jenis', 'stock_kertas.gsm', 'stock_kertas.lebar as master_lebar')
                    ->where('shift_v3_id', $id)
                    ->orderBy('transaksi_roll_v3.id', 'desc')
                    ->get();

        return view('shift_v3.scan', compact('shift', 'scans'));
    }

    // FUNGSI AJAX: MENERIMA DATA DARI SCANNER KAMERA V3
    public function storeScanAjax(Request $request, $id)
    {
        $no_roll = strtoupper(trim($request->no_roll));
        
        $master = DB::table('stock_kertas')->where('no_roll', $no_roll)->first();
        if(!$master) return response()->json(['success' => false, 'message' => "Roll $no_roll tidak ditemukan di Master Gudang!"]);

        $exists = DB::table('transaksi_roll_v3')->where('shift_v3_id', $id)->where('no_roll', $no_roll)->exists();
        if($exists) return response()->json(['success' => false, 'message' => "Roll $no_roll sudah di-scan di shift ini!"]);

        DB::table('transaksi_roll_v3')->insert([
            'shift_v3_id' => $id,
            'lebar_jalan' => $request->lebar_jalan, // 👈 FITUR SPESIAL V3
            'no_roll' => $no_roll,
            'posisi_mesin' => $request->posisi_mesin,
            'waktu_ambil' => now(),
            'sisa_kilo_awal' => $master->sisa_kertas, // Ambil dari master gudang
            'status' => 'diambil',
            'metode_input' => $request->metode ?? 'scan_manual',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    // FUNGSI KEMBALIKAN ROLL & INPUT SISA AKHIR
    public function kembalikanRoll(Request $request, $id)
    {
        DB::table('transaksi_roll_v3')->where('id', $id)->update([
            'sisa_kilo_akhir' => $request->sisa_kilo_akhir,
            'status' => 'kembali',
            'posisi_mesin' => $request->posisi_mesin ?? DB::raw('posisi_mesin'),
            'waktu_kembali' => now(),
            'updated_at' => now()
        ]);
        return back()->with('success', '✅ Sisa roll berhasil diupdate!');
    }

    // FUNGSI HAPUS ROLL
    public function batalRoll($id)
    {
        DB::table('transaksi_roll_v3')->where('id', $id)->delete();
        return back()->with('success', '✅ Roll berhasil dihapus dari sistem!');
    }

    // FUNGSI UBAH POSISI
    public function ubahPosisi(Request $request, $id)
    {
        DB::table('transaksi_roll_v3')->where('id', $id)->update(['posisi_mesin' => $request->posisi_mesin]);
        return back()->with('success', '✅ Posisi mesin berhasil diubah!');
    }
}