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
        $request->validate(['sisa_kilo_akhir' => 'required|numeric']);

        $transaksi = TransaksiRoll::findOrFail($id);
        $transaksi->update([
            'waktu_kembali' => Carbon::now(),
            'sisa_kilo_akhir' => $request->sisa_kilo_akhir,
            'status' => 'kembali'
        ]);

        StockKertas::where('no_roll', $transaksi->no_roll)->update([
            'sisa_kertas' => $request->sisa_kilo_akhir
        ]);

        return redirect()->back()->with('success', 'Data sisa roll berhasil diperbarui!');
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
}