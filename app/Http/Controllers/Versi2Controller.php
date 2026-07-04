<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShiftV2;
use App\Models\TransaksiRollV2;
use App\Models\KalkulasiSpkV2;
use App\Models\StockKertas; // Sesuaikan dengan nama model stok kertas asli Anda

class Versi2Controller extends Controller
{
    // =================================================================
    // A. FITUR SHIFT & SCAN FORKLIFT V2 (TANPA POSISI MESIN)
    // =================================================================
    
    public function shiftIndex()
    {
        $shifts = ShiftV2::orderBy('id', 'desc')->get();
        // Anda harus membuat view: resources/views/versi2/shift_index.blade.php
        return view('versi2.shift_index', compact('shifts'));
    }

    public function shiftStore(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'shift' => 'required',
            'kepala_shift' => 'required',
        ]);

        ShiftV2::create($request->all());
        return redirect()->back()->with('success', 'Sesi Shift V2 Berhasil Dibuat!');
    }

    public function shiftDashboard(Request $request, $id)
    {
        $shift = ShiftV2::findOrFail($id);
        $search = $request->input('search');

        // Panggil query dasar
        $query = TransaksiRollV2::with('stockKertas')
            ->where('shift_v2_id', $id)
            ->orderBy('id', 'desc');

        // Jika Admin mengetik sesuatu di kotak search, filter datanya secara cerdas
        if ($search) {
            $query->whereHas('stockKertas', function($q) use ($search) {
                $q->where('no_roll', 'LIKE', '%' . $search . '%')
                  ->orWhere('jenis', 'LIKE', '%' . $search . '%')
                  ->orWhere('gsm', 'LIKE', '%' . $search . '%')
                  ->orWhere('lebar', 'LIKE', '%' . $search . '%');
            });
        }

        $rolls = $query->get();

        // Kirim variabel $search ke view agar ketikan tidak hilang setelah di-refresh
        return view('versi2.shift_dashboard', compact('shift', 'rolls', 'search'));
    }

    public function postAmbilRoll(Request $request, $id)
    {
        // 1. Cari data roll di master stok
        $roll = \App\Models\StockKertas::where('no_roll', $request->no_roll)->first(); 
        
        if (!$roll) {
            return redirect()->back()->with('error', '⚠️ Gagal! Barcode Roll tidak ditemukan di gudang!');
        }

        // 2. RADAR ANTI-DUPLIKAT: Cek apakah roll sudah masuk di shift ini
        $sudah_scan = TransaksiRollV2::where('shift_v2_id', $id)
                        ->where('stock_kertas_id', $roll->id)
                        ->exists();

        if ($sudah_scan) {
            return redirect()->back()->with('error', '❌ TETOT! Roll ' . $request->no_roll . ' SUDAH DI-SCAN di shift ini!');
        }

        // 3. SIMPAN DATA (Pakai stock_kertas_id dengan huruf S)
        TransaksiRollV2::create([
            'shift_v2_id' => $id,
            'stock_kertas_id' => $roll->id,
            'sisa_kilo_awal' => floatval($roll->sisa_kertas), 
            'sisa_kilo_akhir' => 0 // Default 0 (Asumsi dihabiskan)
        ]);

        // 4. KEMBALIKAN KE HALAMAN DENGAN NOTIFIKASI POPUP/ALERT (Bukan JSON)
        return redirect()->back()->with('success', '✅ Roll ' . $request->no_roll . ' berhasil masuk antrean!');
    }

    public function postKembaliRoll(Request $request, $id)
    {
        $transaksi = TransaksiRollV2::findOrFail($id);
        $transaksi->update(['sisa_kilo_akhir' => $request->sisa_kilo_akhir]);
        return redirect()->back()->with('success', 'Sisa Kg Roll berhasil diupdate!');
    }

    public function batalRoll($id)
    {
        TransaksiRollV2::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Roll dibatalkan dari shift!');
    }


    // =================================================================
    // B. KAMUS PENERJEMAH (TETAP SAMA SEPERTI V1)
    // =================================================================
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

    // =================================================================
    // C. MAK COMBLANG V2 (OTAK GLOBAL POOLING & PRORATE TEORI KG)
    // =================================================================

    public function pencocokanIndex()
    {
        $shifts = ShiftV2::orderBy('id', 'desc')->get();
        // Anda harus membuat view: resources/views/versi2/pencocokan.blade.php
        return view('versi2.pencocokan', compact('shifts'));
    }

    public function storePencocokan(Request $request)
    {
        $request->validate([
            'shift_v2_id' => 'required',
            'no_spk' => 'required|array',
        ]);

        $shift_id = $request->shift_v2_id;
        $posisiList = ['DB' => 'gsm_db', 'BM' => 'gsm_bm', 'BL' => 'gsm_bl', 'CM' => 'gsm_cm', 'CL' => 'gsm_cl'];

        // --- TAHAP 1: KUMPULKAN DAFTAR ROLL FISIK ---
        $transaksi = TransaksiRollV2::with('stockKertas')->where('shift_v2_id', $shift_id)->get();
        $rollsBySpec = []; 
        
        foreach ($transaksi as $t) {
            $lebar_db = floatval($t->stockKertas->lebar ?? 0);
            if ($lebar_db > 500) { $lebar_db = $lebar_db / 10; } 
            
            $gsm_standar = $this->terjemahkanKode($t->stockKertas->gsm ?? '');
            if ($gsm_standar === '') continue;

            $prorate_gsm = $gsm_standar;
            if (in_array(substr($gsm_standar, 0, 1), ['B', 'T'])) {
                $prorate_gsm = 'K' . substr($gsm_standar, 1);
            }

            $kunci = $lebar_db . '_' . $prorate_gsm;
            $sisa_awal = floatval($t->sisa_kilo_awal);
            $sisa_akhir = floatval($t->sisa_kilo_akhir);
            $terpakai = ($sisa_akhir <= 0) ? $sisa_awal : ($sisa_awal - $sisa_akhir);

            if ($terpakai > 0) {
                $rollsBySpec[$kunci][] = [
                    'no_roll' => $t->stockKertas->no_roll ?? 'N/A',
                    'gsm_asli' => $gsm_standar, 
                    'terpakai' => $terpakai,
                    'sisa' => $terpakai
                ];
            }
        }

        // --- TAHAP 2: HITUNG KEBUTUHAN TEORI & PRORATA ---
        $demandsBySpec = [];
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
                if (in_array(substr($gsm_standar, 0, 1), ['B', 'T'])) {
                    $prorate_gsm = 'K' . substr($gsm_standar, 1);
                }

                $gsm_angka = floatval(preg_replace('/[^0-9]/', '', $gsm_standar));
                $faktor = ($pos === 'BM') ? ($request->faktor_bm[$index] ?? 1.36) : (($pos === 'CM') ? ($request->faktor_cm[$index] ?? 1.46) : 1.0);
                $teori_kg_posisi = ($meter * ($lebar_pakai_cm / 100) * $gsm_angka * $faktor) / 1000;

                $kunci = $lebar_pakai_cm . '_' . $prorate_gsm;
                
                if ($teori_kg_posisi > 0) {
                    $demandsBySpec[$kunci][] = [
                        'spk_index' => $index,
                        'posisi' => strtolower($pos),
                        'gsm_diminta' => $gsm_standar,
                        'teori' => $teori_kg_posisi,
                        'scaled_aktual' => 0
                    ];
                }
            }
        }

        $demandsBySpk = [];
        foreach ($demandsBySpec as $kunci => &$demands) {
            $total_teori_grup = array_sum(array_column($demands, 'teori'));
            $total_aktual_grup = isset($rollsBySpec[$kunci]) ? array_sum(array_column($rollsBySpec[$kunci], 'terpakai')) : 0;
            
            foreach ($demands as &$d) {
                if ($total_teori_grup > 0) {
                    $d['scaled_aktual'] = ($d['teori'] / $total_teori_grup) * $total_aktual_grup;
                    $demandsBySpk[$d['spk_index']][] = [
                        'posisi' => $d['posisi'],
                        'gsm_diminta' => $d['gsm_diminta'],
                        'scaled_aktual' => $d['scaled_aktual'],
                        'kunci' => $kunci
                    ];
                }
            }
        }
        unset($demands);

        // --- TAHAP 3: ALGORITMA SIMULASI STAND MESIN (DENGAN PENGHANCUR GHOST STAND) ---
        $rollPool = $rollsBySpec; 
        $allocatedRollsBySpk = [];
        $standMesin = ['db' => null, 'bm' => null, 'bl' => null, 'cm' => null, 'cl' => null];
        $urutanFisik = ['db', 'bm', 'bl', 'cm', 'cl'];
        $lastUsedRollLocation = []; 

        $spkIndices = array_keys($request->no_spk);

        foreach ($spkIndices as $spkIdx) {
            if (!isset($demandsBySpk[$spkIdx])) {
                // Jika SPK ini tidak jalan sama sekali, copot semua roll
                $standMesin = ['db' => null, 'bm' => null, 'bl' => null, 'cm' => null, 'cl' => null];
                continue;
            }

            $spkDemands = $demandsBySpk[$spkIdx];

            // 🔥 LOGIKA BARU: PENGHANCUR GHOST STAND 🔥
            // Jika ada posisi mesin yang tidak dipakai di SPK ini, turunkan roll-nya sekarang juga!
            $activePositions = array_column($spkDemands, 'posisi');
            foreach ($urutanFisik as $pos) {
                if (!in_array($pos, $activePositions)) {
                    $standMesin[$pos] = null; // Roll bebas direbut posisi lain!
                }
            }

            foreach ($urutanFisik as $pos) {
                $d = null;
                foreach ($spkDemands as $demandItem) {
                    if ($demandItem['posisi'] === $pos) { $d = $demandItem; break; }
                }
                if (!$d) continue; 

                $kunci = $d['kunci'];
                $gsmDiminta = $d['gsm_diminta'];
                $sisa_butuh = $d['scaled_aktual'];

                if ($sisa_butuh <= 0.01) continue;
                if (!isset($rollPool[$kunci])) continue;
                $rolls = &$rollPool[$kunci];

                $isUsedInThisSpk = function($no_roll) use (&$allocatedRollsBySpk, $spkIdx, $pos) {
                    if (!isset($allocatedRollsBySpk[$spkIdx])) return false;
                    foreach ($allocatedRollsBySpk[$spkIdx] as $p => $allocs) {
                        if ($p === $pos) continue; 
                        foreach ($allocs as $a) {
                            if ($a['no_roll'] === $no_roll) return true;
                        }
                    }
                    return false;
                };

                while ($sisa_butuh > 0.01) {
                    $activeRoll = $standMesin[$pos];
                    $rollCocok = false;

                    // 1. Cek Roll di Stand
                    if ($activeRoll !== null && $activeRoll['kunci'] === $kunci) {
                        $r = &$rolls[$activeRoll['idx']];
                        if ($r['sisa'] > 0.01 && !$isUsedInThisSpk($r['no_roll'])) {
                            $rollCocok = true;
                        }
                    }

                    // 2. Ganti Roll Jika Perlu
                    if (!$rollCocok) {
                        $standMesin[$pos] = null; 

                        $findBestRoll = function($allowFallback) use (&$rolls, $gsmDiminta, $kunci, $pos, &$standMesin, $isUsedInThisSpk) {
                            $bIdx = -1;
                            $mSisa = -1;
                            foreach ($rolls as $idx => $r) {
                                if ($r['sisa'] <= 0.01) continue;
                                if (!$allowFallback && $r['gsm_asli'] !== $gsmDiminta) continue;
                                if ($allowFallback && $r['gsm_asli'] === $gsmDiminta) continue;

                                $onOtherStand = false;
                                foreach ($standMesin as $stPos => $stData) {
                                    if ($stPos !== $pos && $stData !== null) {
                                        if ($stData['kunci'] === $kunci && $stData['idx'] === $idx) { $onOtherStand = true; break; }
                                    }
                                }
                                if ($onOtherStand) continue;
                                if ($isUsedInThisSpk($r['no_roll'])) continue;

                                if ($r['sisa'] > $mSisa) { $mSisa = $r['sisa']; $bIdx = $idx; }
                            }
                            return $bIdx;
                        };

                        $bestIdx = $findBestRoll(false);
                        if ($bestIdx === -1) $bestIdx = $findBestRoll(true);

                        if ($bestIdx !== -1) {
                            $standMesin[$pos] = ['kunci' => $kunci, 'idx' => $bestIdx];
                            $activeRoll = $standMesin[$pos];
                        } else {
                            break; 
                        }
                    }

                    // 3. Sedot Kilogram
                    $r = &$rolls[$activeRoll['idx']];
                    $ambil = min($r['sisa'], $sisa_butuh);
                    $r['sisa'] -= $ambil;
                    $sisa_butuh -= $ambil;
                    
                    $allocatedRollsBySpk[$spkIdx][$pos][] = ['no_roll' => $r['no_roll'], 'kg' => $ambil];
                    $lastUsedRollLocation[$r['no_roll']] = ['spkIdx' => $spkIdx, 'pos' => $pos];

                    if ($r['sisa'] <= 0.01) { $standMesin[$pos] = null; }
                }
            }
        }

        // --- TAHAP 4: FINAL DUMP (MENGEMBALIKAN SISA TONASE) ---
        foreach ($rollPool as $kunci => &$rolls) {
            foreach ($rolls as &$r) {
                if ($r['sisa'] > 0.01) {
                    $no_roll = $r['no_roll'];
                    
                    if (isset($lastUsedRollLocation[$no_roll])) {
                        $loc = $lastUsedRollLocation[$no_roll];
                        $sIdx = $loc['spkIdx'];
                        $p = $loc['pos'];
                        
                        $found = false;
                        foreach ($allocatedRollsBySpk[$sIdx][$p] as &$alloc) {
                            if ($alloc['no_roll'] === $no_roll) {
                                $alloc['kg'] += $r['sisa'];
                                $found = true; break;
                            }
                        }
                        if (!$found) {
                            $allocatedRollsBySpk[$sIdx][$p][] = ['no_roll' => $no_roll, 'kg' => $r['sisa']];
                        }
                    } else {
                        foreach (array_reverse($spkIndices) as $spkIdx) {
                            if (isset($demandsBySpk[$spkIdx])) {
                                foreach ($demandsBySpk[$spkIdx] as $d) {
                                    if ($d['kunci'] === $kunci) {
                                        $allocatedRollsBySpk[$spkIdx][$d['posisi']][] = ['no_roll' => $no_roll, 'kg' => $r['sisa'], 'is_force_share' => true];
                                        break 3;
                                    }
                                }
                            }
                        }
                    }
                    $r['sisa'] = 0; 
                }
            }
        }

        // --- TAHAP 5: RAKIT DATA JSON & SIMPAN ---
        $data_json = [];
        $grand_total_aktual = 0;

        foreach ($request->no_spk as $index => $no_spk) {
            $lebar_mm = floatval($request->lebar_mm[$index] ?? 0);
            $lebar_cm_global = $lebar_mm > 500 ? ($lebar_mm / 10) : $lebar_mm;
            $meter = floatval($request->panjang_m[$index] ?? 0);

            $r_db = $allocatedRollsBySpk[$index]['db'] ?? [];
            $r_bm = $allocatedRollsBySpk[$index]['bm'] ?? [];
            $r_bl = $allocatedRollsBySpk[$index]['bl'] ?? [];
            $r_cm = $allocatedRollsBySpk[$index]['cm'] ?? [];
            $r_cl = $allocatedRollsBySpk[$index]['cl'] ?? [];

            $akt_db = array_sum(array_column($r_db, 'kg'));
            $akt_bm = array_sum(array_column($r_bm, 'kg'));
            $akt_bl = array_sum(array_column($r_bl, 'kg'));
            $akt_cm = array_sum(array_column($r_cm, 'kg'));
            $akt_cl = array_sum(array_column($r_cl, 'kg'));

            $total_baris_aktual = $akt_db + $akt_bm + $akt_bl + $akt_cm + $akt_cl;
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
                'akt_db' => $akt_db, 'akt_bm' => $akt_bm, 'akt_bl' => $akt_bl, 'akt_cm' => $akt_cm, 'akt_cl' => $akt_cl,
                'rolls_db' => $r_db, 'rolls_bm' => $r_bm, 'rolls_bl' => $r_bl, 'rolls_cm' => $r_cm, 'rolls_cl' => $r_cl,
                'total_aktual' => $total_baris_aktual
            ];
        }

        KalkulasiSpkV2::create([
            'kode_sesi' => 'AUTOV2-' . date('Ymd-His'),
            'data_spk' => $data_json,
            'total_aktual_semua' => $grand_total_aktual,
            'shift_v2_id' => $shift_id 
        ]);

        return redirect('/versi2/riwayat')->with('success', '✅ GHOST STAND DIHANCURKAN! Mesin Mati = Roll Langsung Turun!');
    }

    // =================================================================
    // D. RIWAYAT V2
    // =================================================================
    public function riwayatIndex(Request $request)
    {
        $search = $request->input('search');
        $query = KalkulasiSpkV2::orderBy('id', 'desc');

        if ($search) {
            $query->where('kode_sesi', 'LIKE', '%' . $search . '%')
                  ->orWhere('data_spk', 'LIKE', '%' . $search . '%');
        }

        $kalkulasis = $query->paginate(10)->appends(['search' => $search]);
        // Anda harus membuat view: resources/views/versi2/riwayat.blade.php
        return view('versi2.riwayat', compact('kalkulasis', 'search'));
    }

    public function destroy($id)
    {
        KalkulasiSpkV2::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Sesi Batch V2 berhasil dihapus!');
    }

    public function showDetail($id)
    {
        $kalkulasi = KalkulasiSpkV2::findOrFail($id);
        
        // Panggil data shift untuk dropdown Edit form
        $shifts = ShiftV2::orderBy('id', 'desc')->get();

        $transaksiRolls = TransaksiRollV2::with('stockKertas')
            ->where('shift_v2_id', $kalkulasi->shift_v2_id)
            ->get()
            ->sortBy(function($roll) {
                return floatval($roll->sisa_kilo_akhir) <= 0 ? 1 : 2;
            })->values();

        return view('versi2.detail', compact('kalkulasi', 'transaksiRolls', 'shifts'));
    }

    // FUNGSI BARU UNTUK MENANGANI RE-RUN DARI HALAMAN DETAIL
    public function reRunPencocokan(Request $request, $id)
    {
        // 1. Hapus riwayat mak-comblang yang lama (karena salah/mau direvisi)
        KalkulasiSpkV2::findOrFail($id)->delete();
        
        // 2. Langsung oper ke fungsi pencocokan utama untuk membuat sesi baru!
        return $this->storePencocokan($request);
    }

    public function editIndex($id)
 {
     $kalkulasi = KalkulasiSpkV2::findOrFail($id);
     $shifts = ShiftV2::orderBy('id', 'desc')->get();
     return view('versi2.edit', compact('kalkulasi', 'shifts'));
 }
}