<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockKertas;
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
        
        // 1. AUTO-DETECT DELIMITER (Koma atau Titik Koma)
        $firstLine = fgets(fopen($file, 'r'));
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        
        // 2. AUTO-DETECT VERSI FILE (Apakah Versi Bersih atau FastReport)
        $isCleanVersion = str_contains(strtolower($firstLine), 'noroll') && str_contains(strtolower($firstLine), 'nopo');

        $handle = fopen($file, "r");

        DB::beginTransaction();
        try {
            // =========================================================
            // FITUR BARU: Catat waktu pasti saat import dimulai
            // =========================================================
            $waktuImport = now();

            $currentJenis = '';
            $currentGsm = '';
            $currentLebar = '';

            // Lewati baris pertama jika itu versi bersih
            if ($isCleanVersion) {
                fgetcsv($handle, 2000, $delimiter);
            }

            while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                
                if (empty(trim($data[0]))) {
                    continue;
                }

                // ==========================================
                // JIKA MENGGUNAKAN CSV VERSI LAMA (FASTREPORT)
                // ==========================================
                if (!$isCleanVersion) {
                    if (empty($data[1]) && !empty($data[3]) && !empty($data[4]) && strlen(trim($data[0])) <= 5) {
                        $currentJenis = trim($data[0]);
                        $currentGsm   = trim($data[3]);
                        $currentLebar = trim($data[4]);
                        continue;
                    }

                    if (strlen(trim($data[0])) > 5) {
                        $no_roll      = trim($data[0]);
                        $no_roll_asli = trim($data[4] ?? '');
                        $sisa_kotor   = trim($data[5] ?? '0');
                        $no_po        = trim($data[6] ?? '');
                        $wilayah      = trim($data[14] ?? '');
                        $lokasi       = trim($data[15] ?? '');
                    } else {
                        continue;
                    }
                } 
                // ==========================================
                // JIKA MENGGUNAKAN CSV VERSI BARU (BERSIH)
                // ==========================================
                else {
                    $no_roll      = trim($data[0] ?? '');
                    $no_roll_asli = trim($data[1] ?? '');
                    $no_po        = trim($data[2] ?? '');
                    
                    $currentJenis = trim($data[4] ?? '');
                    $currentGsm   = trim($data[5] ?? '');
                    $currentLebar = trim($data[6] ?? '');
                    
                    $sisa_kotor   = trim($data[9] ?? '0');
                    $wilayah      = trim($data[10] ?? '');
                    $lokasi       = trim($data[11] ?? '');
                }

                // --- SMART PARSING ANGKA SISA KERTAS (TETAP DIPERTAHANKAN) ---
                $sisa_bersih = trim($sisa_kotor);

                if (strpos($sisa_bersih, ',') !== false && strpos($sisa_bersih, '.') !== false) {
                    if (strrpos($sisa_bersih, ',') > strrpos($sisa_bersih, '.')) {
                        $sisa_bersih = str_replace('.', '', $sisa_bersih); 
                        $sisa_bersih = str_replace(',', '.', $sisa_bersih);
                    } else {
                        $sisa_bersih = str_replace(',', '', $sisa_bersih);
                    }
                } else {
                    if (preg_match('/[.,]\d{3}$/', $sisa_bersih)) {
                        $sisa_bersih = str_replace(['.', ','], '', $sisa_bersih);
                    } else {
                        $sisa_bersih = str_replace(',', '.', $sisa_bersih);
                    }
                }

                $sisa_bersih = preg_replace('/[^0-9.]/', '', $sisa_bersih);
                $sisa_final  = (float) ($sisa_bersih ?: 0);
                
                // --- FORMATTING LEBAR (Bulatkan ke angka utuh) ---
                $lebar_bersih = str_replace(',', '.', $currentLebar);
                $lebar_final  = (int) round((float) $lebar_bersih);


                // INSERT ATAU UPDATE KE DATABASE
                if (!empty($no_roll)) {
                    StockKertas::updateOrCreate(
                        ['no_roll' => $no_roll], 
                        [
                            'jenis'        => $currentJenis,
                            'gsm'          => $currentGsm,
                            'lebar'        => $lebar_final,
                            'no_roll_asli' => $no_roll_asli,
                            'sisa_kertas'  => $sisa_final,
                            'no_po'        => $no_po,
                            'wilayah'      => $wilayah,
                            'lokasi'       => $lokasi,
                            // =================================================
                            // FITUR BARU: Paksa updated_at menggunakan waktu import
                            // =================================================
                            'updated_at'   => $waktuImport 
                        ]
                    );
                }
            }

            fclose($handle);

            // =========================================================
            // FITUR BARU: HAPUS ROLL YANG TIDAK ADA DI CSV
            // =========================================================
            // Mencari roll di database yang 'updated_at'-nya LEBIH LAMA dari $waktuImport.
            // Karena jika dia ada di CSV, pasti updated_at nya sudah berubah menjadi $waktuImport.
            StockKertas::where('updated_at', '<', $waktuImport)->delete();

            DB::commit();
            
            return back()->with('success', 'Database Stok Kertas berhasil disinkronisasi otomatis!');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return back()->with('error', 'Gagal import! Error: ' . $e->getMessage());
        }
    }
}