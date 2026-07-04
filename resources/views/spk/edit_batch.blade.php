<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Batch Kalkulasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .form-control { border-color: #ced4da; }
        .form-control:focus { border-color: #0d6efd; box-shadow: none; }
        .bg-flute { background-color: #fff3cd !important; } 
        .spk-card { border-left: 5px solid #ffc107; transition: all 0.3s ease; }
        .grid-header { background-color: #e9ecef; border-radius: 5px 5px 0 0; }
        .input-readonly { background-color: transparent !important; border: 1px dashed #ced4da; cursor: not-allowed; }
        .input-aktual { background-color: #fff5f5 !important; border-color: #feb2b2 !important; color: #dc3545; font-weight: bold; }
        .input-aktual:focus { background-color: #fff0f0 !important; border-color: #dc3545 !important; box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25); }
        
        /* Highlight khusus untuk baris error / anomali */
        .row-warning { background-color: #fff3cd !important; }
        .row-danger { background-color: #f8d7da !important; }
    </style>
</head>
<body>
@php
    // 1. Tarik semua data roll & URUTKAN (Roll Habis Maju Duluan!)
    $transaksiRolls = \App\Models\TransaksiRoll::with('masterKertas')
        ->where('shift_id', $kalkulasi->shift_id ?? 0)
        ->get()
        ->sortBy(function($roll) {
            // LOGIKA PENGURUTAN: 
            // Jika sisa ludes (0), kasih nomor antrean 1. Jika masih sisa, antrean 2.
            return floatval($roll->sisa_kilo_akhir) <= 0 ? 1 : 2;
        })
        ->values(); // WAJIB: Reset urutan index array setelah disortir

    // 2. Setup Saldo & Tracker SPK
    $saldo_roll_global = [];
    $pemakaian_roll_di_spk = []; 

    foreach($transaksiRolls as $r) {
        $awal = floatval($r->sisa_kilo_awal);
        $akhir = floatval($r->sisa_kilo_akhir);
        $saldo_roll_global[$r->id] = ($akhir <= 0) ? $awal : ($awal - $akhir);
        $pemakaian_roll_di_spk[$r->id] = []; 
    }

    // Mengumpulkan daftar lebar SPK yang valid untuk deteksi Slash (Beda Ukuran)
    $listLebarSpk = [];
    if(isset($kalkulasi->data_spk)) {
        foreach($kalkulasi->data_spk as $s) {
            $listLebarSpk[] = floatval($s['lebar_cm']);
        }
    }

    // 3. Buat fungsi kamus mini khusus untuk halaman ini
    if(!function_exists('terjemahkanKodeBlade')) {
        function terjemahkanKodeBlade($kode) {
            if (!$kode || $kode == '-') return '';
            $kode = strtoupper(str_replace(' ', '', $kode));
            
            if (preg_match('/^([A-Z]+)(\d+)/', $kode, $matches)) {
                $huruf = $matches[1];
                if ($huruf == 'W') $huruf = 'WK';
                if ($huruf == 'T') $huruf = 'K';
                return $huruf . $matches[2];
            }
            
            if (preg_match('/^(\d+)([A-Z]+)/', $kode, $matches)) {
                $angka = $matches[1];
                $huruf = substr($matches[2], 0, 1);
                if (!in_array($huruf, ['K', 'B', 'T', 'M', 'W'])) $huruf = 'M';
                
                $angka_db = $angka;
                if ($angka == '101') $angka_db = '100';
                if ($angka == '111') $angka_db = '110';
                if ($angka == '113') $angka_db = '112';
                if ($angka == '127') $angka_db = '125';
                if ($angka == '137') $angka_db = '135';
                if ($angka == '160') { $angka_db = ($huruf == 'W') ? '140' : '150'; }
                
                $prefix = ($huruf == 'W') ? 'WK' : $huruf;
                if ($prefix == 'T') $prefix = 'K';
                
                return $prefix . $angka_db;
            }
            return $kode;
        }
    }
@endphp

<div class="container py-4" style="max-width: 1100px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/hitung-spk/riwayat') }}" class="btn btn-outline-dark fw-bold shadow-sm">⬅️ KEMBALI</a>
        
        <div>
            <h3 class="fw-bold mb-0">✏️ Edit Sesi: <span class="text-warning">{{ $kalkulasi->kode_sesi }}</span></h3>
            @php
                $shiftInfo = \App\Models\Shift::find($kalkulasi->shift_id);
            @endphp
            @if($shiftInfo)
                <span class="badge bg-primary fs-6 mt-2">
                    👨‍🔧 Laporan Forklift: Shift {{ $shiftInfo->shift_ke }} | Tanggal: {{ \Carbon\Carbon::parse($shiftInfo->tanggal)->format('d-M-Y') }} | Opr: {{ $shiftInfo->kepala_shift }}
                </span>
            @else
                <span class="badge bg-secondary fs-6 mt-2">ID Shift: {{ $kalkulasi->shift_id ?? 'Tidak Ditemukan' }}</span>
            @endif
        </div>

        <div>
            <button type="button" class="btn btn-primary fw-bold shadow-sm px-4 me-2" onclick="reRunSapuJagat()">⚙️ RE-RUN MATCHING</button>
            <button type="button" class="btn btn-warning fw-bold shadow-sm px-4" onclick="simpanData()">💾 SIMPAN MANUAL</button>        
        </div>
    </div>

    <form id="form-spk-multi" action="{{ url('/hitung-spk/update/' . $kalkulasi->id) }}" method="POST">
        <input type="hidden" name="shift_id" value="{{ $kalkulasi->shift_id ?? 1 }}">
        @csrf
        <div id="spk-container">
            
            @foreach($kalkulasi->data_spk as $index => $spk)
            <div class="card shadow-sm border-0 mb-4 spk-card" id="spk-{{ $index + 1 }}">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 judul-spk">SPK #{{ $index + 1 }}</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-warning fw-bold me-2" onclick="cloneCard(this)">📄 CLONE</button>
                        <button type="button" class="btn btn-sm btn-danger fw-bold btn-hapus" onclick="hapusCard(this)" {{ count($kalkulasi->data_spk) == 1 ? 'disabled' : '' }}>❌ HAPUS</button>
                    </div>
                </div>
                <div class="card-body bg-white">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">NOMOR SPK / CUSTOM</label>
                            <input type="text" name="no_spk[]" class="form-control fw-bold text-uppercase" value="{{ $spk['no_spk'] ?? '' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">LEBAR KERTAS (cm)</label>
                            <div class="input-group">
                                <input type="number" name="lebar_mm[]" class="form-control fw-bold text-center input-lebar" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()" value="{{ $spk['lebar_cm'] }}" required>
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">PANJANG LARI (Meter)</label>
                            <div class="input-group">
                                <input type="number" name="panjang_m[]" class="form-control fw-bold text-center input-panjang" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()" value="{{ $spk['panjang_m'] }}" required>
                                <span class="input-group-text">m</span>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-white shadow-sm">
                        <div class="row g-2 text-center align-items-end fw-bold small grid-header p-2 mb-2">
                            <div class="col-2 text-start text-muted">PARAMETER</div>
                            <div class="col">DB (1.0)</div>
                            <div class="col text-primary">BM<br><input type="number" step="0.01" name="faktor_bm[]" class="form-control form-control-sm text-center text-primary fw-bold mx-auto mt-1 input-faktor-bm" onkeyup="hitungKalkulator()" value="{{ $spk['faktor_bm'] ?? '1.36' }}" style="width: 60px;"></div>
                            <div class="col">BL (1.0)</div>
                            <div class="col text-primary">CM<br><input type="number" step="0.01" name="faktor_cm[]" class="form-control form-control-sm text-center text-primary fw-bold mx-auto mt-1 input-faktor-cm" onkeyup="hitungKalkulator()" value="{{ $spk['faktor_cm'] ?? '1.46' }}" style="width: 60px;"></div>
                            <div class="col">CL (1.0)</div>
                            <div class="col-2 text-success">TOTAL</div>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-2">
                            <div class="col-2 text-start fw-bold small text-muted">1. INPUT GSM</div>
                            <div class="col"><input type="text" name="gsm_db[]" class="form-control form-control-sm fw-bold text-center input-db" onkeyup="hitungKalkulator()" value="{{ $spk['gsm_db'] ?? '' }}"></div>
                            <div class="col"><input type="text" name="gsm_bm[]" class="form-control form-control-sm fw-bold text-center bg-flute input-bm" onkeyup="hitungKalkulator()" value="{{ $spk['gsm_bm'] ?? '' }}"></div>
                            <div class="col"><input type="text" name="gsm_bl[]" class="form-control form-control-sm fw-bold text-center input-bl" onkeyup="hitungKalkulator()" value="{{ $spk['gsm_bl'] ?? '' }}"></div>
                            <div class="col"><input type="text" name="gsm_cm[]" class="form-control form-control-sm fw-bold text-center bg-flute input-cm" onkeyup="hitungKalkulator()" value="{{ $spk['gsm_cm'] ?? '' }}"></div>
                            <div class="col"><input type="text" name="gsm_cl[]" class="form-control form-control-sm fw-bold text-center input-cl" onkeyup="hitungKalkulator()" value="{{ $spk['gsm_cl'] ?? '' }}"></div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-3">
                            <div class="col-2 text-start fw-bold small text-secondary">2. TEORI (Kg)</div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-db" value="0.00" readonly></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-bm" value="0.00" readonly></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-bl" value="0.00" readonly></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-cm" value="0.00" readonly></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-cl" value="0.00" readonly></div>
                            <div class="col-2"><input type="text" class="form-control form-control-sm text-center fw-bold input-readonly text-secondary total-teori-card" value="0.00" readonly></div>
                        </div>

                        <div class="row g-2 text-center align-items-center pt-2 border-top border-danger">
                            <div class="col-2 text-start fw-bold small text-danger">3. AKTUAL (Kg)</div>
                            
                            <div class="col">
                                <input type="number" step="0.01" name="aktual_db[]" class="form-control form-control-sm text-center input-aktual akt-db" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)" value="{{ $spk['akt_db'] ?? 0 }}">
                                <div class="small text-danger fw-bold d-none warn-pos-db" style="font-size: 10px; margin-top: 2px; line-height: 1.1;"></div>
                            </div>
                            
                            <div class="col">
                                <input type="number" step="0.01" name="aktual_bm[]" class="form-control form-control-sm text-center input-aktual akt-bm" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)" value="{{ $spk['akt_bm'] ?? 0 }}">
                                <div class="small text-danger fw-bold d-none warn-pos-bm" style="font-size: 10px; margin-top: 2px; line-height: 1.1;"></div>
                            </div>
                            
                            <div class="col">
                                <input type="number" step="0.01" name="aktual_bl[]" class="form-control form-control-sm text-center input-aktual akt-bl" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)" value="{{ $spk['akt_bl'] ?? 0 }}">
                                <div class="small text-danger fw-bold d-none warn-pos-bl" style="font-size: 10px; margin-top: 2px; line-height: 1.1;"></div>
                            </div>
                            
                            <div class="col">
                                <input type="number" step="0.01" name="aktual_cm[]" class="form-control form-control-sm text-center input-aktual akt-cm" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)" value="{{ $spk['akt_cm'] ?? 0 }}">
                                <div class="small text-danger fw-bold d-none warn-pos-cm" style="font-size: 10px; margin-top: 2px; line-height: 1.1;"></div>
                            </div>
                            
                            <div class="col">
                                <input type="number" step="0.01" name="aktual_cl[]" class="form-control form-control-sm text-center input-aktual akt-cl" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)" value="{{ $spk['akt_cl'] ?? 0 }}">
                                <div class="small text-danger fw-bold d-none warn-pos-cl" style="font-size: 10px; margin-top: 2px; line-height: 1.1;"></div>
                            </div>
                            
                            <div class="col-2">
                                <input type="text" name="total_kg_aktual[]" class="form-control form-control-md text-center fw-bold text-white bg-danger border-danger total-aktual-card" value="{{ $spk['total_aktual'] ?? 0 }}" readonly>
                            </div>
                        </div>

                        <div class="alert alert-danger mt-2 py-2 px-3 d-none warning-selisih-val" role="alert" style="font-size: 0.85rem;">
                            <strong>⚠️ Peringatan Selisih Tinggi!</strong> Berat aktual berbeda <span class="txt-persen-selisih text-decoration-underline">0</span>% dari kalkulasi teori (Teori: <span class="txt-teori-val">0</span> Kg | Aktual: <span class="txt-aktual-val">0</span> Kg). Pastikan tidak ada salah ketik data!
                        </div>

                        <div class="row g-2 text-center align-items-start pt-3 border-top border-info mt-2">
                            <div class="col-2 text-start fw-bold small text-info">
                                4. INFO ROLL<br>
                                <small class="text-muted fw-normal" style="font-size: 0.65rem;">(Data Forklift)</small>
                            </div>
                            
                            @foreach(['db', 'bm', 'bl', 'cm', 'cl'] as $pos)
                                <div class="col text-center">
            @php
                $input_mentah = $spk["gsm_$pos"] ?? '';
                $lebar_spk = floatval($spk['lebar_cm']);
                $is_tembak = false;
                $lebar_tembak = 0;
                
                // Deteksi trik tembak ukuran (Garis miring)
                if(strpos($input_mentah, '/') !== false) {
                    $is_tembak = true;
                    $parts = explode('/', $input_mentah);
                    $input_mentah = $parts[0];
                    $lebar_khusus = floatval($parts[1]);
                    $lebar_spk = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                    $lebar_tembak = $lebar_spk;
                }
                
                $gsm_standar = terjemahkanKodeBlade($input_mentah);
                $matchedRolls = [];
                
                if($gsm_standar !== '') {
                    // --- MERGE BUCKET LOGIC (SINKRON DENGAN CONTROLLER) ---
                    // 1. Ubah target pencarian SPK: Jika B atau T, anggap mencari K
                    $target_gsm = $gsm_standar;
                    if (in_array(substr($target_gsm, 0, 1), ['B', 'T'])) {
                        $target_gsm = 'K' . substr($target_gsm, 1);
                    }

                    // 2. Cari Jodoh di data Forklift (Yang sudah disorting 0 Kg maju duluan)
                    foreach($transaksiRolls as $r) {
                        $r_lebar = floatval($r->masterKertas->lebar ?? 0);
                        $r_lebar = $r_lebar > 500 ? ($r_lebar / 10) : $r_lebar;

                        $r_gsm_asli = terjemahkanKodeBlade($r->masterKertas->gsm ?? '');
                        $r_gsm_normalized = $r_gsm_asli;

                        // Anggap roll B/T dari forklift sebagai K juga agar bisa digabung
                        if (in_array(substr($r_gsm_normalized, 0, 1), ['B', 'T'])) {
                            $r_gsm_normalized = 'K' . substr($r_gsm_normalized, 1);
                        }

                        $r_pos = strtoupper($r->posisi_mesin);

                        // Jika ukuran, posisi, dan rumpun kertas (K/B/T) cocok, masukkan antrean!
                        if($r_lebar == $lebar_spk && $r_gsm_normalized == $target_gsm && $r_pos == strtoupper($pos)) {
                            $matchedRolls[] = $r;
                        }
                    }
                }
            @endphp

                                    @if($is_tembak)
                                        <div class="badge bg-danger mb-1 shadow-sm" style="font-size: 0.55rem;">⚠️ Slash: {{ $lebar_tembak }}cm</div>
                                    @endif
                                    
                                    @if(count($matchedRolls) > 0)
                                        @php
                                            $kebutuhan_spk = floatval($spk["akt_$pos"] ?? 0);
                                        @endphp

                                        @foreach($matchedRolls as $r)
                                            @php
                                                $saldo_saat_ini = $saldo_roll_global[$r->id] ?? 0;
                                                $diambil = 0;

                                                if($kebutuhan_spk > 0 && $saldo_saat_ini > 0) {
                                                    $diambil = min($kebutuhan_spk, $saldo_saat_ini);
                                                    $saldo_roll_global[$r->id] -= $diambil;
                                                    $kebutuhan_spk -= $diambil;

                                                    $nama_spk = $spk['no_spk'] ?? 'Tanpa Nama';
                                                    $pemakaian_roll_di_spk[$r->id][] = "SPK #" . ($index + 1) . " <span class='text-muted' style='font-size:0.7rem'>($nama_spk)</span>";
                                                }
                                            @endphp

                                            @if($diambil > 0.01) 
                                                <div class="border border-success rounded p-1 mb-1 bg-light text-center shadow-sm" style="font-size: 0.65rem; line-height: 1.2;">
                                                    <span class="fw-bold text-dark">{{ $r->no_roll ?? 'Tanpa Nama' }}</span><br>
                                                    <span class="text-success fw-bold">{{ number_format($diambil, 2) }} Kg</span>
                                                </div>
                                            @endif
                                        @endforeach

                                        @if($kebutuhan_spk > 0.1)
                                            <div class="border border-warning rounded p-1 mb-1 bg-light text-center shadow-sm" style="font-size: 0.65rem; line-height: 1.1;">
                                                <span class="text-warning fw-bold">⚠️ Sisa: {{ number_format($kebutuhan_spk, 2) }} Kg</span><br>
                                                <span class="text-muted" style="font-size: 0.55rem;">(Roll tak cukup)</span>
                                            </div>
                                        @endif

                                    @elseif($input_mentah !== '')
                                        <div class="border border-danger rounded p-1 mb-1 bg-white text-center shadow-sm" style="font-size: 0.65rem;">
                                            <span class="text-danger fw-bold">❌ Kosong</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            <div class="col-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            
        </div>
        <div class="card shadow-sm border-primary mb-4 mx-auto" style="border-width: 2px; border-style: dashed; max-width: 100%;">
            <div class="card-body bg-light">
                <h6 class="fw-bold text-primary mb-3">🧠 TAMBAH SPK CEPAT VIA AI (Paste JSON)</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-dark">1. Salin Prompt Ini & Buka ChatGPT:</label>
                        <div class="input-group input-group-sm mb-2 shadow-sm">
                            <textarea id="prompt-chatgpt" class="form-control text-muted" rows="3" readonly style="font-size: 0.75rem;">Kamu sistem OCR corrugator. Aku kasih 2 foto. ATURAN WAJIB: 1. Gabung data berdasar 'Seq'. 2. SPK: Gabung ID & Customer pakai separator ' / ' ke key 'spk'. 3. LEBAR & METER: Buat key 'lebar' (dari Width). Untuk key 'meter', WAJIB AMBIL ANGKA DARI KOLOM 'Total' pada gambar kedua! 4. KERTAS: Pisah sesuai urutan ke key 'db', 'bm', 'bl', 'cm', 'cl'. Kosongkan ("") jika posisi tidak dipakai. 5. KEMBALIKAN HANYA JSON murni array of objects di dalam key 'data'. Tanpa markdown.</textarea>
                            <button class="btn btn-primary fw-bold" type="button" onclick="copyPrompt()">📋 COPY</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-dark">2. Paste Hasil JSON di sini:</label>
                        <textarea id="input-json-manual" class="form-control form-control-sm mb-2 shadow-sm" rows="3" placeholder='{"data": [{"spk": "123 / BAA", "lebar": 1750, "meter": 2000, ...}]}'></textarea>
                        <button type="button" class="btn btn-primary btn-sm fw-bold w-100 py-2 shadow-sm" onclick="prosesPasteJSONEdit()">
                            ⚡ EKSTRAK & TAMBAHKAN SPK KE BAWAH
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mb-5">
            <button type="button" class="btn btn-outline-primary fw-bold px-5 py-2 shadow-sm" onclick="tambahCardKosong()">➕ TAMBAH SPK KOSONG</button>
        </div>

        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white fw-bold fs-5 text-center">📊 GRAND TOTAL TEORI</div>
            <div class="card-body">
                <div class="row text-center fw-bold fs-5 align-items-center">
                    <div class="col-2 fs-6 text-muted text-end">TOTAL TEORI :</div>
                    <div class="col-2 text-secondary" id="gt_db">0.00</div>
                    <div class="col-2 text-secondary" id="gt_bm">0.00</div>
                    <div class="col-2 text-secondary" id="gt_bl">0.00</div>
                    <div class="col-2 text-secondary" id="gt_cm">0.00</div>
                    <div class="col-2 text-secondary" id="gt_cl">0.00</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-secondary mb-5">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                <span>📦 TRACKING & ANOMALI ROLL SHIFT INI</span>
                <span class="badge bg-light text-dark">Data Forklift vs Form SPK</span>
            </div>
            <div class="card-body bg-white p-0">
                @if($transaksiRolls->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <em>Belum ada data roll kertas yang dicatat oleh Forklift untuk Shift ini.</em>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle text-center" style="font-size: 0.85rem;">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th width="4%">No</th>
                                    <th width="12%">No. Roll</th>
                                    <th width="8%">Posisi</th>
                                    <th width="8%">GSM</th>
                                    <th width="8%">Lebar</th>
                                    <th width="9%">B. Awal</th>
                                    <th width="9%">Sisa</th>
                                    <th width="10%" class="text-danger">Terpakai</th>
                                    <th width="16%" class="text-primary text-start">📌 Digunakan Di (SPK)</th>
                                    <th width="16%" class="text-warning text-start">⚠️ Status / Anomali</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $grandTotalPakai = 0; 
                                @endphp
                                @foreach($transaksiRolls as $index => $roll)
                                    @php
                                        $list_spk = $pemakaian_roll_di_spk[$roll->id] ?? [];
                                        $text_spk = count($list_spk) > 0 ? implode('<hr class="my-1 border-secondary">', array_unique($list_spk)) : '<span class="text-muted fst-italic">Tidak ada</span>';

                                        $rowClass = '';
                                        $isSlash = false;
                                        $isBelumPakai = false;
                                        $isDiMesin = false;
                                        $isNyasar = false;
                                        
                                        $awal = floatval($roll->sisa_kilo_awal);
                                        $akhirRaw = $roll->sisa_kilo_akhir;
                                        $isDiMesin = ($akhirRaw === null || $akhirRaw === '');
                                        
                                        if($isDiMesin) {
                                            $pakai = 0;
                                            $akhirText = '<span class="badge bg-warning text-dark">Belum Balik</span>';
                                        } else {
                                            $akhir = floatval($akhirRaw);
                                            $pakai = ($akhir <= 0) ? $awal : ($awal - $akhir);
                                            if($pakai < 0) $pakai = 0;
                                            
                                            $akhirText = ($akhir <= 0) ? 'Habis (0)' : number_format($akhir, 2) . ' Kg';
                                            if($pakai == 0) $isBelumPakai = true;
                                            if($pakai > 0 && count($list_spk) == 0) $isNyasar = true;
                                        }
                                        $grandTotalPakai += $pakai;

                                        $r_lebar = floatval($roll->masterKertas->lebar ?? 0);
                                        $r_lebar_cm = $r_lebar > 500 ? ($r_lebar / 10) : $r_lebar;
                                        if(!in_array($r_lebar_cm, $listLebarSpk)) {
                                            $isSlash = true;
                                        }

                                        if($isSlash || $isBelumPakai || $isNyasar) {
                                            $rowClass = 'row-danger';
                                        } elseif($isDiMesin) {
                                            $rowClass = 'row-warning';
                                        }
                                    @endphp
                                    
                                    <tr class="{{ $rowClass }}">
                                        <td class="fw-bold text-muted">{{ $index + 1 }}</td>
                                        <td class="fw-bold">{{ $roll->no_roll ?? '-' }}</td>
                                        <td><span class="badge bg-dark">{{ strtoupper($roll->posisi_mesin) }}</span></td>
                                        <td class="fw-bold">{{ $roll->masterKertas->gsm ?? '-' }}</td>
                                        <td class="fw-bold">{{ $roll->masterKertas->lebar ?? '-' }} cm</td>
                                        <td class="text-secondary">{{ number_format($awal, 2) }}</td>
                                        <td class="text-secondary">{!! $akhirText !!}</td>
                                        <td class="fw-bold text-danger">{{ number_format($pakai, 2) }} Kg</td>
                                        
                                        <td class="text-start fw-bold text-primary" style="line-height: 1.2;">
                                            {!! $text_spk !!}
                                        </td>

                                        <td class="text-start" style="font-size: 0.75rem; line-height: 1.5;">
                                            @if($isDiMesin)
                                                <span class="badge bg-warning text-dark mb-1">⏳ Masih Di Mesin</span><br>
                                            @elseif($isBelumPakai)
                                                <span class="badge bg-danger mb-1">⚠️ Sisa Utuh (Batal Pakai)</span><br>
                                            @endif

                                            @if($isNyasar)
                                                <span class="badge bg-danger mb-1">⚠️ Terpakai tapi Nyasar (Loss/Gagal masuk Form SPK)</span><br>
                                            @endif

                                            @if($isSlash)
                                                <span class="badge bg-danger mb-1">⚠️ Beda Ukuran SPK (Slash)</span><br>
                                            @endif
                                            
                                            @if(!$isDiMesin && !$isBelumPakai && !$isSlash && !$isNyasar)
                                                <span class="badge bg-success">✔️ Normal (Cocok)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary fw-bold text-danger">
                                <tr>
                                    <td colspan="7" class="text-end">GRAND TOTAL KERTAS DIPROSES SHIFT INI :</td>
                                    <td colspan="3" class="text-start ms-2">{{ number_format($grandTotalPakai, 2) }} Kg</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </form>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => { hitungKalkulator('init'); });

    function reindexSPK() {
        let cards = document.querySelectorAll('.spk-card');
        cards.forEach((card, index) => {
            let nomorUrut = index + 1;
            card.id = "spk-" + nomorUrut;
            card.querySelector('.judul-spk').innerText = "SPK #" + nomorUrut;
        });
    }

    // --- LOGIKA BARU: Cek Selisih Total SPK & Selisih Per Posisi Roll ---
    function cekWarningSelisih(card) {
        // 1. Validasi Skala Makro (Total SPK Card)
        let totalTeori = parseFloat(card.querySelector('.total-teori-card').value) || 0;
        let totalAktual = parseFloat(card.querySelector('.total-aktual-card').value) || 0;
        let alertBlok = card.querySelector('.warning-selisih-val');
        
        if (alertBlok) {
            if (totalTeori > 0 && totalAktual > 0) {
                let selisihPersen = (Math.abs(totalAktual - totalTeori) / totalTeori) * 100;
                if (selisihPersen > 15) {
                    alertBlok.classList.remove('d-none');
                    card.querySelector('.txt-persen-selisih').innerText = selisihPersen.toFixed(1);
                    card.querySelector('.txt-teori-val').innerText = totalTeori.toFixed(2);
                    card.querySelector('.txt-aktual-val').innerText = totalAktual.toFixed(2);
                } else {
                    alertBlok.classList.add('d-none');
                }
            } else {
                alertBlok.classList.add('d-none');
            }
        }

        // 2. Validasi Skala Mikro (Per Posisi Roll: DB, BM, BL, CM, CL)
        let posisiRolls = ['db', 'bm', 'bl', 'cm', 'cl'];
        posisiRolls.forEach(pos => {
            let tVal = parseFloat(card.querySelector('.kg-' + pos).value) || 0;
            let aVal = parseFloat(card.querySelector('.akt-' + pos).value) || 0;
            let warnEl = card.querySelector('.warn-pos-' + pos);
            let inputEl = card.querySelector('.akt-' + pos);

            if (warnEl) {
                let isAnomaly = false;
                let pesanWarning = "";

                if (tVal > 0 && aVal > 0) {
                    let diff = (Math.abs(aVal - tVal) / tVal) * 100;
                    if (diff > 15) { // Batas toleransi per item 15%
                        isAnomaly = true;
                        pesanWarning = `⚠️ Selisih ${diff.toFixed(0)}%`;
                    }
                } else if (tVal === 0 && aVal > 0) {
                    // Proteksi Fatal: Di sistem teorinya 0 Kg (kertas tidak dipakai), tapi operator malah isi aktual beratnya!
                    isAnomaly = true;
                    pesanWarning = "⚠️ Salah Kolom!";
                }

                // Tampilkan efek error jika terdeteksi anomali data
                if (isAnomaly) {
                    warnEl.classList.remove('d-none');
                    warnEl.innerText = pesanWarning;
                    inputEl.style.setProperty('border-color', '#dc3545', 'important'); // Kasih border merah menyala di inputnya
                } else {
                    warnEl.classList.add('d-none');
                    warnEl.innerText = "";
                    inputEl.style.setProperty('border-color', '#feb2b2', 'important'); // Kembalikan ke warna soft default
                }
            }
        });
    }

    // Jika admin mengetik langsung di kolom aktual kecil per posisi roll
    function hitungManualCard(input) {
        let card = input.closest('.spk-card');
        
        let aDB = parseFloat(card.querySelector('.akt-db').value) || 0;
        let aBM = parseFloat(card.querySelector('.akt-bm').value) || 0;
        let aBL = parseFloat(card.querySelector('.akt-bl').value) || 0;
        let aCM = parseFloat(card.querySelector('.akt-cm').value) || 0;
        let aCL = parseFloat(card.querySelector('.akt-cl').value) || 0;

        let totalCard = aDB + aBM + aBL + aCM + aCL;
        card.querySelector('.total-aktual-card').value = totalCard.toFixed(2);
        
        // Jalankan deteksi warning real-time untuk card ini
        cekWarningSelisih(card);
        
        // Update total global di form bawah agar angkanya sinkron
        updateGlobalSump();
    }

    function updateGlobalSump() {
        let totalDB = 0, totalBM = 0, totalBL = 0, totalCM = 0, totalCL = 0;
        document.querySelectorAll('.spk-card').forEach(card => {
            totalDB += floatInputClean(card.querySelector('.akt-db').value);
            totalBM += floatInputClean(card.querySelector('.akt-bm').value);
            totalBL += floatInputClean(card.querySelector('.akt-bl').value);
            totalCM += floatInputClean(card.querySelector('.akt-cm').value);
            totalCL += floatInputClean(card.querySelector('.akt-cl').value);
        });
        document.getElementById('akt_global_db').value = totalDB.toFixed(2);
        document.getElementById('akt_global_bm').value = totalBM.toFixed(2);
        document.getElementById('akt_global_bl').value = totalBL.toFixed(2);
        document.getElementById('akt_global_cm').value = totalCM.toFixed(2);
        document.getElementById('akt_global_cl').value = totalCL.toFixed(2);
    }

    function floatInputClean(val) {
        return parseFloat(val) || 0;
    }

    // Fungsi membelah garis miring dan membuang huruf
    function getGsmBersih(val) {
        if (!val) return 0;
        let kiriSaja = val.toString().split('/')[0];
        return parseFloat(kiriSaja.replace(/[^0-9]/g, '')) || 0;
    }

    function hitungKalkulator(triggerType = 'normal') {
        let cards = document.querySelectorAll('.spk-card');
        let gtDB = 0, gtBM = 0, gtBL = 0, gtCM = 0, gtCL = 0;
        let totalMeterAll = 0; 

        // 1. HITUNG TEORI (SUDAH ANTI MELEDAK)
        cards.forEach(card => {
            let rawLebar = parseFloat(card.querySelector('.input-lebar').value) || 0;
            let lebarM = rawLebar > 500 ? (rawLebar / 1000) : (rawLebar / 100);
            let panjangM = parseFloat(card.querySelector('.input-panjang').value) || 0;
            totalMeterAll += panjangM;

            let fBM = parseFloat(card.querySelector('.input-faktor-bm').value) || 1.36;
            let fCM = parseFloat(card.querySelector('.input-faktor-cm').value) || 1.46;

            let gDB = getGsmBersih(card.querySelector('.input-db').value);
            let gBM = getGsmBersih(card.querySelector('.input-bm').value);
            let gBL = getGsmBersih(card.querySelector('.input-bl').value);
            let gCM = getGsmBersih(card.querySelector('.input-cm').value);
            let gCL = getGsmBersih(card.querySelector('.input-cl').value);

            let kgDB = (panjangM * lebarM * gDB * 1.0) / 1000;
            let kgBM = (panjangM * lebarM * gBM * fBM) / 1000;
            let kgBL = (panjangM * lebarM * gBL * 1.0) / 1000;
            let kgCM = (panjangM * lebarM * gCM * fCM) / 1000;
            let kgCL = (panjangM * lebarM * gCL * 1.0) / 1000;

            card.querySelector('.kg-db').value = kgDB.toFixed(2);
            card.querySelector('.kg-bm').value = kgBM.toFixed(2);
            card.querySelector('.kg-bl').value = kgBL.toFixed(2);
            card.querySelector('.kg-cm').value = kgCM.toFixed(2);
            card.querySelector('.kg-cl').value = kgCL.toFixed(2);
            
            let totalTeoriCard = kgDB + kgBM + kgBL + kgCM + kgCL;
            card.querySelector('.total-teori-card').value = totalTeoriCard.toFixed(2);

            gtDB += kgDB; gtBM += kgBM; gtBL += kgBL; gtCM += kgCM; gtCL += kgCL;
        });

        document.getElementById('gt_db').innerText = gtDB.toFixed(2);
        document.getElementById('gt_bm').innerText = gtBM.toFixed(2);
        document.getElementById('gt_bl').innerText = gtBL.toFixed(2);
        document.getElementById('gt_cm').innerText = gtCM.toFixed(2);
        document.getElementById('gt_cl').innerText = gtCL.toFixed(2);

        // 2. HITUNG AKTUAL PRORATE
        if(triggerType === 'global') {
            let aktDB = parseFloat(document.getElementById('akt_global_db').value) || 0;
            let aktBM = parseFloat(document.getElementById('akt_global_bm').value) || 0;
            let aktBL = parseFloat(document.getElementById('akt_global_bl').value) || 0;
            let aktCM = parseFloat(document.getElementById('akt_global_cm').value) || 0;
            let aktCL = parseFloat(document.getElementById('akt_global_cl').value) || 0;

            cards.forEach(card => {
                let panjangM = parseFloat(card.querySelector('.input-panjang').value) || 0;
                let rasio = totalMeterAll > 0 ? (panjangM / totalMeterAll) : 0;

                let gDB = parseFloat(card.querySelector('.input-db').value.replace(/[^0-9]/g, '')) || 0;
                let gBM = parseFloat(card.querySelector('.input-bm').value.replace(/[^0-9]/g, '')) || 0;
                let gBL = parseFloat(card.querySelector('.input-bl').value.replace(/[^0-9]/g, '')) || 0;
                let gCM = parseFloat(card.querySelector('.input-cm').value.replace(/[^0-9]/g, '')) || 0;
                let gCL = parseFloat(card.querySelector('.input-cl').value.replace(/[^0-9]/g, '')) || 0;

                let jatahDB = gDB > 0 ? (rasio * aktDB) : 0;
                let jatahBM = gBM > 0 ? (rasio * aktBM) : 0;
                let jatahBL = gBL > 0 ? (rasio * aktBL) : 0;
                let jatahCM = gCM > 0 ? (rasio * aktCM) : 0;
                let jatahCL = gCL > 0 ? (rasio * aktCL) : 0;

                card.querySelector('.akt-db').value = jatahDB.toFixed(2);
                card.querySelector('.akt-bm').value = jatahBM.toFixed(2);
                card.querySelector('.akt-bl').value = jatahBL.toFixed(2);
                card.querySelector('.akt-cm').value = jatahCM.toFixed(2);
                card.querySelector('.akt-cl').value = jatahCL.toFixed(2);

                let totalActualCard = jatahDB + jatahBM + jatahBL + jatahCM + jatahCL;
                card.querySelector('.total-aktual-card').value = totalActualCard.toFixed(2);
            });
        }

        // 3. JALANKAN VALIDASI WARNING (TOTAL & DETAIL PER POSISI)
        cards.forEach(card => {
            cekWarningSelisih(card);
        });
    }

    function simpanData() {
        let form = document.getElementById('form-spk-multi');
        
        // Cek semua input aktual, jika kosong paksa jadi 0
        let aktualInputs = document.querySelectorAll('.input-aktual');
        aktualInputs.forEach(input => {
            if (input.value.trim() === "") input.value = "0";
        });

        if (form.reportValidity()) { 
            form.submit(); 
        }
    }

    function cloneCard(btn) {
        let cardAsli = btn.closest('.spk-card');
        let inputsAsli = cardAsli.querySelectorAll('input');
        let values = Array.from(inputsAsli).map(input => input.value);
        let cardBaru = cardAsli.cloneNode(true);
        let inputsBaru = cardBaru.querySelectorAll('input');
        inputsBaru.forEach((input, index) => { input.value = values[index]; });
        cardBaru.querySelector('input[name="no_spk[]"]').value = "";
        document.getElementById('spk-container').appendChild(cardBaru);
        reindexSPK();
        hitungKalkulator('init');
        updateTombolHapus();
    }

    function hapusCard(btn) {
        let card = btn.closest('.spk-card');
        card.remove();
        reindexSPK();
        hitungKalkulator('init');
        updateTombolHapus();
    }

    function updateTombolHapus() {
        let cards = document.querySelectorAll('.spk-card');
        let btns = document.querySelectorAll('.btn-hapus');
        if (cards.length === 1) { btns[0].disabled = true; } else { btns.forEach(btn => btn.disabled = false); }
    }

    function tambahCardKosong() {
        let cardPertama = document.querySelector('.spk-card');
        let cardBaru = cardPertama.cloneNode(true);
        let inputs = cardBaru.querySelectorAll('input');
        inputs.forEach(input => {
            if(!input.classList.contains('input-faktor-bm') && !input.classList.contains('input-faktor-cm')) { input.value = ""; }
        });
        cardBaru.querySelector('.total-teori-card').value = "0.00";
        cardBaru.querySelector('.total-aktual-card').value = "0.00";
        document.getElementById('spk-container').appendChild(cardBaru);
        reindexSPK();
        updateTombolHapus();
    }

    function reRunSapuJagat() {
        let form = document.getElementById('form-spk-multi');
        
        // 1. Cek semua input aktual, jika kosong paksa jadi 0 agar backend tidak error
        let aktualInputs = document.querySelectorAll('.input-aktual');
        aktualInputs.forEach(input => {
            if (input.value.trim() === "") input.value = "0";
        });

        // 2. Bypass validasi bawaan browser (HTML5) khusus untuk fitur Re-Run
        form.noValidate = true; 

        // 3. Submit ke route re-run
        form.action = "{{ url('/hitung-spk/sapujagat/re-run/' . $kalkulasi->id) }}";
        form.submit();
    }

    function copyPrompt() {
        let copyText = document.getElementById("prompt-chatgpt");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        alert("✅ Prompt berhasil disalin! Buka ChatGPT, paste teks ini, dan kirim beserta foto.");
    }

    function prosesPasteJSONEdit() {
        let jsonString = document.getElementById('input-json-manual').value.trim();

        if (!jsonString) {
            alert("⚠️ Paste dulu hasil JSON dari ChatGPT di kolom yang disediakan!");
            return;
        }

        try {
            // Bersihkan teks (Hapus markdown, dll seperti di halaman otomatis)
            jsonString = jsonString.replace(/```json/gi, '').replace(/```/g, '');
            jsonString = jsonString.replace(/[“”]/g, '"').replace(/[‘’]/g, "'");
            jsonString = jsonString.replace(/[\u200B-\u200D\uFEFF]/g, '');

            const firstBrace = jsonString.indexOf('{');
            const lastBrace = jsonString.lastIndexOf('}');
            if (firstBrace !== -1 && lastBrace !== -1) {
                jsonString = jsonString.substring(firstBrace, lastBrace + 1);
            }

            let res = JSON.parse(jsonString);
            if (Array.isArray(res)) res = { data: res };

            if (!res || !res.data || !Array.isArray(res.data)) {
                throw new Error("Format harus memiliki property data berupa array");
            }

            // Looping data dan tambahkan Card satu-satu
            res.data.forEach(item => {
                tambahCardDariJSON(item);
            });

            document.getElementById('input-json-manual').value = '';
            alert(`✨ Berhasil menambahkan ${res.data.length} SPK baru ke urutan paling bawah!`);

        } catch (e) {
            console.error("JSON ERROR:", e);
            alert("❌ Gagal membaca JSON\n\n" + e.message);
        }
    }

    function tambahCardDariJSON(data) {
        // Ambil card pertama sebagai template (blueprint)
        let cardPertama = document.querySelector('.spk-card');
        let cardBaru = cardPertama.cloneNode(true);

        // Deteksi key dari AI (antisipasi huruf besar/kecil)
        let v_spk = data.spk || data.Spk || data.Customer || '';
        let v_lebar = data.lebar || data.Lebar || data.width || data.Width || '';
        let v_meter = data.meter || data.Meter || data.length || data.Length || data.panjang || '';
        let v_db = data.db || data.DB || '';
        let v_bm = data.bm || data.BM || '';
        let v_bl = data.bl || data.BL || '';
        let v_cm = data.cm || data.CM || '';
        let v_cl = data.cl || data.CL || '';

        // Suntikkan data ke input di dalam card baru
        cardBaru.querySelector('input[name="no_spk[]"]').value = v_spk;
        cardBaru.querySelector('.input-lebar').value = v_lebar;
        cardBaru.querySelector('.input-panjang').value = v_meter;
        cardBaru.querySelector('.input-db').value = v_db;
        cardBaru.querySelector('.input-bm').value = v_bm;
        cardBaru.querySelector('.input-bl').value = v_bl;
        cardBaru.querySelector('.input-cm').value = v_cm;
        cardBaru.querySelector('.input-cl').value = v_cl;

        // Kosongkan value kalkulasi dan value aktual (karena ini SPK baru)
        const fieldsToReset = ['.kg-db', '.kg-bm', '.kg-bl', '.kg-cm', '.kg-cl', '.total-teori-card', '.total-aktual-card'];
        fieldsToReset.forEach(selector => cardBaru.querySelector(selector).value = "0.00");
        
        const aktualFields = ['.akt-db', '.akt-bm', '.akt-bl', '.akt-cm', '.akt-cl'];
        aktualFields.forEach(selector => cardBaru.querySelector(selector).value = "0");

        // Bersihkan warning selisih yang mungkin ter-clone dari card pertama
        let warnSelisih = cardBaru.querySelector('.warning-selisih-val');
        if(warnSelisih) warnSelisih.classList.add('d-none');
        
        ['db', 'bm', 'bl', 'cm', 'cl'].forEach(pos => {
            let warnPos = cardBaru.querySelector('.warn-pos-' + pos);
            if(warnPos) { warnPos.classList.add('d-none'); warnPos.innerText = ""; }
            let inputAktual = cardBaru.querySelector('.akt-' + pos);
            if(inputAktual) inputAktual.style.setProperty('border-color', '#feb2b2', 'important');
        });

        // Hapus info "Data Forklift" (Bagian Nomor 4) karena ini SPK baru dan belum dimatching server
        let infoRollCols = cardBaru.querySelectorAll('.border-top.border-info .col.text-center');
        infoRollCols.forEach(col => {
            // Kita sisakan HTML kosong agar rapi, user harus klik "RE-RUN MATCHING" nanti
            col.innerHTML = '<div class="border border-secondary border-dashed rounded p-1 mb-1 bg-light text-center shadow-sm text-muted" style="font-size: 0.60rem;">Belum Matching</div>';
        });

        // Masukkan card ke paling bawah container
        document.getElementById('spk-container').appendChild(cardBaru);
        
        // Rapikan index, tombol hapus, dan hitung otomatis
        reindexSPK();
        updateTombolHapus();
        hitungKalkulator('init');
    }
</script>
</body>
</html>