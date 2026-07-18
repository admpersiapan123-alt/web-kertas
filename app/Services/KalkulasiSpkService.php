<?php

namespace App\Services;

use App\Models\TransaksiRoll;

class KalkulasiSpkService
{
    // Daftar posisi mesin dan nama inputnya
    private $posisiList = [
        'DB' => 'gsm_db', 
        'BM' => 'gsm_bm', 
        'BL' => 'gsm_bl', 
        'CM' => 'gsm_cm', 
        'CL' => 'gsm_cl'
    ];

    /**
     * TAHAP 1: Kumpulkan data dari transaksi forklift
     */
    private function kelompokkanForklift($transaksi)
    {
        $forkliftGroup = []; 

        foreach ($transaksi as $t) {
            $lebar_db = floatval($t->masterKertas->lebar ?? 0);
            if ($lebar_db > 500) { $lebar_db = $lebar_db / 10; } 
            
            $gsm_standar = $this->terjemahkanKode($t->masterKertas->gsm ?? '');
            if ($gsm_standar === '') continue;

            // MERGE BUCKET: Jika awalan B atau T, paksa masuk ke kolam K
            $prorate_gsm = $gsm_standar;
            $first_char = substr($gsm_standar, 0, 1);
            if ($first_char === 'B' || $first_char === 'T') {
                $prorate_gsm = 'K' . substr($gsm_standar, 1);
            }

            $posisi = strtoupper($t->posisi_mesin); 
            $sisa_awal = floatval($t->sisa_kilo_awal);
            $sisa_akhir = floatval($t->sisa_kilo_akhir);
            $terpakai = ($sisa_akhir <= 0) ? $sisa_awal : ($sisa_awal - $sisa_akhir);

            $kunci = $lebar_db . '_' . $prorate_gsm . '_' . $posisi; 
            if (!isset($forkliftGroup[$kunci])) { $forkliftGroup[$kunci] = 0; }
            $forkliftGroup[$kunci] += $terpakai; 
        }

        return $forkliftGroup;
    }

   public function hitungProrate($shift_id, $request)
    {
        // TAHAP 1: Ambil data stok forklift
        $transaksi = TransaksiRoll::with('masterKertas')->where('shift_id', $shift_id)->get();
        $forkliftGroup = $this->kelompokkanForklift($transaksi);

        // TAHAP 2: Hitung Total Meter Group (UPDATE: Lempar data forklift ke sini untuk pembagian proporsional)
        $meterGroup = $this->hitungMeterGroup($request, $forkliftGroup);

        // TAHAP 3: Eksekusi Pembagian Prorate
        return $this->eksekusiProrate($request, $forkliftGroup, $meterGroup);
    }

    private function hitungMeterGroup($request, $forkliftGroup)
    {
        $meterGroup = []; 

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm; 
            $meter = floatval($request->panjang_m[$index] ?? 0);

            foreach ($this->posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $input_gsm = $input_mentah;
                $target_lebar_array = [$lebar_cm_global];

                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_tambahan = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                    
                    if (!in_array($lebar_tambahan, $target_lebar_array)) {
                        $target_lebar_array[] = $lebar_tambahan;
                    }
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar === '') continue;

                $prorate_gsm = $gsm_standar;
                $first_char = substr($gsm_standar, 0, 1);
                if ($first_char === 'B' || $first_char === 'T') {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                // LOGIKA BARU: Cek stok aktual di forklift untuk bucket target
                $total_stok_target = 0;
                $stok_per_bucket = [];
                foreach ($target_lebar_array as $lbr) {
                    $kunci = $lbr . '_' . $prorate_gsm . '_' . $pos;
                    $stok = $forkliftGroup[$kunci] ?? 0;
                    $stok_per_bucket[$kunci] = $stok;
                    $total_stok_target += $stok;
                }

                // Daftarkan meter ke laci secara PROPORSIONAL berdasarkan ketersediaan stok
                foreach ($target_lebar_array as $lbr) {
                    $kunci = $lbr . '_' . $prorate_gsm . '_' . $pos;
                    
                    $meter_dibebankan = $meter; 
                    
                    // Jika SPK ini mengklaim lebih dari 1 ukuran laci (ada slash)
                    if (count($target_lebar_array) > 1 && $total_stok_target > 0) {
                        // Pecah meteran berdasarkan rasio stok roll di forklift
                        $meter_dibebankan = $meter * ($stok_per_bucket[$kunci] / $total_stok_target);
                    } elseif (count($target_lebar_array) > 1 && $total_stok_target == 0) {
                        // Fallback jika tidak ada data roll sama sekali
                        $meter_dibebankan = $meter / count($target_lebar_array);
                    }

                    if (!isset($meterGroup[$kunci])) { $meterGroup[$kunci] = 0; }
                    $meterGroup[$kunci] += $meter_dibebankan;
                }
            }
        }
        return $meterGroup;
    }

    private function eksekusiProrate($request, $forkliftGroup, $meterGroup)
    {
        $data_json = [];
        $grand_total_aktual = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            $jatah = ['DB' => 0, 'BM' => 0, 'BL' => 0, 'CM' => 0, 'CL' => 0];
            $total_baris_aktual = 0;

            foreach ($this->posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $input_gsm = $input_mentah;
                $target_lebar_array = [$lebar_cm_global];

                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_tambahan = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                    
                    if (!in_array($lebar_tambahan, $target_lebar_array)) {
                        $target_lebar_array[] = $lebar_tambahan;
                    }
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar === '') continue;

                $prorate_gsm = $gsm_standar;
                $first_char = substr($gsm_standar, 0, 1);
                if ($first_char === 'B' || $first_char === 'T') {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                // LOGIKA BARU: Cek stok aktual di forklift untuk membagi porsi klaim
                $total_stok_target = 0;
                $stok_per_bucket = [];
                foreach ($target_lebar_array as $lbr) {
                    $kunci = $lbr . '_' . $prorate_gsm . '_' . $pos;
                    $stok = $forkliftGroup[$kunci] ?? 0;
                    $stok_per_bucket[$kunci] = $stok;
                    $total_stok_target += $stok;
                }

                $total_jatah_posisi_ini = 0;

                foreach ($target_lebar_array as $lbr) {
                    $kunci = $lbr . '_' . $prorate_gsm . '_' . $pos;
                    
                    // Tentukan porsi meteran yang berhak ditarik dari laci ini
                    $meter_porsi = $meter;
                    if (count($target_lebar_array) > 1 && $total_stok_target > 0) {
                        $meter_porsi = $meter * ($stok_per_bucket[$kunci] / $total_stok_target);
                    } elseif (count($target_lebar_array) > 1 && $total_stok_target == 0) {
                        $meter_porsi = $meter / count($target_lebar_array);
                    }
                    
                    $total_meter_spek = $meterGroup[$kunci] ?? 0;
                    $total_kg_forklift = $forkliftGroup[$kunci] ?? 0;

                    // Hitung rasio menggunakan meter_porsi yang sudah dipecah, bukan meter full
                    $rasio = $total_meter_spek > 0 ? ($meter_porsi / $total_meter_spek) : 0;
                    $total_jatah_posisi_ini += ($rasio * $total_kg_forklift);
                }

                $jatah[$pos] = $total_jatah_posisi_ini;
                $total_baris_aktual += $total_jatah_posisi_ini;
            }

            $grand_total_aktual += $total_baris_aktual;

            $data_json[] = [
                'seq' => $request->seq[$index] ?? '',
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $lebar_cm_global,
                'panjang_m' => $meter,
                'faktor_bm' => $request->faktor_bm[$index] ?? 1.36, 
                'faktor_cm' => $request->faktor_cm[$index] ?? 1.46, 
                'gsm_db' => strtoupper($request->gsm_db[$index] ?? ''), 
                'gsm_bm' => strtoupper($request->gsm_bm[$index] ?? ''), 
                'gsm_bl' => strtoupper($request->gsm_bl[$index] ?? ''), 
                'gsm_cm' => strtoupper($request->gsm_cm[$index] ?? ''), 
                'gsm_cl' => strtoupper($request->gsm_cl[$index] ?? ''), 
                'akt_db' => $jatah['DB'], 
                'akt_bm' => $jatah['BM'], 
                'akt_bl' => $jatah['BL'], 
                'akt_cm' => $jatah['CM'], 
                'akt_cl' => $jatah['CL'],
                'total_aktual' => $total_baris_aktual
            ];
        }

        return [
            'data_json' => $data_json,
            'grand_total' => $grand_total_aktual
        ];
    }

    /**
     * Kamus penerjemah kode kertas
     */
    private function terjemahkanKode($kode)
    {
        if (!$kode || $kode == '-') return '';
        $kode = strtoupper(str_replace(' ', '', $kode));

        if (preg_match('/^([A-Z]+)(\d+)/', $kode, $matches)) {
            $huruf_depan = $matches[1];
            if ($huruf_depan == 'W') { $huruf_depan = 'WK'; }
            if ($huruf_depan == 'T') { $huruf_depan = 'K'; }
            return $huruf_depan . $matches[2]; 
        }

        if (preg_match('/^(\d+)([A-Z]+)/', $kode, $matches)) {
            $angka_depan = $matches[1];
            $huruf_belakang = substr($matches[2], 0, 1); 
            if (!in_array($huruf_belakang, ['K', 'B', 'T', 'M', 'W'])) { $huruf_belakang = 'M'; }

            $angka_db = $angka_depan;
            if ($angka_depan == '101') $angka_db = '100';
            if ($angka_depan == '111') $angka_db = '110';
            if ($angka_depan == '113') $angka_db = '112';
            if ($angka_depan == '127') $angka_db = '125';
            if ($angka_depan == '137') $angka_db = '135';
            if ($angka_depan == '160') { $angka_db = ($huruf_belakang == 'W') ? '140' : '150'; }

            $prefix_db = $huruf_belakang;
            if ($huruf_belakang == 'W') { $prefix_db = 'WK'; }
            if ($prefix_db == 'T') { $prefix_db = 'K'; }

            return $prefix_db . $angka_db; 
        }
        return $kode; 
    }
}