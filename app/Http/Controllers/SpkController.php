<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spk;
use App\Models\KalkulasiSpk;
use App\Models\TransaksiRoll;

class SpkController extends Controller
{
    // Halaman Menu Utama SPK
    public function index()
    {
        return view('spk.index'); 
    }

    // HALAMAN RIWAYAT BATCH
    public function riwayat(Request $request)
    {
        $search = $request->input('search');
        $query = KalkulasiSpk::orderBy('id', 'desc');

        if ($search) {
            $query->where('kode_sesi', 'LIKE', '%' . $search . '%')
                  ->orWhere('data_spk', 'LIKE', '%' . $search . '%');
        }

        $kalkulasis = $query->paginate(10)->appends(['search' => $search]);
        return view('spk.riwayat', compact('kalkulasis', 'search'));
    }

    // FUNGSI HAPUS BATCH
    public function destroy($id)
    {
        KalkulasiSpk::findOrFail($id)->delete();
        return redirect('/hitung-spk/riwayat')->with('success', 'Satu Sesi Batch SPK berhasil dihapus secara keseluruhan!');
    }

    public function kalkulasiManual()
    {
        return view('spk.manual');
    }

    // =========================================================================
    // MODIFIKASI: SIMPAN MANUAL HYBRID (BISA ECERAN PER SPK & BORONGAN BULK)
    // =========================================================================
    public function storeManual(Request $request)
    {
        $request->validate([
            'no_spk' => 'required|array',
            'lebar_cm' => 'required|array',
            'panjang_m' => 'required|array',
        ]);

        $bulk_aktual = $request->bulk_aktual ?? []; // Ambil data borongan jika ada
        $posisiList = ['DB' => 'gsm_db', 'BM' => 'gsm_bm', 'BL' => 'gsm_bl', 'CM' => 'gsm_cm', 'CL' => 'gsm_cl'];

        // --- TAHAP 1: KUMPULKAN TOTAL METER PER KELOMPOK (Untuk Prorate Manual) ---
        $meterBulk = [];
        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_cm = floatval($request->lebar_cm[$index] ?? 0);
            $meter = floatval($request->panjang_m[$index] ?? 0);

            foreach ($posisiList as $pos => $inputName) {
                $mentah = strtoupper(trim($request->input($inputName)[$index] ?? ''));
                if ($mentah === '' || $mentah === '-') continue;

                $l_pakai = $lebar_cm;
                $g_pakai = $mentah;
                
                if (strpos($mentah, '/') !== false) {
                    $parts = explode('/', $mentah);
                    $g_pakai = $parts[0];
                    $khusus = floatval($parts[1]);
                    $l_pakai = $khusus > 500 ? ($khusus / 10) : $khusus;
                }

                $kunci_mentah = $l_pakai . '_' . $g_pakai . '_' . $pos;
                if (!isset($meterBulk[$kunci_mentah])) { $meterBulk[$kunci_mentah] = 0; }
                $meterBulk[$kunci_mentah] += $meter;
            }
        }

        // --- TAHAP 2: SUSUN DATA JSON SPK ---
        $data_json = []; 
        $total_semua = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_cm = floatval($request->lebar_cm[$index] ?? 0);
            $meter = floatval($request->panjang_m[$index] ?? 0);
            
            $jatah = ['DB' => 0, 'BM' => 0, 'BL' => 0, 'CM' => 0, 'CL' => 0];
            $aktual_baris = 0;

            foreach ($posisiList as $pos => $inputName) {
                $mentah = strtoupper(trim($request->input($inputName)[$index] ?? ''));
                if ($mentah === '' || $mentah === '-') continue;

                $l_pakai = $lebar_cm;
                $g_pakai = $mentah;
                
                if (strpos($mentah, '/') !== false) {
                    $parts = explode('/', $mentah);
                    $g_pakai = $parts[0];
                    $khusus = floatval($parts[1]);
                    $l_pakai = $khusus > 500 ? ($khusus / 10) : $khusus;
                }

                $kunci_mentah = $l_pakai . '_' . $g_pakai . '_' . $pos;

                // Prioritas 1: Jika kotak borongan diisi, gunakan Prorate Meteran
                if (isset($bulk_aktual[$kunci_mentah]) && $bulk_aktual[$kunci_mentah] !== '') {
                    $totMeterGrup = $meterBulk[$kunci_mentah] ?? 0;
                    $bulkKg = floatval($bulk_aktual[$kunci_mentah]);
                    $rasio = $totMeterGrup > 0 ? ($meter / $totMeterGrup) : 0;
                    $jatah[$pos] = $rasio * $bulkKg;
                } 
                // Prioritas 2: Jika borongan kosong, ambil dari input eceran per baris
                else {
                    $input_name = 'akt_' . strtolower($pos);
                    $jatah[$pos] = floatval($request->input($input_name)[$index] ?? 0);
                }
                $aktual_baris += $jatah[$pos];
            }

            $total_semua += $aktual_baris;

            $data_json[] = [
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $lebar_cm,
                'panjang_m' => $meter,
                'faktor_bm' => $request->faktor_bm[$index] ?? 1.36,
                'faktor_cm' => $request->faktor_cm[$index] ?? 1.46,
                'gsm_db' => $request->gsm_db[$index] ?? 0,
                'gsm_bm' => $request->gsm_bm[$index] ?? 0,
                'gsm_bl' => $request->gsm_bl[$index] ?? 0,
                'gsm_cm' => $request->gsm_cm[$index] ?? 0,
                'gsm_cl' => $request->gsm_cl[$index] ?? 0,
                'akt_db' => $jatah['DB'],
                'akt_bm' => $jatah['BM'],
                'akt_bl' => $jatah['BL'],
                'akt_cm' => $jatah['CM'],
                'akt_cl' => $jatah['CL'],
                'total_aktual' => $aktual_baris,
            ];
        }

        KalkulasiSpk::create([
            'kode_sesi' => 'MANUAL-' . date('Ymd-His'),
            'data_spk' => $data_json,
            'total_aktual_semua' => $total_semua
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', '1 Sesi Batch Manual Berhasil Disimpan!');
    }

    public function edit($id)
    {
        $kalkulasi = KalkulasiSpk::findOrFail($id);
        return view('spk.edit_batch', compact('kalkulasi'));
    }

    public function update(Request $request, $id)
    {
        $kalkulasi = KalkulasiSpk::findOrFail($id);
        $data_json = [];
        $total_semua = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $aktual_baris = $request->total_kg_aktual[$index] ?? 0;
            $total_semua += $aktual_baris;

            $data_json[] = [
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $request->lebar_cm[$index] ?? 0,
                'panjang_m' => $request->panjang_m[$index] ?? 0,
                'faktor_bm' => $request->faktor_bm[$index] ?? 1.36,
                'faktor_cm' => $request->faktor_cm[$index] ?? 1.46,
                'gsm_db' => $request->gsm_db[$index] ?? 0,
                'gsm_bm' => $request->gsm_bm[$index] ?? 0,
                'gsm_bl' => $request->gsm_bl[$index] ?? 0,
                'gsm_cm' => $request->gsm_cm[$index] ?? 0,
                'gsm_cl' => $request->gsm_cl[$index] ?? 0,
                'akt_db' => $request->aktual_db[$index] ?? 0,
                'akt_bm' => $request->aktual_bm[$index] ?? 0,
                'akt_bl' => $request->aktual_bl[$index] ?? 0,
                'akt_cm' => $request->aktual_cm[$index] ?? 0,
                'akt_cl' => $request->aktual_cl[$index] ?? 0,
                'total_aktual' => $aktual_baris,
            ];
        }

        $kalkulasi->update([
            'data_spk' => $data_json,
            'total_aktual_semua' => $total_semua
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', 'Sesi Batch ' . $kalkulasi->kode_sesi . ' berhasil diperbarui!');
    }

    public function menu2()
    {
        return view('spk.menu2');
    }

    public function kalkulasiOtomatis()
    {
        $shifts = \App\Models\Shift::orderBy('id', 'desc')->get();
        return view('spk.otomatis', compact('shifts'));
    }

    public function storeOtomatis(Request $request)
    {
        $request->validate([
            'transaksi_roll_id' => 'required',
            'no_spk' => 'required|array',
            'panjang_m' => 'required|array',
        ]);

        $rollAktual = TransaksiRoll::with('masterKertas')->findOrFail($request->transaksi_roll_id);
        $totalBeratAktual = $rollAktual->sisa_kilo_awal - $rollAktual->sisa_kilo_akhir;
        $totalMeterGabungan = array_sum($request->panjang_m);

        $posisi = $rollAktual->posisi_mesin; 
        $faktor = 1.0; 
        if ($posisi == 'BM') { $faktor = 1.36; }
        if ($posisi == 'CM') { $faktor = 1.46; }

        $lebar_m = ($rollAktual->masterKertas->lebar ?? 0) / 100; 
        $gsm = $rollAktual->masterKertas->gsm ?? 0; 

        $data_json = [];

        foreach ($request->no_spk as $index => $no_spk) {
            $panjang_spk = $request->panjang_m[$index] ?? 0;
            $rasio = $totalMeterGabungan > 0 ? ($panjang_spk / $totalMeterGabungan) : 0;
            $jatah_aktual_posisi = $rasio * $totalBeratAktual;
            $teori_baris = ($panjang_spk * $lebar_m * $gsm * $faktor) / 1000;

            $data_json[] = [
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $lebar_m * 100,
                'panjang_m' => $panjang_spk,
                'faktor_bm' => $posisi == 'BM' ? $faktor : 1.36,
                'faktor_cm' => $posisi == 'CM' ? $faktor : 1.46,
                'gsm_db' => $posisi == 'DB' ? $gsm : 0,
                'gsm_bm' => $posisi == 'BM' ? $gsm : 0,
                'gsm_bl' => $posisi == 'BL' ? $gsm : 0,
                'gsm_cm' => $posisi == 'CM' ? $gsm : 0,
                'gsm_cl' => $posisi == 'CL' ? $gsm : 0,
                'akt_db' => $posisi == 'DB' ? $jatah_aktual_posisi : 0,
                'akt_bm' => $posisi == 'BM' ? $jatah_aktual_posisi : 0,
                'akt_bl' => $posisi == 'BL' ? $jatah_aktual_posisi : 0,
                'akt_cm' => $posisi == 'CM' ? $jatah_aktual_posisi : 0,
                'akt_cl' => $posisi == 'CL' ? $jatah_aktual_posisi : 0,
                'total_aktual' => $jatah_aktual_posisi,
                'teori_kg' => $teori_baris
            ];
        }

        \App\Models\KalkulasiSpk::create([
            'kode_sesi' => 'AUTO-' . date('Ymd-His'),
            'data_spk' => $data_json,
            'total_aktual_semua' => $totalBeratAktual
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', 'Sistem berhasil membagi otomatis beban ' . $totalBeratAktual . ' Kg ke ' . count($request->no_spk) . ' SPK!');
    }

    public function sapuJagat()
    {
        $shifts = \App\Models\Shift::orderBy('id', 'desc')->get();
        return view('spk.otomatis', compact('shifts'));
    }

    // =========================================================================
    // KAMUS PENERJEMAH ASLI & STABIL MILIK ANDA (TETAP DIPERTAHANKAN)
    // =========================================================================
    public function terjemahkanKode($kode)
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

    // =========================================================================
    // UPGRADE UTAMA: STORE SAPU JAGAT (DENGAN SISTEM MERGE BUCKET KRAFT)
    // =========================================================================
    public function storeSapuJagat(Request $request)
    {
        $request->validate([
            'shift_id' => 'required',
            'no_spk' => 'required|array',
            'lebar_mm' => 'required|array',
            'panjang_m' => 'required|array',
        ]);

        $shift_id = $request->shift_id;
        $posisiList = ['DB' => 'gsm_db', 'BM' => 'gsm_bm', 'BL' => 'gsm_bl', 'CM' => 'gsm_cm', 'CL' => 'gsm_cl'];

        // --- TAHAP 1: KUMPULKAN DATA STOK FORKLIFT (GABUNGKAN B & T KE KOLAM K) ---
        $transaksi = \App\Models\TransaksiRoll::with('masterKertas')->where('shift_id', $shift_id)->get();
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

        // --- TAHAP 2: HITUNG TOTAL METER MONITOR (GABUNGKAN B & T KE KOLAM K) ---
        $meterGroup = [];
        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            foreach ($posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $lebar_pakai_cm = $lebar_cm_global;
                $input_gsm = $input_mentah;

                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_pakai_cm = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar === '') continue;

                // MERGE BUCKET: Arahkan meteran ini ke kolam K
                $prorate_gsm = $gsm_standar;
                $first_char = substr($gsm_standar, 0, 1);
                if ($first_char === 'B' || $first_char === 'T') {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                $kunci = $lebar_pakai_cm . '_' . $prorate_gsm . '_' . $pos;
                if (!isset($meterGroup[$kunci])) { $meterGroup[$kunci] = 0; }
                $meterGroup[$kunci] += $meter;
            }
        }

        // --- TAHAP 3: EKSEKUSI PEMBAGIAN PRORATE ---
        $data_json = [];
        $grand_total_aktual = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            $jatah = ['DB' => 0, 'BM' => 0, 'BL' => 0, 'CM' => 0, 'CL' => 0];
            $total_baris_aktual = 0;

            foreach ($posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $lebar_pakai_cm = $lebar_cm_global;
                $input_gsm = $input_mentah;

                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_pakai_cm = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar === '') continue;

                // TARIK JATAH DARI KOLAM K YANG SUDAH DIGABUNG (B + T + K)
                $prorate_gsm = $gsm_standar;
                $first_char = substr($gsm_standar, 0, 1);
                if ($first_char === 'B' || $first_char === 'T') {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                $kunci = $lebar_pakai_cm . '_' . $prorate_gsm . '_' . $pos;
                $total_meter_spek = $meterGroup[$kunci] ?? 0;
                $total_kg_forklift = $forkliftGroup[$kunci] ?? 0;

                $rasio = $total_meter_spek > 0 ? ($meter / $total_meter_spek) : 0;
                $jatah[$pos] = $rasio * $total_kg_forklift;
                $total_baris_aktual += $jatah[$pos];
            }

            $grand_total_aktual += $total_baris_aktual;

            $data_json[] = [
                'seq' => $request->seq[$index] ?? '',
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $lebar_cm_global,
                'panjang_m' => $meter,
                'faktor_bm' => 1.36, 'faktor_cm' => 1.46, 
                'gsm_db' => strtoupper($request->gsm_db[$index] ?? ''), 
                'gsm_bm' => strtoupper($request->gsm_bm[$index] ?? ''), 
                'gsm_bl' => strtoupper($request->gsm_bl[$index] ?? ''), 
                'gsm_cm' => strtoupper($request->gsm_cm[$index] ?? ''), 
                'gsm_cl' => strtoupper($request->gsm_cl[$index] ?? ''), 
                'akt_db' => $jatah['DB'], 'akt_bm' => $jatah['BM'], 'akt_bl' => $jatah['BL'], 'akt_cm' => $jatah['CM'], 'akt_cl' => $jatah['CL'],
                'total_aktual' => $total_baris_aktual
            ];
        }

        \App\Models\KalkulasiSpk::create([
            'kode_sesi' => 'SAPU-' . date('Ymd-His'),
            'data_spk' => $data_json,
            'total_aktual_semua' => $grand_total_aktual,
            'shift_id' => $shift_id 
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', '✅ Boom! Data berhasil dicocokkan! Varian Kraft telah ter-merge sempurna!');
    }

    // =========================================================================
    // UPGRADE UTAMA: RE-RUN MATCHING (DENGAN SISTEM MERGE BUCKET KRAFT)
    // =========================================================================
    public function reRunSapuJagat(Request $request, $id)
    {
        $request->validate([
            'shift_id' => 'required',
            'no_spk' => 'required|array',
            'lebar_mm' => 'required|array',
            'panjang_m' => 'required|array',
        ]);

        $kalkulasi = \App\Models\KalkulasiSpk::findOrFail($id);
        $shift_id = $request->shift_id;
        $posisiList = ['DB' => 'gsm_db', 'BM' => 'gsm_bm', 'BL' => 'gsm_bl', 'CM' => 'gsm_cm', 'CL' => 'gsm_cl'];

        // --- TAHAP 1: AMBIL DATA STOK FORKLIFT (GABUNGKAN B & T KE KOLAM K) ---
        $transaksi = \App\Models\TransaksiRoll::with('masterKertas')->where('shift_id', $shift_id)->get();
        $forkliftGroup = []; 
        foreach ($transaksi as $t) {
            $lebar_db = floatval($t->masterKertas->lebar ?? 0);
            if ($lebar_db > 500) { $lebar_db = $lebar_db / 10; } 
            
            $gsm_standar = $this->terjemahkanKode($t->masterKertas->gsm ?? '');
            if ($gsm_standar === '') continue;

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

        // --- TAHAP 2: HITUNG ULANG METER REVISI (GABUNGKAN B & T KE KOLAM K) ---
        $meterGroup = [];
        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            foreach ($posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $lebar_pakai_cm = $lebar_cm_global;
                $input_gsm = $input_mentah;

                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_pakai_cm = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar === '') continue;

                $prorate_gsm = $gsm_standar;
                $first_char = substr($gsm_standar, 0, 1);
                if ($first_char === 'B' || $first_char === 'T') {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                $kunci = $lebar_pakai_cm . '_' . $prorate_gsm . '_' . $pos;
                if (!isset($meterGroup[$kunci])) { $meterGroup[$kunci] = 0; }
                $meterGroup[$kunci] += $meter;
            }
        }

        // --- TAHAP 3: RE-PRORATE ULANG ---
        $data_json = [];
        $grand_total_aktual = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            $jatah = ['DB' => 0, 'BM' => 0, 'BL' => 0, 'CM' => 0, 'CL' => 0];
            $total_baris_aktual = 0;

            foreach ($posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $lebar_pakai_cm = $lebar_cm_global;
                $input_gsm = $input_mentah;

                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_pakai_cm = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar === '') continue;

                $prorate_gsm = $gsm_standar;
                $first_char = substr($gsm_standar, 0, 1);
                if ($first_char === 'B' || $first_char === 'T') {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                $kunci = $lebar_pakai_cm . '_' . $prorate_gsm . '_' . $pos;
                $total_meter_spek = $meterGroup[$kunci] ?? 0;
                $total_kg_forklift = $forkliftGroup[$kunci] ?? 0;

                $rasio = $total_meter_spek > 0 ? ($meter / $total_meter_spek) : 0;
                $jatah[$pos] = $rasio * $total_kg_forklift;
                $total_baris_aktual += $jatah[$pos];
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
                'akt_db' => $jatah['DB'], 'akt_bm' => $jatah['BM'], 'akt_bl' => $jatah['BL'], 'akt_cm' => $jatah['CM'], 'akt_cl' => $jatah['CL'],
                'total_aktual' => $total_baris_aktual
            ];
        }

        $kalkulasi->update([
            'data_spk' => $data_json,
            'total_aktual_semua' => $grand_total_aktual
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', '🔄 Selesai! Data Re-Run Matching sukses dikalibrasi!');
    }

    // AI AUTO-FILL GROQ
    public function scanFotoAi(Request $request)
    {
        $request->validate([
            'foto_spek' => 'required|image|max:4096', 
            'foto_meter' => 'required|image|max:4096', 
        ]);

        try {
            $imgSpek = base64_encode(file_get_contents($request->file('foto_spek')->path()));
            $imgMeter = base64_encode(file_get_contents($request->file('foto_meter')->path()));

            $prompt = "Kamu adalah sistem ekstraksi OCR untuk monitor mesin corrugator pabrik kardus. Aku memberikan 2 foto dari layar monitor. Foto 1 berisi spesifikasi: Seq, ID SPK, Customer, Width (Lebar), dan deretan kertas (DB, BM, BL, CM, CL). Foto 2 berisi target jalan: Seq dan Length (Meter). ATURAN WAJIB (HARUS DIIKUTI 100%): 1. GABUNGKAN data dari kedua foto berdasarkan nomor 'Seq' yang sama. 2. FORMAT SPK: Gabungkan ID SPK dan Customer dengan separator garis miring ' / '. Masukkan ke key 'spk'. 3. PEMISAHAN KERTAS (SANGAT PENTING): JANGAN PERNAH menggabungkan semua kertas ke dalam satu key 'db'! Pisahkan kertas tersebut sesuai urutannya ke key 'db', 'bm', 'bl', 'cm', 'cl'. 4. KEMBALIKAN HANYA JSON murni dengan key 'data' berupa array of objects. Dilarang keras memberikan teks pengantar atau penutup Markdown (seperti ```json).";

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('[https://api.groq.com/openai/v1/chat/completions](https://api.groq.com/openai/v1/chat/completions)', [
                'model' => 'llama-3.2-90b-vision-preview', 
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imgSpek}"]],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imgMeter}"]]
                        ]
                    ]
                ],
                'response_format' => ['type' => 'json_object'], 
                'temperature' => 0.1, 
            ]);

            if ($response->successful()) {
                $aiResult = json_decode($response->json()['choices'][0]['message']['content'], true);
                return response()->json(['success' => true, 'data' => $aiResult['data'] ?? []]);
            }

            return response()->json(['success' => false, 'message' => 'API Error: ' . $response->body()]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
        }
    }
}