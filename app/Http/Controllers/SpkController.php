<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spk;
use App\Models\KalkulasiSpk;
use App\Models\TransaksiRoll;

class SpkController extends Controller
{
    // Halaman Menu & List SPK yang Tersimpan
    // Halaman Menu Utama SPK
    public function index()
    {
        return view('spk.index'); 
    }

    // Halaman Khusus Riwayat / List Data SPK
    // HALAMAN RIWAYAT BATCH (Update)
    public function riwayat(Request $request)
    {
        $search = $request->input('search');
        $query = KalkulasiSpk::orderBy('id', 'desc');

        if ($search) {
            // Pencarian cerdas: cari berdasarkan Kode Sesi ATAU Nomor SPK yang tersimpan di dalam JSON
            $query->where('kode_sesi', 'LIKE', '%' . $search . '%')
                  ->orWhere('data_spk', 'LIKE', '%' . $search . '%');
        }

        // Tampilkan 10 Sesi per halaman
        $kalkulasis = $query->paginate(10)->appends(['search' => $search]);
        return view('spk.riwayat', compact('kalkulasis', 'search'));
    }

    // FUNGSI HAPUS BATCH (Update)
    public function destroy($id)
    {
        KalkulasiSpk::findOrFail($id)->delete();
        return redirect('/hitung-spk/riwayat')->with('success', 'Satu Sesi Batch SPK berhasil dihapus secara keseluruhan!');
    }


    public function kalkulasiManual()
    {
        return view('spk.manual');
    }

    

    // SIMPAN JSON
    public function storeManual(Request $request)
    {
        $request->validate([
            'no_spk' => 'required|array',
            'no_spk.*' => 'required|string',
        ]);

        $data_json = []; // Wadah keranjang
        $total_semua = 0;

        // Susun semua form clone ke dalam 1 keranjang Array
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
                // Simpan juga hasil proporsionalnya
                'akt_db' => $request->aktual_db[$index] ?? 0,
                'akt_bm' => $request->aktual_bm[$index] ?? 0,
                'akt_bl' => $request->aktual_bl[$index] ?? 0,
                'akt_cm' => $request->aktual_cm[$index] ?? 0,
                'akt_cl' => $request->aktual_cl[$index] ?? 0,
                'total_aktual' => $aktual_baris,
            ];
        }

        // Simpan 1 baris ke Database!
        KalkulasiSpk::create([
            'kode_sesi' => 'KALK-' . date('Ymd-His'),
            'data_spk' => $data_json, // Otomatis jadi JSON
            'total_aktual_semua' => $total_semua
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', '1 Sesi Batch (Berisi '.count($request->no_spk).' SPK) berhasil disimpan!');
    }

    // TAMPILKAN HALAMAN EDIT BATCH
    public function edit($id)
    {
        $kalkulasi = KalkulasiSpk::findOrFail($id);
        // Bawa data JSON ke View
        return view('spk.edit_batch', compact('kalkulasi'));
    }

    // SIMPAN UPDATE BATCH JSON
    public function update(Request $request, $id)
    {
        // Logikanya persis seperti Store, kita bungkus ulang lalu timpa yang lama
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
        // $spk = Spk::findOrFail($id);
        return view('spk.menu2');
    }

    // 1. TAMPILKAN HALAMAN FORM OTOMATIS
    public function kalkulasiOtomatis()
    {
        // 1. Ambil data Shift asli dari database untuk diisi ke Dropdown
        $shifts = \App\Models\Shift::orderBy('id', 'desc')->get();

        // 2. Buka halaman otomatis.blade.php dan BAWA data $shifts tersebut
        return view('spk.otomatis', compact('shifts'));
    }

    // 2. PROSES HITUNG & SIMPAN OTOMATIS (BACKEND PRORATE)
    public function storeOtomatis(Request $request)
    {
        $request->validate([
            'transaksi_roll_id' => 'required',
            'no_spk' => 'required|array',
            'panjang_m' => 'required|array',
        ]);

        // Ambil data roll aktual dari scanner forklift
        $rollAktual = TransaksiRoll::with('masterKertas')->findOrFail($request->transaksi_roll_id);
        
        // Hitung total berat murni yang ludes terpakai di mesin (Awal - Sisa Akhir)
        $totalBeratAktual = $rollAktual->sisa_kilo_awal - $rollAktual->sisa_kilo_akhir;
        
        // Hitung total meter lari gabungan dari semua SPK yang diinput Admin
        $totalMeterGabungan = array_sum($request->panjang_m);

        // Cari tahu faktor kerut berdasarkan posisi mesin roll tersebut
        $posisi = $rollAktual->posisi_mesin; // DB, BM, BL, CM, CL
        $faktor = 1.0; // Default untuk kertas lurus (DB, BL, CL)
        if ($posisi == 'BM') { $faktor = 1.36; }
        if ($posisi == 'CM') { $faktor = 1.46; }

        $lebar_m = ($rollAktual->masterKertas->lebar ?? 0) / 100; // Ambil lebar dari master
        $gsm = $rollAktual->masterKertas->gsm ?? 0; // Ambil gsm dari master

        $data_json = [];

        // Looping untuk membagi berat secara otomatis per SPK
        foreach ($request->no_spk as $index => $no_spk) {
            $panjang_spk = $request->panjang_m[$index] ?? 0;
            
            // RUMUS UTAMA: (Meter SPK / Total Meter) * Total Berat Timbangan Forklift
            $rasio = $totalMeterGabungan > 0 ? ($panjang_spk / $totalMeterGabungan) : 0;
            $jatah_aktual_posisi = $rasio * $totalBeratAktual;

            // Hitung juga teorinya sebagai perbandingan pembukuan
            $teori_baris = ($panjang_spk * $lebar_m * $gsm * $faktor) / 1000;

            // Susun struktur data untuk disimpan ke kolom JSON
            $data_json[] = [
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $lebar_m * 100,
                'panjang_m' => $panjang_m,
                'faktor_bm' => $posisi == 'BM' ? $faktor : 1.36,
                'faktor_cm' => $posisi == 'CM' ? $faktor : 1.46,
                'gsm_db' => $posisi == 'DB' ? $gsm : 0,
                'gsm_bm' => $posisi == 'BM' ? $gsm : 0,
                'gsm_bl' => $posisi == 'BL' ? $gsm : 0,
                'gsm_cm' => $posisi == 'CM' ? $gsm : 0,
                'gsm_cl' => $posisi == 'CL' ? $gsm : 0,
                // Jatah berat hasil pembagian otomatis sistem
                'akt_db' => $posisi == 'DB' ? $jatah_aktual_posisi : 0,
                'akt_bm' => $posisi == 'BM' ? $jatah_aktual_posisi : 0,
                'akt_bl' => $posisi == 'BL' ? $jatah_aktual_posisi : 0,
                'akt_cm' => $posisi == 'CM' ? $jatah_aktual_posisi : 0,
                'akt_cl' => $posisi == 'CL' ? $jatah_aktual_posisi : 0,
                'total_aktual' => $jatah_aktual_posisi,
                'teori_kg' => $teori_baris
            ];
        }

        // Simpan ke tabel kalkulasi_spks dalam format JSON Sesi Batch
        \App\Models\KalkulasiSpk::create([
            'kode_sesi' => 'AUTO-' . date('Ymd-His'),
            'data_spk' => $data_json,
            'total_aktual_semua' => $totalBeratAktual
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', 'Sistem berhasil membagi otomatis beban ' . $totalBeratAktual . ' Kg ke ' . count($request->no_spk) . ' SPK!');
    }

    // ========================================================
    // FITUR SAPU JAGAT (MATCHING OTOMATIS FORKLIFT VS MESIN)
    // ========================================================

    // ========================================================
    // 1. TAMPILKAN HALAMAN SAPU JAGAT
    // ========================================================
    public function sapuJagat()
    {
        $shifts = \App\Models\Shift::orderBy('id', 'desc')->get();
        return view('spk.otomatis', compact('shifts'));
    }

    // =========================================================================
    // 2. KAMUS PENERJEMAH DINAMIS (MEMISAHKAN KRAFT, BKRAFT, TESTLINER, MEDIUM)
    // =========================================================================
    public function terjemahkanKode($kode)
    {
        if (!$kode || $kode == '-') return '';
        
        // Bersihkan spasi dan jadikan huruf besar (Contoh: "B 150" jadi "B150")
        $kode = strtoupper(str_replace(' ', '', $kode));

        // ---------------------------------------------------------------------
        // POLA 1: FORMAT DATABASE / FORKLIFT (Huruf di DEPAN, Angka di BELAKANG)
        // ---------------------------------------------------------------------
        if (preg_match('/^([A-Z]+)(\d+)/', $kode, $matches)) {
            $huruf_depan = $matches[1];
            $angka_belakang = $matches[2];

            // Jika awalan W murni, standarkan jadi WK (White Kraft)
            if ($huruf_depan == 'W') {
                $huruf_depan = 'WK';
            }
            
            // --- ATURAN BARU: T jadikan otomatis K ---
            if ($huruf_depan == 'T') {
                $huruf_depan = 'K';
            }

            return $huruf_depan . $angka_belakang; 
        }

        // ---------------------------------------------------------------------
        // POLA 2: FORMAT MONITOR MESIN (Angka di DEPAN, Huruf di BELAKANG)
        // ---------------------------------------------------------------------
        if (preg_match('/^(\d+)([A-Z]+)/', $kode, $matches)) {
            $angka_depan = $matches[1];
            $huruf_belakang = substr($matches[2], 0, 1); 
            
            // Validasi rumpun huruf standar pabrik Anda
            if (!in_array($huruf_belakang, ['K', 'B', 'T', 'M', 'W'])) {
                $huruf_belakang = 'M'; 
            }

            // Rumus konversi angka monitor ke angka database asli
            $angka_db = $angka_depan;
            if ($angka_depan == '101') $angka_db = '100';
            if ($angka_depan == '111') $angka_db = '110';
            if ($angka_depan == '113') $angka_db = '112';
            if ($angka_depan == '127') $angka_db = '125';
            if ($angka_depan == '137') $angka_db = '135';
            
            // Aturan Khusus angka 160
            if ($angka_depan == '160') {
                $angka_db = ($huruf_belakang == 'W') ? '140' : '150';
            }

            // Sesuaikan prefix huruf untuk database akhir
            $prefix_db = $huruf_belakang;
            if ($huruf_belakang == 'W') {
                $prefix_db = 'WK';
            }
            
            // --- ATURAN BARU: T jadikan otomatis K ---
            if ($prefix_db == 'T') {
                $prefix_db = 'K';
            }

            return $prefix_db . $angka_db; 
        }
        
        return $kode; 
    }

    // ========================================================
    // 3. EKSEKUSI PENCOCOKAN & PRORATE (DENGAN FITUR SLASH '/')
    // ========================================================
    public function storeSapuJagat(Request $request)
    {
        $request->validate([
            'shift_id' => 'required',
            'no_spk' => 'required|array',
            'lebar_mm' => 'required|array',
            'panjang_m' => 'required|array',
        ]);

        $shift_id = $request->shift_id;

        // --- TAHAP 1: KUMPULKAN DATA FORKLIFT ---
        $transaksi = \App\Models\TransaksiRoll::with('masterKertas')
            ->where('shift_id', $shift_id)
            ->get();

        $forkliftGroup = []; 
        foreach ($transaksi as $t) {
            $lebar_db = floatval($t->masterKertas->lebar ?? 0);
            if ($lebar_db > 500) { $lebar_db = $lebar_db / 10; } 
            
            $gsm_standar = $this->terjemahkanKode($t->masterKertas->gsm ?? '');
            $posisi = strtoupper($t->posisi_mesin); 
            
            $sisa_awal = floatval($t->sisa_kilo_awal);
            $sisa_akhir = floatval($t->sisa_kilo_akhir);
            $terpakai = ($sisa_akhir <= 0) ? $sisa_awal : ($sisa_awal - $sisa_akhir);

            $kunci = $lebar_db . '_' . $gsm_standar . '_' . $posisi; 
            if (!isset($forkliftGroup[$kunci])) { $forkliftGroup[$kunci] = 0; }
            $forkliftGroup[$kunci] += $terpakai; 
        }

        // --- TAHAP 2: KUMPULKAN DATA SPK MONITOR (BISA BACA GARIS MIRING '/') ---
        $meterGroup = [];
        $posisiList = ['DB' => 'gsm_db', 'BM' => 'gsm_bm', 'BL' => 'gsm_bl', 'CM' => 'gsm_cm', 'CL' => 'gsm_cl'];

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            foreach ($posisiList as $pos => $inputName) {
                $input_mentah = $request->input($inputName)[$index] ?? '';
                if ($input_mentah === '' || $input_mentah === '-') continue;

                $lebar_pakai_cm = $lebar_cm_global; // Pakai lebar SPK secara default
                $input_gsm = $input_mentah;

                // FITUR PINTAR: Membelah teks jika ada garis miring (misal: 160BB/165)
                if (strpos($input_mentah, '/') !== false) {
                    $parts = explode('/', $input_mentah);
                    $input_gsm = $parts[0]; // Kiri: Sandi kertas
                    $lebar_khusus = floatval($parts[1]); // Kanan: Lebar khusus
                    $lebar_pakai_cm = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                }

                $gsm_standar = $this->terjemahkanKode($input_gsm);
                if ($gsm_standar !== '') { 
                    $kunci = $lebar_pakai_cm . '_' . $gsm_standar . '_' . $pos;
                    if (!isset($meterGroup[$kunci])) { $meterGroup[$kunci] = 0; }
                    $meterGroup[$kunci] += $meter;
                }
            }
        }

        // --- TAHAP 3: EKSEKUSI PRORATE ---
        $data_json = [];
        $grand_total_aktual = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            $jatah = ['DB' => 0, 'BM' => 0, 'BL' => 0, 'CM' => 0, 'CL' => 0];
            $total_baris_aktual = 0;

            // Simpan Data Mentah Asli untuk Audit Trail di Halaman Edit
            $gsm_db_mentah = $request->gsm_db[$index] ?? '';
            $gsm_bm_mentah = $request->gsm_bm[$index] ?? '';
            $gsm_bl_mentah = $request->gsm_bl[$index] ?? '';
            $gsm_cm_mentah = $request->gsm_cm[$index] ?? '';
            $gsm_cl_mentah = $request->gsm_cl[$index] ?? '';

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

                if ($gsm_standar !== '') {
                    $kunci = $lebar_pakai_cm . '_' . $gsm_standar . '_' . $pos;
                    
                    $total_meter_spek = $meterGroup[$kunci] ?? 0;
                    $total_kg_forklift = $forkliftGroup[$kunci] ?? 0;

                    $rasio = $total_meter_spek > 0 ? ($meter / $total_meter_spek) : 0;
                    $jatah[$pos] = $rasio * $total_kg_forklift;
                    
                    $total_baris_aktual += $jatah[$pos];
                }
            }

            $grand_total_aktual += $total_baris_aktual;

            $data_json[] = [
                'seq' => $request->seq[$index] ?? '',
                'no_spk' => strtoupper($no_spk),
                'lebar_cm' => $lebar_cm_global,
                'panjang_m' => $meter,
                'faktor_bm' => 1.36, 'faktor_cm' => 1.46, 
                'gsm_db' => strtoupper($gsm_db_mentah), 
                'gsm_bm' => strtoupper($gsm_bm_mentah), 
                'gsm_bl' => strtoupper($gsm_bl_mentah), 
                'gsm_cm' => strtoupper($gsm_cm_mentah), 
                'gsm_cl' => strtoupper($gsm_cl_mentah), 
                'akt_db' => $jatah['DB'],
                'akt_bm' => $jatah['BM'],
                'akt_bl' => $jatah['BL'],
                'akt_cm' => $jatah['CM'],
                'akt_cl' => $jatah['CL'],
                'total_aktual' => $total_baris_aktual
            ];
        }

        \App\Models\KalkulasiSpk::create([
            'kode_sesi' => 'SAPU-' . date('Ymd-His'),
            'data_spk' => $data_json,
            'total_aktual_semua' => $grand_total_aktual,
            'shift_id' => $shift_id // <-- TAMBAHKAN INI agar database merekam ID Shift-nya
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', '✅ Boom! Data berhasil dicocokkan! (Termasuk deteksi lebar khusus dari format garis miring)');
    }

    // ========================================================
    // 4. FITUR BARU: RE-RUN MATCHING DARI HALAMAN EDIT
    // ========================================================
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

        // --- TAHAP 1: AMBIL ULANG DATA FORKLIFT ---
        $transaksi = \App\Models\TransaksiRoll::with('masterKertas')->where('shift_id', $shift_id)->get();
        $forkliftGroup = []; 
        foreach ($transaksi as $t) {
            $lebar_db = floatval($t->masterKertas->lebar ?? 0);
            if ($lebar_db > 500) { $lebar_db = $lebar_db / 10; } 
            $gsm_standar = $this->terjemahkanKode($t->masterKertas->gsm ?? '');
            $posisi = strtoupper($t->posisi_mesin); 
            $sisa_awal = floatval($t->sisa_kilo_awal);
            $sisa_akhir = floatval($t->sisa_kilo_akhir);
            $terpakai = ($sisa_akhir <= 0) ? $sisa_awal : ($sisa_awal - $sisa_akhir);

            $kunci = $lebar_db . '_' . $gsm_standar . '_' . $posisi; 
            if (!isset($forkliftGroup[$kunci])) { $forkliftGroup[$kunci] = 0; }
            $forkliftGroup[$kunci] += $terpakai; 
        }

        // --- TAHAP 2: HITUNG ULANG METER MONITOR YANG SUDAH DIREVISI ---
        $meterGroup = [];
        $posisiList = ['DB' => 'gsm_db', 'BM' => 'gsm_bm', 'BL' => 'gsm_bl', 'CM' => 'gsm_cm', 'CL' => 'gsm_cl'];

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
                if ($gsm_standar !== '') { 
                    $kunci = $lebar_pakai_cm . '_' . $gsm_standar . '_' . $pos;
                    if (!isset($meterGroup[$kunci])) { $meterGroup[$kunci] = 0; }
                    $meterGroup[$kunci] += $meter;
                }
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

                if ($gsm_standar !== '') {
                    $kunci = $lebar_pakai_cm . '_' . $gsm_standar . '_' . $pos;
                    $total_meter_spek = $meterGroup[$kunci] ?? 0;
                    $total_kg_forklift = $forkliftGroup[$kunci] ?? 0;

                    $rasio = $total_meter_spek > 0 ? ($meter / $total_meter_spek) : 0;
                    $jatah[$pos] = $rasio * $total_kg_forklift;
                    
                    $total_baris_aktual += $jatah[$pos];
                }
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

        // UPDATE DATABASE DATA LAMA DENGAN HASIL KALKULASI BARU
        $kalkulasi->update([
            'data_spk' => $data_json,
            'total_aktual_semua' => $grand_total_aktual
        ]);

        return redirect('/hitung-spk/riwayat')->with('success', '🔄 Selesai! Data monitor berhasil direvisi dan sistem telah menghitung ulang porsi Kg-nya!');
    }

    // ========================================================
    // 5. FITUR AI: EKSTRAK FOTO MONITOR MENGGUNAKAN GROQ API
    // ========================================================
    public function scanFotoAi(Request $request)
    {
        $request->validate([
            'foto_spek' => 'required|image|max:4096', // Foto 1: Spesifikasi Kertas
            'foto_meter' => 'required|image|max:4096', // Foto 2: Meteran Run
        ]);

        try {
            // Ubah gambar fisik menjadi string Base64
            $imgSpek = base64_encode(file_get_contents($request->file('foto_spek')->path()));
            $imgMeter = base64_encode(file_get_contents($request->file('foto_meter')->path()));

            // Prompt super spesifik (Prompt Engineering)
            // Prompt super spesifik (Prompt Engineering tingkat Dewa)
            $prompt = "Kamu adalah sistem ekstraksi OCR untuk monitor mesin corrugator pabrik kardus. 
            Aku memberikan 2 foto dari layar monitor. 
            Foto 1 berisi spesifikasi: Seq, ID SPK, Customer, Width (Lebar), dan deretan kertas (DB, BM, BL, CM, CL). 
            Foto 2 berisi target jalan: Seq dan Length (Meter).
            
            ATURAN WAJIB (HARUS DIIKUTI 100%):
            1. GABUNGKAN data dari kedua foto berdasarkan nomor 'Seq' yang sama.
            2. FORMAT SPK: Gabungkan ID SPK dan Customer dengan separator garis miring ' / '. (Contoh: jika ID '12345' dan Customer 'MERCU', maka tulis '12345 / MERCU'). Masukkan ke key 'spk'.
            3. PEMISAHAN KERTAS (SANGAT PENTING): JANGAN PERNAH menggabungkan semua kertas ke dalam satu key 'db'! Pisahkan kertas tersebut sesuai urutannya ke key 'db', 'bm', 'bl', 'cm', 'cl'. 
               - Jika di layar tertulis berjejer '160WS 150SD 160KS', maka pecah menjadi: db: '160WS', bm: '150SD', bl: '160KS', cm: '', cl: ''.
               - Kosongkan string (\"\") jika tidak ada kertas di posisi tersebut (misal untuk Single Wall, cm dan cl pasti kosong).
            4. KEMBALIKAN HANYA JSON murni dengan key 'data' berupa array of objects. Dilarang keras memberikan teks pengantar atau penutup Markdown (seperti ```json).
            
            Contoh Format Output Yang Benar:
            {
              \"data\": [
                {\"seq\": \"10\", \"spk\": \"12345 / KARTA\", \"lebar\": \"1650\", \"meter\": \"1500\", \"db\": \"160WS\", \"bm\": \"150SD\", \"bl\": \"160KS\", \"cm\": \"\", \"cl\": \"\"}
              ]
            }";

            // Tembak ke Groq API
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'meta-llama/llama-4-scout-17b-16e-instruct', // Ganti ke llama-3.2-90b-vision-preview jika model ini belum support image di Groq
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
                'response_format' => ['type' => 'json_object'], // Paksa AI hanya membalas JSON
                'temperature' => 0.1, // Suhu sangat rendah agar tidak halusinasi
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