<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockKertas;
use App\Models\ShiftRoll;
use Illuminate\Support\Facades\DB;

class ImportStockController extends Controller
{
    public function showImportForm()
    {
        return view('stock.import'); 
    }

    public function importStockCSV(Request $request)
    {
        $request->validate([
            'file_csv' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file_csv')->getRealPath();
        $handle = fopen($file, "r");

        DB::beginTransaction();
        try {
            $currentJenis = '';
            $currentGsm = '';
            $currentLebar = '';

            while (($data = fgetcsv($handle, 2000, ';')) !== FALSE) {
                
                if (empty(trim($data[0]))) {
                    continue;
                }

                // 3. DETEKSI BARIS KELOMPOK
                if (empty($data[1]) && !empty($data[3]) && !empty($data[4]) && strlen(trim($data[0])) <= 5) {
                    $currentJenis = trim($data[0]);
                    $currentGsm   = trim($data[3]);
                    $currentLebar = trim($data[4]);
                    continue;
                }

                // 4. DETEKSI BARIS DATA ROLL
                if (strlen(trim($data[0])) > 5) {
                    
                    $no_roll      = trim($data[0]);
                    $no_roll_asli = trim($data[4] ?? '');
                    $sisa_kotor   = trim($data[5] ?? '0');
                    $no_po        = trim($data[6] ?? '');
                    $wilayah      = trim($data[14] ?? '');
                    $lokasi       = trim($data[15] ?? '');

                    // --- PERBAIKAN LOGIKA PARSING ANGKA (SMART DETECT WIN 7 & WIN 10) ---
                    
                    $sisa_bersih = trim($sisa_kotor);

                    // Skenario 1: Ada koma DAN titik sekaligus (contoh: 1.367,50 atau 1,367.50)
                    if (strpos($sisa_bersih, ',') !== false && strpos($sisa_bersih, '.') !== false) {
                        // Cek posisi mana yang paling belakang, itu pasti desimalnya
                        if (strrpos($sisa_bersih, ',') > strrpos($sisa_bersih, '.')) {
                            // Format Indo (1.367,50) -> Buang titik, ubah koma jadi titik
                            $sisa_bersih = str_replace('.', '', $sisa_bersih); 
                            $sisa_bersih = str_replace(',', '.', $sisa_bersih);
                        } else {
                            // Format US (1,367.50) -> Buang koma saja
                            $sisa_bersih = str_replace(',', '', $sisa_bersih);
                        }
                    } 
                    // Skenario 2: Hanya ada SALAH SATU simbol (Titik SAJA atau Koma SAJA)
                    else {
                        // Karena berat fisik kertas biasanya bulat atau pakai 1-2 angka desimal...
                        // Jika tepat ada 3 angka di belakang simbol, 99% itu adalah pemisah ribuan (1.367 atau 1,367)
                        if (preg_match('/[.,]\d{3}$/', $sisa_bersih)) {
                            $sisa_bersih = str_replace(['.', ','], '', $sisa_bersih);
                        } else {
                            // Jika bukan 3 angka (misal 1367.5 atau 1367,5), maka itu adalah desimal
                            $sisa_bersih = str_replace(',', '.', $sisa_bersih);
                        }
                    }

                    // Pastikan hanya angka dan titik desimal yang tersisa untuk masuk ke database
                    $sisa_bersih = preg_replace('/[^0-9.]/', '', $sisa_bersih);

                    // Konversi akhir ke float
                    $sisa_final  = (float) ($sisa_bersih ?: 0);

                    // --- END PERBAIKAN ---

                    StockKertas::updateOrCreate(
                        ['no_roll' => $no_roll], 
                        [
                            'jenis'        => $currentJenis,
                            'gsm'          => $currentGsm,
                            'lebar'        => $currentLebar,
                            'no_roll_asli' => $no_roll_asli,
                            'sisa_kertas'  => $sisa_final,
                            'no_po'        => $no_po,
                            'wilayah'      => $wilayah,
                            'lokasi'       => $lokasi,
                            'updated_at'   => now()
                        ]
                    );
                }
            }

            fclose($handle);
            DB::commit();
            
            return back()->with('success', 'Database Stok Kertas berhasil disinkronisasi sepenuhnya!');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return back()->with('error', 'Gagal import! Pastikan format CSV sesuai. Error: ' . $e->getMessage());
        }
    }
}