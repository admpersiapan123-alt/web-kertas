<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckerController extends Controller
{
    // --- FUNGSI SAKTI PEMBEDAH KODE (Kamus Baru sesuai PDF) ---
    private function bedahKodeKertas($kode_mentah) {
        if (!$kode_mentah || $kode_mentah == '' || $kode_mentah == '-') return null;
        
        $kode_bersih = strtoupper(preg_replace('/\s+/', '', $kode_mentah));
        if (strpos($kode_bersih, '/') !== false) {
            $kode_bersih = explode('/', $kode_bersih)[0];
        }

        if (preg_match('/^(\d+)([A-Z]+)/', $kode_bersih, $matches)) {
            $angka = $matches[1];
            $huruf_planning = $matches[2]; // ex: TF, MY, MC, KE, dll

            // 1. Standarisasi Angka & Tipe Kertas
            if ($angka == '101') $angka = '100';
            if ($angka == '111') $angka = '110';
            if ($angka == '113') $angka = '112';
            if ($angka == '127') $angka = '125';
            if ($angka == '137') $angka = '135';
            if ($angka == '160') {
                $angka = (strpos($huruf_planning, 'W') !== false) ? '140' : '150';
            }

            $tipe = 'M'; 
            if (preg_match('/[K|B|T|S|L]/', substr($huruf_planning, 0, 1))) $tipe = 'K'; 
            if (strpos($huruf_planning, 'W') !== false) $tipe = 'WK';

            // 2. TERJEMAHAN HURUF PERTAMA (Gramatur/Jenis) sesuai PDF
            $h1 = '';
            if ($tipe == 'M' && $angka == '110') $h1 = 'G';
            elseif ($tipe == 'M' && $angka == '125') $h1 = 'A';
            elseif ($tipe == 'M' && $angka == '150') $h1 = 'I';
            elseif ($tipe == 'M' && $angka == '135') $h1 = 'N';
            elseif ($tipe == 'M' && $angka == '140') $h1 = 'U';
            elseif ($tipe == 'K' && $angka == '125') $h1 = 'B';
            elseif ($tipe == 'K' && $angka == '200') $h1 = 'R';
            elseif ($tipe == 'K' && $angka == '275') $h1 = 'X';
            elseif ($tipe == 'K' && $angka == '135') $h1 = 'J';
            elseif ($tipe == 'K' && $angka == '110') $h1 = 'L';
            elseif ($tipe == 'K' && $angka == '150') $h1 = 'M';
            elseif ($tipe == 'WK' && $angka == '140') $h1 = 'O'; 
            elseif ($tipe == 'WK' && $angka == '200') $h1 = 'T';
            else $h1 = substr($tipe, 0, 1); // Fallback

            // 3. TERJEMAHAN HURUF KEDUA (Supplier) sesuai PDF
            $h2 = '';
            $mapSupplier = [
                'F' => ['TF', 'MC', 'MF', 'KF', 'F'], // Fajar Surya
                'D' => ['MY', 'MD', 'KD', 'Y', 'D'],  // Dayasa
                'K' => ['KE', 'EK'],             // Ekamas
                'S' => ['KS', 'SD', 'BS', 'S'],       // CMI / SDM
                'B' => ['BB', 'SB', 'MB', 'BN', 'B'], // SMB
                'C' => ['CK1', 'CK', 'MV', 'C'],       // Tjiwi Kimia
                'P' => ['PK', 'TP', 'P'],             // Pakerin
                'E' => ['SE', 'ES', 'E'],             // Enggal Subur
                'T' => ['T'],                         // Mekabox
                'M' => ['M'],                         // Buana Megah
                'SI'=> ['SI'], 'SP'=> ['SP'], 'R'=> ['R'], 'X'=> ['X'], 'UJ'=> ['UJ'], 'H'=> ['H']
            ];

            foreach ($mapSupplier as $supKode => $listPlanning) {
                foreach ($listPlanning as $planKode) {
                    if (strpos($huruf_planning, $planKode) !== false || $huruf_planning == $planKode) {
                        $h2 = $supKode;
                        break 2;
                    }
                }
            }
            if ($h2 == '') $h2 = substr($huruf_planning, -1); // Fallback ambil huruf terakhir planning

            // GABUNGKAN JADI MIDDLE CODE FISIK (ex: B + F = BF)
            $middle_code_fisik = $h1 . $h2;

            return [
                'gsm_asli' => $kode_mentah,
                'gsm_baca' => $tipe . $angka,
                'angka_teori' => floatval($angka),
                'middle_code' => $middle_code_fisik // Ini yang akan tayang di kotak kuning!
            ];
        }
        return null;
    }

    public function index()
    {
        // PERBAIKAN BUG: Sembunyikan SELESAI dan juga MERGED dari antrean!
        $tasks = DB::table('checker_tasks')
                    ->whereNotIn('status', ['SELESAI', 'MERGED'])
                    ->orderBy('id', 'desc')
                    ->get();
                    
        $scans = DB::table('checker_scans')->get()->groupBy('checker_task_id'); 
        
        return view('checker.index', compact('tasks', 'scans'));
    }

    public function storePlan(Request $request)
    {
        $json_raw = $request->input('json_data');
        $parsed = json_decode($json_raw, true);
        $items = $parsed['data'] ?? $parsed;

        if (!$items || !is_array($items)) {
            return back()->with('error', 'Format JSON Tidak Valid!');
        }

        // 1. KELOMPOKKAN BERDASARKAN LEBAR
        $groupedByLebar = [];
        foreach ($items as $item) {
            $lebar = floatval($item['lebar'] ?? 0);
            $lebar_cm = $lebar > 500 ? $lebar / 10 : $lebar; 
            $groupedByLebar[$lebar_cm][] = $item;
        }

        // 2. AGREGASI & PENGURUTAN MUTLAK
        foreach ($groupedByLebar as $lebar_cm => $spkList) {
            
            usort($spkList, function($a, $b) {
                return intval($a['seq'] ?? 0) <=> intval($b['seq'] ?? 0);
            });

            $posisiGrup = [
                'db' => ['id_pos' => 'db', 'judul' => 'DB', 'urut' => 1, 'kertas_list' => []],
                'bm' => ['id_pos' => 'bm', 'judul' => 'BM (Gel BF)', 'urut' => 2, 'kertas_list' => []],
                'bl' => ['id_pos' => 'bl', 'judul' => 'BL (Lap BF)', 'urut' => 3, 'kertas_list' => []],
                'cm' => ['id_pos' => 'cm', 'judul' => 'CM (Gel CF)', 'urut' => 4, 'kertas_list' => []],
                'cl' => ['id_pos' => 'cl', 'judul' => 'CL (Lap CF)', 'urut' => 5, 'kertas_list' => []],
            ];

            $faktor = ['db' => 1.0, 'bm' => 1.36, 'bl' => 1.0, 'cm' => 1.46, 'cl' => 1.0];

            foreach ($spkList as $spk) {
                $meter = floatval($spk['meter'] ?? 0);

                foreach (['db', 'bm', 'bl', 'cm', 'cl'] as $pos) {
                    $mentah = $spk[$pos] ?? '';
                    $bedah = $this->bedahKodeKertas($mentah);
                    
                    if ($bedah) {
                        $gsm_asli = $bedah['gsm_asli']; 
                        $teoriKg = ($meter * ($lebar_cm / 100) * $bedah['angka_teori'] * $faktor[$pos]) / 1000;

                        if (!isset($posisiGrup[$pos]['kertas_list'][$gsm_asli])) {
                            $posisiGrup[$pos]['kertas_list'][$gsm_asli] = [
                                'kertas_info' => $bedah,
                                'total_meter' => 0,
                                'estimasi_kg' => 0,
                                'spk_detail' => [],
                                'first_seq' => intval($spk['seq'] ?? 999999) 
                            ];
                        }

                        $posisiGrup[$pos]['kertas_list'][$gsm_asli]['total_meter'] += $meter;
                        $posisiGrup[$pos]['kertas_list'][$gsm_asli]['estimasi_kg'] += $teoriKg;
                        $posisiGrup[$pos]['kertas_list'][$gsm_asli]['spk_detail'][] = [
                            'seq' => $spk['seq'] ?? '-',
                            'spk_nama' => $spk['spk'] ?? 'Unknown'
                        ];
                    }
                }
            }

            $finalPosisiArray = [];
            foreach ($posisiGrup as $key => $val) {
                if (!empty($val['kertas_list'])) {
                    $kertasArray = array_values($val['kertas_list']);
                    usort($kertasArray, function($a, $b) {
                        return $a['first_seq'] <=> $b['first_seq'];
                    });
                    $val['kertas_list'] = $kertasArray;
                    $finalPosisiArray[] = $val;
                }
            }

            usort($finalPosisiArray, function($a, $b) {
                return $a['urut'] <=> $b['urut'];
            });

            DB::table('checker_tasks')->insert([
                'kode_tugas' => 'CHK-' . $lebar_cm . '-' . date('YmdHis') . rand(10,99),
                'lebar_cm' => $lebar_cm,
                'data_spk' => json_encode($finalPosisiArray), 
                'target_kebutuhan' => json_encode([]), 
                'status' => 'MENUNGGU',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', '✅ Middle Code Otomatis Terjemah dengan Kamus Master!');
    }

    // FUNGSI UNTUK MENYIMPAN 1 ROLL SECARA DIAM-DIAM (AJAX)
    public function saveScan(Request $request)
    {
        $id = DB::table('checker_scans')->insertGetId([
            'checker_task_id' => $request->task_id,
            'posisi' => $request->posisi,
            'no_roll' => $request->no_roll,
            'gsm_asli' => $request->gsm_asli,
            'gsm_terjemahan' => $request->gsm_terjemahan,
            'berat_kg' => $request->berat_kg,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    // FUNGSI UNTUK MEMBATALKAN/MENGHAPUS ROLL (AJAX)
    public function deleteScan(Request $request)
    {
        DB::table('checker_scans')->where('id', $request->id)->delete();
        return response()->json(['success' => true]);
    }

    // FUNGSI AJAX: CARI KG ROLL DI MASTER GUDANG
    public function fetchKg(Request $request)
    {
        $no_roll = $request->no_roll;
        
        // ⚠️ PENTING: Ganti 'stock_kertas' dengan nama tabel master roll pabrik Anda!
        // ⚠️ Ganti 'sisa_kg' atau 'berat' dengan nama kolom kiloannya!
        $roll = DB::table('stock_kertas')->where('no_roll', $no_roll)->first();

        if ($roll) {
            // Kalau ketemu, kirim angka Kg-nya ke layar Checker
            return response()->json(['success' => true, 'kg' => $roll->sisa_kertas]); // ubah ->berat sesuai kolom db
        } else {
            // Kalau nggak ada di gudang, tolak!
            return response()->json(['success' => false]);
        }
    }

    // FUNGSI UNTUK MENGHAPUS 1 KELOMPOK LEBAR SAJA
    public function hapusTugas($id)
    {
        // Hapus data scan-nya dulu biar bersih
        DB::table('checker_scans')->where('checker_task_id', $id)->delete();
        // Baru hapus tugasnya
        DB::table('checker_tasks')->where('id', $id)->delete();

        return back()->with('success', '🗑️ Jadwal Lebar tersebut berhasil dihapus!');
    }

    // FUNGSI UNTUK RESET TOTAL (PENGGANTI TINKER TRUNCATE)
    public function resetSemua()
    {
        DB::table('checker_scans')->truncate();
        // DB::table('checker_tasks')->truncate();

        return back()->with('success', '💥 BAAAM! Semua jadwal dan memori roll berhasil dikosongkan!');
    }


    // FUNGSI SUBMIT KE RIWAYAT
    public function submitTask($id)
    {
        DB::table('checker_tasks')->where('id', $id)->update([
            'status' => 'SELESAI',
            'updated_at' => now() // Rekam jam dan tanggal submit
        ]);
        return back()->with('success', '✅ Persiapan Lebar berhasil di-Submit dan masuk ke Riwayat!');
    }

    // FUNGSI TAMPILKAN HALAMAN RIWAYAT
    public function riwayat()
    {
        $tasks = DB::table('checker_tasks')->whereIn('status', ['SELESAI', 'MERGED'])->orderBy('updated_at', 'desc')->get();
        $scans = DB::table('checker_scans')->get()->groupBy('checker_task_id'); 
        
        // Panggil Shift dari tabel V3
        $shifts = DB::table('shifts_v3')->orderBy('id', 'desc')->limit(10)->get(); 
        
        return view('checker.riwayat', compact('tasks', 'scans', 'shifts'));
    }

    // FUNGSI PUSH KE SCAN FORKLIFT (V3)
    public function pushToMakComblang(Request $request, $id)
    {
        $shift_id = $request->shifts_id; // Dari input form riwayat
        if (!$shift_id) return back()->with('error', 'Pilih Shift terlebih dahulu!');

        // Ambil data task utama (untuk mendapatkan Lebar CM-nya)
        $taskMaster = DB::table('checker_tasks')->where('id', $id)->first();
        $lebar_jalan = $taskMaster->lebar_cm; // 👈 KITA TANGKAP LEBARNYA DI SINI

        $scans = DB::table('checker_scans')->where('checker_task_id', $id)->get();

        foreach($scans as $scan) {
            // Ambil data mutlak dari komputer
            $master = DB::table('stock_kertas')->where('no_roll', $scan->no_roll)->first();

            if($master) {
                // Cek agar tidak dobel di V3
                $exists = DB::table('transaksi_roll_v3')
                            ->where('shift_v3_id', $shift_id)
                            ->where('no_roll', $scan->no_roll)
                            ->exists();

                if(!$exists) {
                    // MASUKKAN KE TABEL V3
                    DB::table('transaksi_roll_v3')->insert([
                        'shift_v3_id'     => $shift_id,
                        'lebar_jalan'     => $lebar_jalan, // 👈 SUNTIKKAN LEBAR JALAN KE V3!
                        'no_roll'         => $scan->no_roll,
                        'posisi_mesin'    => strtoupper($scan->posisi),
                        'waktu_ambil'     => now(),
                        'sisa_kilo_awal'  => $master->sisa_kertas, // Ambil dari master gudang
                        'status'          => 'diambil',
                        'metode_input'    => 'otomatis_checker', 
                        'keterangan'      => 'Push dari Dashboard Checker (Lebar '.$lebar_jalan.')',
                        'created_at'      => now(),
                        'updated_at'      => now()
                    ]);
                }
            }
        }

        DB::table('checker_tasks')->where('id', $id)->update([
            'status' => 'MERGED',
            'updated_at' => now()
        ]);

        return back()->with('success', '🚀 BAAAM! Data persiapan berhasil di-Merge ke V3 Track Per Lebar!');
    }

    // FUNGSI KEMBALIKAN DARI RIWAYAT KE DASHBOARD (EDIT)
    public function batalSubmit($id)
    {
        DB::table('checker_tasks')->where('id', $id)->update([
            'status' => 'MENUNGGU',
            'updated_at' => now()
        ]);
        return redirect('/checker')->with('success', '⚠️ Jadwal ditarik kembali ke Dashboard untuk diedit!');
    }

    // FUNGSI BATAL MERGE (TARIK KEMBALI DATA DARI V1)
    public function batalMerge($id)
    {
        // 1. Ambil semua nomor roll yang ada di tugas ini
        $scans = DB::table('checker_scans')->where('checker_task_id', $id)->pluck('no_roll');

        // 2. Hapus roll tersebut dari tabel V1 (Hanya hapus yang dari otomatis checker)
        if($scans->isNotEmpty()) {
            DB::table('transaksi_roll')
                ->whereIn('no_roll', $scans)
                ->where('metode_input', 'otomatis_checker') // Proteksi biar aman
                ->delete();
        }

        // 3. Ubah status kembali menjadi SELESAI (Bisa dibongkar lagi)
        DB::table('checker_tasks')->where('id', $id)->update([
            'status' => 'SELESAI',
            'updated_at' => now()
        ]);

        return back()->with('success', '⏪ BAAAM! Data ditarik dari V1! Silakan klik Edit Kembali jika ada perubahan.');
    }
}