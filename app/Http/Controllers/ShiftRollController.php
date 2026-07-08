<?php

namespace App\Http\Controllers;

use App\Models\StockKertas;
use App\Models\Shift;
use App\Models\TransaksiRoll;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftRollController extends Controller
{
    public function history()
    {
        $shifts = Shift::orderBy('id', 'desc')->get();
        return view('shift.history', compact('shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kepala_shift' => 'required|string',
            'tanggal' => 'required|date',
            'shift_ke' => 'required' 
        ]);

        Shift::create([
            'kepala_shift' => $request->kepala_shift,
            'tanggal' => $request->tanggal,
            'shift_ke' => $request->shift_ke,
            'status' => 'aktif'
        ]);

        return redirect('/shift')->with('success', 'Sesi Shift Baru Berhasil Dibuat!');
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        return view('shift.edit_shift', compact('shift'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kepala_shift' => 'required|string',
            'tanggal' => 'required|date',
            'status' => 'required|in:aktif,selesai'
        ]);

        $shift = Shift::findOrFail($id);
        $shift->update($request->all());

        return redirect('/shift')->with('success', 'Data Shift Berhasil Diperbarui!');
    }

    public function dashboard($id)
    {
        $shift = Shift::findOrFail($id);
        $transaksi = TransaksiRoll::with('masterKertas')
                        ->where('shift_id', $id)
                        ->orderBy('id', 'desc')->get();

        return view('shift.dashboard_mobile', compact('shift', 'transaksi'));
    }

    public function postAmbilRoll(Request $request, $id)
    {
        $no_roll = $request->no_roll;
        $metode = $request->metode;
        $keterangan = $request->keterangan;
        $posisi_mesin = $request->posisi_mesin;

        $kertas = StockKertas::where('no_roll', $no_roll)->first();
        if (!$kertas) {
            return response()->json(['success' => false, 'message' => 'Nomor Roll tidak terdaftar di Master Kertas!']);
        }

        $cek = TransaksiRoll::where('no_roll', $no_roll)->where('status', 'diambil')->first();
        if ($cek) {
            return response()->json(['success' => false, 'message' => 'Roll ini sedang dibawa forklift!']);
        }

        // ==========================================
        // FITUR PEMBERSIH DATA SCRAPING PYTHON
        // ==========================================
        $sisa_kotor = $kertas->sisa_kertas;
        
        // 1. Ubah koma menjadi titik (jika format ribuan/desimal pakai koma)
        $sisa_bersih = str_replace(',', '.', $sisa_kotor);
        // 2. Hapus SEMUA karakter kecuali angka dan titik (Hapus 'Kg', spasi, \n, dll)
        $sisa_bersih = preg_replace('/[^0-9.]/', '', $sisa_bersih);
        // 3. Pastikan menjadi tipe data float (Jika kosong, jadikan 0)
        $sisa_final = (float) ($sisa_bersih ?: 0);


        try {
            TransaksiRoll::create([
                'shift_id' => $id,
                'no_roll' => $no_roll,
                'posisi_mesin' => $posisi_mesin,
                'waktu_ambil' => Carbon::now(),
                'sisa_kilo_awal' => $sisa_final, // Masukkan data yang sudah dicuci bersih
                'status' => 'diambil',
                'metode_input' => $metode,
                'keterangan' => $keterangan
            ]);

            return response()->json(['success' => true, 'message' => 'Roll berhasil dicatat di posisi ' . $posisi_mesin . '!']);
            
        } catch (\Exception $e) {
            // Jika MySQL masih menolak, tangkap errornya dan kirim ke layar HP supir/admin!
            return response()->json([
                'success' => false, 
                'message' => 'Sistem Error (Lapor IT): ' . $e->getMessage()
            ]);
        }
    }

    public function batalRoll($id)
    {
        $transaksi = TransaksiRoll::findOrFail($id);
        
        // Jika roll sudah dikembalikan, kembalikan dulu sisa kertas ke stok awal sebelum dihapus
        if ($transaksi->status == 'kembali') {
            StockKertas::where('no_roll', $transaksi->no_roll)->update([
                'sisa_kertas' => $transaksi->sisa_kilo_awal
            ]);
        }

        // Eksekusi hapus data
        $transaksi->delete();
        
        return redirect()->back()->with('success', 'Data scan roll berhasil dibatalkan dan dihapus!');
    }

    public function postKembaliRoll(Request $request, $id)
    {
        $request->validate([
            'sisa_kilo_akhir' => 'required|numeric',
            'posisi_mesin' => 'required|string' // Tambahkan validasi ini
        ]);

        $transaksi = TransaksiRoll::findOrFail($id);
        $transaksi->update([
            'posisi_mesin' => $request->posisi_mesin, // TAMBAHKAN BARIS INI agar posisi mesin tersimpan!
            'waktu_kembali' => Carbon::now(),
            'sisa_kilo_akhir' => $request->sisa_kilo_akhir,
            'status' => 'kembali'
        ]);

        StockKertas::where('no_roll', $transaksi->no_roll)->update([
            'sisa_kertas' => $request->sisa_kilo_akhir
        ]);

        return redirect()->back()->with('success', 'Data sisa roll dan posisi mesin berhasil diperbarui!');
    }

    // 1. Fungsi mencari berat awal, gsm, lebar dari Master Stok Kertas
public function getInfoRoll(Request $request)
{
    // Silakan sesuaikan nama model Master Stok Anda (misal MasterKertas)
    $kertas = \App\Models\StockKertas::where('no_roll', $request->no_roll)->first();

    if ($kertas) {
        return response()->json([
            'success' => true,
            'berat_awal' => $kertas->berat_awal, // Sesuaikan nama kolom berat di tabel master Anda
            'gsm'        => $kertas->gsm,
            'lebar'      => $kertas->lebar,
        ]);
    }
    return response()->json(['success' => false]);
}

// 2. Fungsi memperbarui nomor roll, berat sisa awal, dan posisi mesin sekaligus
public function updateRollDanPosisi(Request $request) // Sesuaikan nama fungsinya dengan yang Anda pakai
    {
        // 1. JIKA EDIT DATA LAMA
        if ($request->has('id') && $request->id != null) {
            $transaksi = \App\Models\TransaksiRoll::find($request->id);
            if ($transaksi) {
                $transaksi->no_roll         = $request->no_roll;
                $transaksi->sisa_kilo_awal  = $request->sisa_kilo_awal;
                $transaksi->posisi_mesin    = $request->posisi_mesin;
                $transaksi->save();
                return response()->json(['success' => true]);
            }
        } 
        // 2. JIKA BARIS BARU (Baru diinput dari kolom kosong)
        else {
            $transaksi = new \App\Models\TransaksiRoll();
            $transaksi->shift_id        = $request->shift_id; 
            $transaksi->no_roll         = $request->no_roll;
            $transaksi->sisa_kilo_awal  = $request->sisa_kilo_awal;
            $transaksi->posisi_mesin    = $request->posisi_mesin ?? 'DB'; // Beri default DB jika tidak sengaja kosong
            
            // --- INI BAGIAN YANG DIPERBAIKI SESUAI SKEMA ---
            $transaksi->waktu_ambil     = now(); 
            $transaksi->status          = 'diambil'; // Sesuai enum: 'diambil' atau 'kembali'
            $transaksi->metode_input    = 'manual';  // Wajib diisi karena tidak nullable() di database
            // -----------------------------------------------

            $transaksi->save();

            return response()->json(['success' => true, 'new_id' => $transaksi->id]);
        }

        return response()->json(['success' => false], 404);
    }

public function tambahRollLangsung(Request $request)
{
    try {
        // 1. CARI DATA ROLL INI DI MASTER STOCK KERTAS
        $master = \App\Models\StockKertas::where('no_roll', $request->no_roll)->first();

        if (!$master) {
            return response()->json([
                'success' => false,
                'message' => 'No Roll tidak ditemukan di Master Stock Kertas!'
            ], 404);
        }

        // 2. CEK RIWAYAT TRANSAKSI TERAKHIR ROLL INI
        $transaksiTerakhir = \App\Models\TransaksiRoll::where('no_roll', $request->no_roll)
                                  ->orderBy('created_at', 'desc')
                                  ->first();

        // 3. LOGIKA FORKLIFT (Cek apakah masih dipakai)
        if ($transaksiTerakhir && is_null($transaksiTerakhir->sisa_kilo_akhir)) {
            return response()->json([
                'success' => false,
                'message' => "Roll {$request->no_roll} masih dipakai di mesin (sisa belum dikembalikan)! Sedang dibawa forklift ya? 🚜"
            ]);
        }

        // 4. SIMPAN SEBAGAI TRANSAKSI BARU
        $transaksi = new \App\Models\TransaksiRoll();
        $transaksi->shift_id        = $request->shift_id; 
        $transaksi->no_roll         = $request->no_roll;
        $transaksi->posisi_mesin    = $request->posisi_mesin ?? 'DB';
        
        // ==========================================
        // PERBAIKAN LOGIKA PENGAMBILAN BERAT
        // ==========================================
        if ($transaksiTerakhir) {
            // Jika pernah dipakai sebelumnya, sisa awalnya adalah sisa akhir dari transaksi sebelumnya
            $transaksi->sisa_kilo_awal = $transaksiTerakhir->sisa_kilo_akhir;
        } else {
            // Jika ini pertama kali dipakai, baru ambil dari Master Kertas
            $transaksi->sisa_kilo_awal = $master->berat ?? $master->sisa_kertas ?? 0;
        }
        // ==========================================
        
        $transaksi->waktu_ambil     = now(); 
        $transaksi->status          = 'diambil'; 
        $transaksi->metode_input    = 'manual';  
        $transaksi->save();

        // 5. KEMBALIKAN SEMUA DATA KE JAVASCRIPT
        return response()->json([
            'success'         => true, 
            'new_id'          => $transaksi->id,
            'gsm'             => $master->gsm ?? '-',
            'lebar'           => $master->lebar ?? '-',
            'sisa_kilo_awal'  => $transaksi->sisa_kilo_awal
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function printReport($id)
    {
        $shift = Shift::findOrFail($id);
        
        // 1. Ambil data mentah urut secara kronologis (waktu scan) terlebih dahulu
        $transaksiRaw = TransaksiRoll::with('masterKertas')
                        ->where('shift_id', $id)
                        ->orderBy('waktu_ambil', 'asc')
                        ->get();

        // Variabel pembantu untuk mendeteksi siklus jalan mesin
        $currentGroup = 1;
        $lastLebar = 99999; // Angka patokan awal dibuat sangat besar

        // 2. Looping untuk membaca alur kerja supir
        foreach ($transaksiRaw as $t) {
            // Ambil angka lebar kertas (jika kosong anggap 0)
            $lebar = floatval($t->masterKertas->lebar ?? 0);
            
            // LOGIKA PABRIK: 
            // Jika ukuran kertas tiba-tiba NAIK dari sebelumnya (misal 95 loncat ke 180),
            // Berarti ini adalah Siklus / Jadwal jalan yang baru.
            if ($lebar > $lastLebar) {
                $currentGroup++; // Buat kelompok baru di bawahnya
            }
            
            // Tempelkan nomor kelompok ke data transaksi ini
            $t->siklus_grup = $currentGroup;
            
            // Update patokan lebar terakhir (abaikan jika lebar 0 agar tidak merusak urutan)
            if ($lebar > 0) {
                $lastLebar = $lebar;
            }
        }

        // 3. Lakukan pengurutan (Sorting) bertingkat yang pintar
        $transaksi = $transaksiRaw->sortBy([
            ['siklus_grup', 'asc'],         // Tahap 1: Urutkan berdasarkan kelompok siklus (yang baru di bawah)
            ['masterKertas.lebar', 'desc'], // Tahap 2: Dalam kelompok yang sama, urutkan LEBAR TERBESAR di atas
            ['waktu_ambil', 'asc'],         // Tahap 3: Jika lebarnya sama persis, urutkan dari yang di-scan duluan
        ])->values(); 

        // Pastikan nama view Anda benar, sebelumnya Anda pakai 'shift.print' atau 'kertas.print'
        return view('shift.print', compact('shift', 'transaksi'));
    }

    // FUNGSI AJAX: UPDATE SISA KILO LANGSUNG DARI HALAMAN PRINT
    // FUNGSI AJAX: UPDATE SISA KILO LANGSUNG DARI HALAMAN PRINT
    // UPDATE FUNGSI INI:
    // FUNGSI AJAX: UPDATE SISA KILO LANGSUNG DARI HALAMAN PRINT
    public function updateSisaKilo(Request $request)
    {
        $transaksi = TransaksiRoll::find($request->id);
        
        if($transaksi) {
            $transaksi->sisa_kilo_akhir = $request->sisa_kilo_akhir;
            $transaksi->status = 'kembali';
            
            // Isi waktu kembali HANYA jika sebelumnya masih kosong
            if(empty($transaksi->waktu_kembali)) {
                $transaksi->waktu_kembali = now();
            }
            
            $transaksi->save(); // Simpan ke database transaksi

            // --- TAMBAHAN BARU: Update Master Stock Kertas ---
            \App\Models\StockKertas::where('no_roll', $transaksi->no_roll)->update([
                'sisa_kertas' => $request->sisa_kilo_akhir
            ]);
            // -------------------------------------------------

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    // TAMBAHKAN FUNGSI BARU INI DI BAWAHNYA:
    // FUNGSI AJAX: BATAL ROLL DARI HALAMAN PRINT
    public function batalRollAjax(Request $request)
    {
        $transaksi = TransaksiRoll::find($request->id);
        
        if($transaksi) {
            // Jika statusnya sudah 'kembali', kembalikan sisa kertas ke stok awal sebelum dihapus
            if ($transaksi->status == 'kembali') {
                \App\Models\StockKertas::where('no_roll', $transaksi->no_roll)->update([
                    'sisa_kertas' => $transaksi->sisa_kilo_awal
                ]);
            }

            // Eksekusi hapus data
            $transaksi->delete();
            
            return response()->json(['success' => true, 'message' => 'Roll berhasil dibatalkan.']);
        }

        return response()->json(['success' => false, 'message' => 'Data transaksi tidak ditemukan!'], 404);
    }

    // Method baru untuk mengubah posisi mesin tanpa input sisa kg
    public function ubahPosisiMesin(Request $request, $id)
    {
        $request->validate([
            'posisi_mesin' => 'required|string'
        ]);

        $transaksi = TransaksiRoll::findOrFail($id);
        $transaksi->update([
            'posisi_mesin' => $request->posisi_mesin
        ]);

        return redirect()->back()->with('success', 'Posisi mesin berhasil diganti menjadi ' . $request->posisi_mesin);
    }
}