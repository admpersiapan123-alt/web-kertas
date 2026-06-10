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
        /* Styling Baru: Input Aktual Berwarna Lembut & Tidak Readonly Lagi */
        .input-aktual { background-color: #fff5f5 !important; border-color: #feb2b2 !important; color: #dc3545; font-weight: bold; }
        .input-aktual:focus { background-color: #fff0f0 !important; border-color: #dc3545 !important; box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25); }
    </style>
</head>
<body>
@php
    // 1. Tarik semua data roll dari shift yang bersangkutan
    $transaksiRolls = \App\Models\TransaksiRoll::with('masterKertas')
        ->where('shift_id', $kalkulasi->shift_id ?? 0)
        ->get();

    // -- TAMBAHAN BARU: ISI TANGKI SALDO AWAL SEMUA ROLL --
    $saldo_roll_global = [];
    foreach($transaksiRolls as $r) {
        $awal = floatval($r->sisa_kilo_awal);
        $akhir = floatval($r->sisa_kilo_akhir);
        $saldo_roll_global[$r->id] = ($akhir <= 0) ? $awal : ($awal - $akhir);
    }

    // 2. Buat fungsi kamus mini khusus untuk halaman ini
    if(!function_exists('terjemahkanKodeBlade')) {
        function terjemahkanKodeBlade($kode) {
            if (!$kode || $kode == '-') return '';
            $kode = strtoupper(str_replace(' ', '', $kode));
            
            // Format Forklift (B150, W140)
            if (preg_match('/^([A-Z]+)(\d+)/', $kode, $matches)) {
                $huruf = $matches[1];
                if ($huruf == 'W') $huruf = 'WK';
                
                // --- ATURAN BARU: T jadikan otomatis K ---
                if ($huruf == 'T') $huruf = 'K';
                
                return $huruf . $matches[2];
            }
            
            // Format Monitor (160BB, 160WS)
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
                
                // --- ATURAN BARU: T jadikan otomatis K ---
                if ($prefix == 'T') $prefix = 'K';
                
                return $prefix . $angka_db;
            }
            return $kode;
        }
    }
@endphp
<div class="container py-4" style="max-width: 1000px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/hitung-spk/riwayat') }}" class="btn btn-outline-dark fw-bold shadow-sm">⬅️ KEMBALI</a>
        
        <div>
            <h3 class="fw-bold mb-0">✏️ Edit Sesi: <span class="text-warning">{{ $kalkulasi->kode_sesi }}</span></h3>
            @php
                // Cari data shift berdasarkan ID yang tersimpan
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
                            <input type="text" name="no_spk[]" class="form-control fw-bold text-uppercase" value="{{ $spk['no_spk'] ?? ($spk['no_spk'] ?? '') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">LEBAR KERTAS (cm/mm)</label>
                            <div class="input-group">
                                <input type="number" name="lebar_mm[]" class="form-control fw-bold text-center input-lebar" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()" value="{{ $spk['lebar_cm'] }}" required>
                                <span class="input-group-text">cm/mm</span>
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
                            <div class="col text-primary">BM<br><input type="number" step="0.01" name="faktor_bm[]" class="form-control form-control-sm text-center text-primary fw-bold mx-auto mt-1 input-faktor-bm" value="{{ $spk['faktor_bm'] ?? '1.36' }}" style="width: 60px;" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()"></div>
                            <div class="col">BL (1.0)</div>
                            <div class="col text-primary">CM<br><input type="number" step="0.01" name="faktor_cm[]" class="form-control form-control-sm text-center text-primary fw-bold mx-auto mt-1 input-faktor-cm" value="{{ $spk['faktor_cm'] ?? '1.46' }}" style="width: 60px;" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()"></div>
                            <div class="col">CL (1.0)</div>
                            <div class="col-2 text-success">TOTAL</div>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-2">
                            <div class="col-2 text-start fw-bold small text-muted">1. INPUT GSM</div>
                            <div class="col"><input type="text" name="gsm_db[]" class="form-control form-control-sm fw-bold text-center input-db" value="{{ $spk['gsm_db'] ?? '' }}" onkeyup="hitungKalkulator()"></div>
                            <div class="col"><input type="text" name="gsm_bm[]" class="form-control form-control-sm fw-bold text-center bg-flute input-bm" value="{{ $spk['gsm_bm'] ?? '' }}" onkeyup="hitungKalkulator()"></div>
                            <div class="col"><input type="text" name="gsm_bl[]" class="form-control form-control-sm fw-bold text-center input-bl" value="{{ $spk['gsm_bl'] ?? '' }}" onkeyup="hitungKalkulator()"></div>
                            <div class="col"><input type="text" name="gsm_cm[]" class="form-control form-control-sm fw-bold text-center bg-flute input-cm" value="{{ $spk['gsm_cm'] ?? '' }}" onkeyup="hitungKalkulator()"></div>
                            <div class="col"><input type="text" name="gsm_cl[]" class="form-control form-control-sm fw-bold text-center input-cl" value="{{ $spk['gsm_cl'] ?? '' }}" onkeyup="hitungKalkulator()"></div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-3">
                            <div class="col-2 text-start fw-bold small text-secondary">2. TEORI (Kg)</div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-db" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-bm" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-bl" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-cm" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control form-control-sm text-center input-readonly kg-cl" value="0.00" readonly tabindex="-1"></div>
                            <div class="col-2"><input type="text" class="form-control form-control-sm text-center fw-bold input-readonly text-secondary total-teori-card" value="0.00" readonly tabindex="-1"></div>
                        </div>

                        <div class="row g-2 text-center align-items-center pt-2 border-top border-danger">
                            <div class="col-2 text-start fw-bold small text-danger">3. AKTUAL (Kg)</div>
                            <div class="col"><input type="number" step="0.01" name="aktual_db[]" class="form-control form-control-sm text-center input-aktual akt-db" value="{{ $spk['akt_db'] ?? 0 }}" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)"></div>
                            <div class="col"><input type="number" step="0.01" name="aktual_bm[]" class="form-control form-control-sm text-center input-aktual akt-bm" value="{{ $spk['akt_bm'] ?? 0 }}" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)"></div>
                            <div class="col"><input type="number" step="0.01" name="aktual_bl[]" class="form-control form-control-sm text-center input-aktual akt-bl" value="{{ $spk['akt_bl'] ?? 0 }}" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)"></div>
                            <div class="col"><input type="number" step="0.01" name="aktual_cm[]" class="form-control form-control-sm text-center input-aktual akt-cm" value="{{ $spk['akt_cm'] ?? 0 }}" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)"></div>
                            <div class="col"><input type="number" step="0.01" name="aktual_cl[]" class="form-control form-control-sm text-center input-aktual akt-cl" value="{{ $spk['akt_cl'] ?? 0 }}" onkeyup="hitungManualCard(this)" onchange="hitungManualCard(this)"></div>
                            <div class="col-2"><input type="text" name="total_kg_aktual[]" class="form-control form-control-md text-center fw-bold text-white bg-danger border-danger total-aktual-card" value="{{ $spk['total_aktual'] ?? 0 }}" readonly tabindex="-1"></div>
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
                                        
                                        // Deteksi trik tembak ukuran (Garis miring)
                                        if(strpos($input_mentah, '/') !== false) {
                                            $parts = explode('/', $input_mentah);
                                            $input_mentah = $parts[0];
                                            $lebar_khusus = floatval($parts[1]);
                                            $lebar_spk = $lebar_khusus > 500 ? ($lebar_khusus / 10) : $lebar_khusus;
                                        }
                                        
                                        $gsm_standar = terjemahkanKodeBlade($input_mentah);
                                        $matchedRolls = [];
                                        
                                        // Cari Jodohnya di data Forklift
                                        if($gsm_standar !== '') {
                                            foreach($transaksiRolls as $r) {
                                                $r_lebar = floatval($r->masterKertas->lebar ?? 0);
                                                $r_lebar = $r_lebar > 500 ? ($r_lebar / 10) : $r_lebar;
                                                $r_gsm = terjemahkanKodeBlade($r->masterKertas->gsm ?? '');
                                                $r_pos = strtoupper($r->posisi_mesin);
                                                
                                                if($r_lebar == $lebar_spk && $r_gsm == $gsm_standar && $r_pos == strtoupper($pos)) {
                                                    $matchedRolls[] = $r;
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if(count($matchedRolls) > 0)
                                        @php
                                            // Ambil target beban Kg Aktual milik SPK ini di posisi ini
                                            $kebutuhan_spk = floatval($spk["akt_$pos"] ?? 0);
                                        @endphp

                                        @foreach($matchedRolls as $r)
                                            @php
                                                // Cek berapa sisa saldo roll ini di "Tangki Global"
                                                $saldo_saat_ini = $saldo_roll_global[$r->id] ?? 0;
                                                $diambil = 0;

                                                // Jika SPK masih butuh Kg DAN Roll ini masih punya saldo
                                                if($kebutuhan_spk > 0 && $saldo_saat_ini > 0) {
                                                    
                                                    // Sedot seperlunya (tidak boleh melebihi sisa roll)
                                                    $diambil = min($kebutuhan_spk, $saldo_saat_ini);
                                                    
                                                    // POTONG SALDO ROLL & POTONG KEBUTUHAN SPK
                                                    $saldo_roll_global[$r->id] -= $diambil;
                                                    $kebutuhan_spk -= $diambil;
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
                                                <span class="text-muted" style="font-size: 0.55rem;">(Roll habis / tidak cukup)</span>
                                            </div>
                                        @endif

                                    @elseif($input_mentah !== '')
                                        <div class="border border-danger rounded p-1 mb-1 bg-white text-center" style="font-size: 0.65rem;">
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

        <div class="card shadow-sm border-danger mb-4">
            <div class="card-header bg-danger text-white fw-bold fs-5 text-center">🎯 RESET/HITUNG PROPORSIONAL GLOBAL (DARI FORKLIFT)</div>
            <div class="card-body bg-light">
                @php
                    $sum_db = collect($kalkulasi->data_spk)->sum('akt_db');
                    $sum_bm = collect($kalkulasi->data_spk)->sum('akt_bm');
                    $sum_bl = collect($kalkulasi->data_spk)->sum('akt_bl');
                    $sum_cm = collect($kalkulasi->data_spk)->sum('akt_cm');
                    $sum_cl = collect($kalkulasi->data_spk)->sum('akt_cl');
                @endphp
                <div class="row text-center fw-bold fs-5 align-items-center">
                    <div class="col-2 fs-6 text-danger text-end">INPUT GLOBAL :<br><small class="text-muted fw-normal">Ketik di sini jika ingin menimpa prorate otomatis</small></div>
                    <div class="col-2"><input type="number" step="0.01" class="form-control fw-bold text-center border-danger" id="akt_global_db" value="{{ $sum_db > 0 ? $sum_db : '' }}" onkeyup="hitungKalkulator('global')" onchange="hitungKalkulator('global')"></div>
                    <div class="col-2"><input type="number" step="0.01" class="form-control fw-bold text-center border-danger" id="akt_global_bm" value="{{ $sum_bm > 0 ? $sum_bm : '' }}" onkeyup="hitungKalkulator('global')" onchange="hitungKalkulator('global')"></div>
                    <div class="col-2"><input type="number" step="0.01" class="form-control fw-bold text-center border-danger" id="akt_global_bl" value="{{ $sum_bl > 0 ? $sum_bl : '' }}" onkeyup="hitungKalkulator('global')" onchange="hitungKalkulator('global')"></div>
                    <div class="col-2"><input type="number" step="0.01" class="form-control fw-bold text-center border-danger" id="akt_global_cm" value="{{ $sum_cm > 0 ? $sum_cm : '' }}" onkeyup="hitungKalkulator('global')" onchange="hitungKalkulator('global')"></div>
                    <div class="col-2"><input type="number" step="0.01" class="form-control fw-bold text-center border-danger" id="akt_global_cl" value="{{ $sum_cl > 0 ? $sum_cl : '' }}" onkeyup="hitungKalkulator('global')" onchange="hitungKalkulator('global')"></div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-secondary mb-5">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                <span>📦 TRACKING: DAFTAR ROLL KERTAS TERPAKAI PADA SHIFT INI</span>
                <span class="badge bg-light text-dark">Data Laporan Forklift</span>
            </div>
            <div class="card-body bg-white p-0">
                @php
                    // Tarik data roll langsung berdasarkan shift_id yang tersimpan di sesi ini
                    $transaksiRolls = \App\Models\TransaksiRoll::with('masterKertas')
                        ->where('shift_id', $kalkulasi->shift_id)
                        ->get();
                @endphp

                @if($transaksiRolls->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <em>Belum ada data roll kertas yang dicatat oleh Forklift untuk Shift ini.</em>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle text-center" style="font-size: 0.9rem;">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">No. Roll</th>
                                    <th width="15%">Posisi Mesin</th>
                                    <th width="10%">Gramatur</th>
                                    <th width="10%">Lebar (cm)</th>
                                    <th width="15%">Berat Awal</th>
                                    <th width="15%">Sisa Akhir</th>
                                    <th width="15%" class="text-danger">Total Terpakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $grandTotalPakai = 0; 
                                @endphp
                                @foreach($transaksiRolls as $index => $roll)
                                    @php
                                        $awal = floatval($roll->sisa_kilo_awal);
                                        $akhir = floatval($roll->sisa_kilo_akhir);
                                        // Rumus ludes: kalau akhir 0, berarti kepakai semua (awal)
                                        $pakai = ($akhir <= 0) ? $awal : ($awal - $akhir);
                                        $grandTotalPakai += $pakai;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-muted">{{ $index + 1 }}</td>
                                        <td class="fw-bold">{{ $roll->no_roll ?? '-' }}</td>
                                        <td><span class="badge bg-dark">{{ strtoupper($roll->posisi_mesin) }}</span></td>
                                        <td class="fw-bold">{{ $roll->masterKertas->gsm ?? '-' }}</td>
                                        <td class="fw-bold">{{ $roll->masterKertas->lebar ?? '-' }}</td>
                                        <td class="text-secondary">{{ number_format($awal, 2) }} Kg</td>
                                        <td class="text-secondary">{{ $akhir <= 0 ? 'Habis (0)' : number_format($akhir, 2) . ' Kg' }}</td>
                                        <td class="fw-bold text-danger">{{ number_format($pakai, 2) }} Kg</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary fw-bold text-danger">
                                <tr>
                                    <td colspan="7" class="text-end">GRAND TOTAL KERTAS DIPROSES SHIFT INI :</td>
                                    <td>{{ number_format($grandTotalPakai, 2) }} Kg</td>
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

    // FUNGSI BARU: Jika admin mengetik langsung di kolom aktual kecil per posisi roll
    function hitungManualCard(input) {
        let card = input.closest('.spk-card');
        
        let aDB = parseFloat(card.querySelector('.akt-db').value) || 0;
        let aBM = parseFloat(card.querySelector('.akt-db').value) || 0; // typo fix to accurate elements
        let aBL = parseFloat(card.querySelector('.akt-bl').value) || 0;
        let aCM = parseFloat(card.querySelector('.akt-cm').value) || 0;
        let aCL = parseFloat(card.querySelector('.akt-cl').value) || 0;
        
        // Re-read correct references
        aDB = parseFloat(card.querySelector('.akt-db').value) || 0;
        aBM = parseFloat(card.querySelector('.akt-bm').value) || 0;
        aBL = parseFloat(card.querySelector('.akt-bl').value) || 0;
        aCM = parseFloat(card.querySelector('.akt-cm').value) || 0;
        aCL = parseFloat(card.querySelector('.akt-cl').value) || 0;

        let totalCard = aDB + aBM + aBL + aCM + aCL;
        card.querySelector('.total-aktual-card').value = totalCard.toFixed(2);
        
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
// Fungsi baru untuk membelah garis miring dan membuang huruf
    function getGsmBersih(val) {
        if (!val) return 0;
        let kiriSaja = val.toString().split('/')[0]; // Ambil yang kiri saja (sebelum garis miring)
        return parseFloat(kiriSaja.replace(/[^0-9]/g, '')) || 0; // Baru buang hurufnya
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

            // BUG FIX: Gunakan fungsi getGsmBersih() agar slash '/' tidak bikin error 82 Ton!
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

                // Gunakan gsm bersih juga di sini untuk deteksi pemakaian posisi roll
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
    }

    function simpanData() {
        let form = document.getElementById('form-spk-multi');
        if (form.reportValidity()) { form.submit(); }
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
        if (form.reportValidity()) {
            // Belokkan action form ke rute re-run otomatis
            form.action = "{{ url('/hitung-spk/sapujagat/re-run/' . $kalkulasi->id) }}";
            form.submit();
        }
    }
</script>
</body>
</html>