<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail & Revisi V2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .spk-card { transition: all 0.2s; border: 2px solid #dee2e6; border-radius: 10px; }
        .spk-card:hover { border-color: #0d6efd; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .info-roll-box { font-size: 0.75rem; padding: 4px; border: 1px solid #198754; border-radius: 5px; margin-bottom: 3px; background-color: #f8fff9; }
    </style>
</head>
<body class="bg-light">

@php
    // --- SETUP SALDO, KAMUS & TEORI ---
    $saldo_roll_global = [];
    $group_aktual = [];
    $group_teori = [];

    // FUNGSI KAMUS YANG SUDAH DIPERBAIKI (BUG 111 -> 110 FIXED!)
    if(!function_exists('terjemahkanKodeBlade')) {
        function terjemahkanKodeBlade($kode) {
            if (!$kode || $kode == '-') return '';
            $kode = strtoupper(str_replace(' ', '', $kode));
            
            if (preg_match('/^([A-Z]+)(\d+)/', $kode, $matches)) {
                $h = $matches[1]; if ($h == 'W') $h = 'WK'; 
                // PERHATIAN: T tidak diubah ke K di sini, agar bisa dilacak prioritasnya!
                return $h . $matches[2];
            }
            if (preg_match('/^(\d+)([A-Z]+)/', $kode, $matches)) {
                $a = $matches[1]; $h = substr($matches[2], 0, 1);
                if (!in_array($h, ['K', 'B', 'T', 'M', 'W'])) $h = 'M';
                
                // Bug Fix: Angka nanggung dikonversi!
                if ($a == '101') $a = '100';
                if ($a == '111') $a = '110';
                if ($a == '113') $a = '112';
                if ($a == '127') $a = '125';
                if ($a == '137') $a = '135';
                if ($a == '160') $a = ($h == 'W') ? '140' : '150';
                
                $p = ($h == 'W') ? 'WK' : $h; 
                return $p . $a;
            }
            return $kode;
        }
    }

    if(!function_exists('hitungTeoriBlade')) {
        function hitungTeoriBlade($meter, $lebar_cm, $gsm_input, $pos, $faktor_bm, $faktor_cm) {
            if (!$gsm_input || $gsm_input == '-') return 0;
            $lebar_pakai = $lebar_cm;
            if(strpos($gsm_input, '/') !== false) {
                $parts = explode('/', $gsm_input);
                $gsm_input = $parts[0];
                $lebar_pakai = floatval($parts[1]) > 500 ? floatval($parts[1])/10 : floatval($parts[1]);
            }
            $gsm_standar = terjemahkanKodeBlade($gsm_input);
            if ($gsm_standar == '') return 0;
            $angka = floatval(preg_replace('/[^0-9]/', '', $gsm_standar));
            $faktor = ($pos == 'BM') ? $faktor_bm : (($pos == 'CM') ? $faktor_cm : 1.0);
            return ($meter * ($lebar_pakai / 100) * $angka * $faktor) / 1000;
        }
    }

    // HITUNG AKTUAL GROUP DARI FORKLIFT (Untuk Tabel Anomali Bawah)
    foreach($transaksiRolls as $r) {
        $awal = floatval($r->sisa_kilo_awal);
        $akhir = floatval($r->sisa_kilo_akhir);
        $pakai = ($akhir <= 0) ? $awal : ($awal - $akhir);
        $saldo_roll_global[$r->id] = $pakai;

        $lebar = floatval($r->stockKertas->lebar ?? 0);
        $lebar = $lebar > 500 ? ($lebar / 10) : $lebar;
        $gsm = terjemahkanKodeBlade($r->stockKertas->gsm ?? '');
        
        // Khusus grup anomali bawah, B & T disatukan ke K agar match dengan teori
        if (in_array(substr($gsm, 0, 1), ['B', 'T'])) $gsm = 'K' . substr($gsm, 1);
        
        if($gsm !== '') {
            $kunci = $lebar . '_' . $gsm;
            if(!isset($group_aktual[$kunci])) $group_aktual[$kunci] = 0;
            $group_aktual[$kunci] += $pakai;
        }
    }

    // HITUNG TEORI GROUP (Untuk Tabel Anomali Bawah)
    foreach($kalkulasi->data_spk as $spk) {
        $lebar_cm = floatval($spk['lebar_cm']);
        $meter = floatval($spk['panjang_m']);
        $f_bm = floatval($spk['faktor_bm'] ?? 1.36);
        $f_cm = floatval($spk['faktor_cm'] ?? 1.46);

        foreach(['db', 'bm', 'bl', 'cm', 'cl'] as $pos) {
            $input_m = $spk["gsm_$pos"] ?? '';
            $lbr_pakai = $lebar_cm;
            if(strpos($input_m, '/') !== false) {
                $pts = explode('/', $input_m);
                $input_m = $pts[0];
                $lbr_pakai = floatval($pts[1]) > 500 ? floatval($pts[1])/10 : floatval($pts[1]);
            }
            $std = terjemahkanKodeBlade($input_m);
            if($std !== '') {
                if (in_array(substr($std, 0, 1), ['B', 'T'])) $std = 'K' . substr($std, 1);
                $kunci = $lbr_pakai . '_' . $std;
                $teori = hitungTeoriBlade($meter, $lebar_cm, $spk["gsm_$pos"], strtoupper($pos), $f_bm, $f_cm);
                if(!isset($group_teori[$kunci])) $group_teori[$kunci] = 0;
                $group_teori[$kunci] += $teori;
            }
        }
    }
@endphp

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/versi2/riwayat') }}" class="btn btn-dark fw-bold">⬅️ KEMBALI</a>
        <h4 class="fw-bold text-primary mb-0">👁️ DETAIL & REVISI V2</h4>
        <span class="badge bg-primary fs-6">{{ $kalkulasi->kode_sesi }}</span>
    </div>

    <div class="card shadow-sm border-secondary mb-4" style="border-width: 2px; border-style: dashed;">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold small">📋 TAMBAH SPK BARU VIA JSON AI</span>
            <button type="button" class="btn btn-xs btn-light fw-bold text-dark py-0 px-2" style="font-size:0.75rem;" onclick="copyPromptAI()">📄 PROMPT AI</button>
        </div>
        <div class="card-body bg-white p-2">
            <div class="input-group input-group-sm">
                <textarea id="json_paste" class="form-control" rows="1" style="font-family: monospace; font-size: 0.75rem;" placeholder='Paste JSON di sini...'></textarea>
                <button type="button" class="btn btn-secondary fw-bold px-3" onclick="injectTambahanJson()">🔌 SUNTIK TAMBAHAN</button>
            </div>
        </div>
    </div>

    <form action="{{ url('/versi2/pencocokan/re-run/'.$kalkulasi->id) }}" method="POST">
        @csrf
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Susunan Kartu SPK</h5>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold small text-muted">Target Shift:</span>
                <select name="shift_v2_id" class="form-select form-select-sm fw-bold border-dark" style="width: auto;" required>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ $kalkulasi->shift_v2_id == $s->id ? 'selected' : '' }}>
                            {{ $s->shift }} ({{ date('d M', strtotime($s->tanggal)) }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="spk-container">
            @php $monopoli_roll = []; @endphp @foreach($kalkulasi->data_spk as $index => $spk)
            @php
                $lebar_cm = floatval($spk['lebar_cm']);
                $meter = floatval($spk['panjang_m']);
                $f_bm = floatval($spk['faktor_bm'] ?? 1.36);
                $f_cm = floatval($spk['faktor_cm'] ?? 1.46);

                $t_db = hitungTeoriBlade($meter, $lebar_cm, $spk['gsm_db'], 'DB', $f_bm, $f_cm);
                $t_bm = hitungTeoriBlade($meter, $lebar_cm, $spk['gsm_bm'], 'BM', $f_bm, $f_cm);
                $t_bl = hitungTeoriBlade($meter, $lebar_cm, $spk['gsm_bl'], 'BL', $f_bm, $f_cm);
                $t_cm = hitungTeoriBlade($meter, $lebar_cm, $spk['gsm_cm'], 'CM', $f_bm, $f_cm);
                $t_cl = hitungTeoriBlade($meter, $lebar_cm, $spk['gsm_cl'], 'CL', $f_bm, $f_cm);
            @endphp
            <div class="card spk-card bg-white mb-4 shadow-sm border-dark">
                <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center border-bottom border-secondary gap-2">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="text" name="seq[]" class="form-control form-control-sm text-center" style="width: 50px;" value="{{ $spk['seq'] ?? '' }}" placeholder="Seq">
                        <input type="text" name="no_spk[]" class="form-control form-control-sm fw-bold text-uppercase border-primary" style="width: 180px;" value="{{ $spk['no_spk'] }}" placeholder="No SPK" required>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <input type="number" name="lebar_mm[]" class="form-control text-center realtime-trigger inp-lebar" value="{{ $lebar_cm * 10 }}" placeholder="Lebar" required>
                            <span class="input-group-text">mm</span>
                        </div>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <input type="number" name="panjang_m[]" class="form-control text-center text-primary fw-bold realtime-trigger inp-meter" value="{{ $meter }}" placeholder="Meter" required>
                            <span class="input-group-text">m</span>
                        </div>
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <span class="input-group-text bg-warning text-dark fw-bold">F.BM</span>
                            <input type="number" step="0.01" name="faktor_bm[]" class="form-control text-center realtime-trigger inp-fbm" value="{{ $f_bm }}" required>
                        </div>
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <span class="input-group-text bg-warning text-dark fw-bold">F.CM</span>
                            <input type="number" step="0.01" name="faktor_cm[]" class="form-control text-center realtime-trigger inp-fcm" value="{{ $f_cm }}" required>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm mt-2 mt-xl-0">
                        <button type="button" class="btn btn-outline-primary fw-bold" onclick="cloneCard(this)">📄 CLONE</button>
                        <button type="button" class="btn btn-outline-danger fw-bold" onclick="hapusCard(this)">❌ HAPUS</button>
                    </div>
                </div>
                
                <div class="card-body p-0 table-responsive">
                    <table class="table table-bordered text-center align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-secondary">
                            <tr>
                                <th width="15%">PARAMETER</th>
                                <th width="17%">DB</th><th width="17%">BM</th><th width="17%">BL</th><th width="17%">CM</th><th width="17%">CL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-dark text-start align-middle">📝 1. Input Kertas</td>
                                <td><input type="text" name="gsm_db[]" class="form-control form-control-sm text-center text-uppercase realtime-trigger inp-db" value="{{ $spk['gsm_db'] }}"></td>
                                <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm text-center text-uppercase bg-warning bg-opacity-25 realtime-trigger inp-bm" value="{{ $spk['gsm_bm'] }}"></td>
                                <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm text-center text-uppercase realtime-trigger inp-bl" value="{{ $spk['gsm_bl'] }}"></td>
                                <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm text-center text-uppercase bg-warning bg-opacity-25 realtime-trigger inp-cm" value="{{ $spk['gsm_cm'] }}"></td>
                                <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm text-center text-uppercase realtime-trigger inp-cl" value="{{ $spk['gsm_cl'] }}"></td>
                            </tr>
                            
                            <tr class="bg-light bg-opacity-50 hasil-lama">
                                <td class="fw-bold text-primary text-start">📐 2. Teori (Kg)</td>
                                <td class="text-primary fw-bold res-teori-db">{{ number_format($t_db, 2) }}</td>
                                <td class="text-primary fw-bold res-teori-bm">{{ number_format($t_bm, 2) }}</td>
                                <td class="text-primary fw-bold res-teori-bl">{{ number_format($t_bl, 2) }}</td>
                                <td class="text-primary fw-bold res-teori-cm">{{ number_format($t_cm, 2) }}</td>
                                <td class="text-primary fw-bold res-teori-cl">{{ number_format($t_cl, 2) }}</td>
                            </tr>

                            <tr class="hasil-lama">
                                <td class="fw-bold text-danger text-start">⚖️ 3. Aktual (Kg)</td>
                                @foreach(['db', 'bm', 'bl', 'cm', 'cl'] as $pos)
                                    @php
                                        $aktual_pos = floatval($spk["akt_$pos"]);
                                        $teori_pos = ${"t_$pos"};
                                        $anomali_html = '';
                                        if($teori_pos > 0) {
                                            $diff = (($aktual_pos - $teori_pos) / $teori_pos) * 100;
                                            if($diff > 15) $anomali_html = '<br><span class="badge bg-danger mt-1" style="font-size:0.65rem;">⚠️ Boros +'.number_format($diff, 0).'%</span>';
                                        }
                                    @endphp
                                    <td class="text-danger fw-bold {{ in_array($pos, ['bm','cm']) ? 'bg-warning bg-opacity-10' : '' }}">
                                        {{ number_format($aktual_pos, 2) }} {!! $anomali_html !!}
                                    </td>
                                @endforeach
                            </tr>

                            <tr class="hasil-lama">
                                <td class="fw-bold text-success text-start align-top">🔎 4. Info Roll</td>
                                @foreach(['db', 'bm', 'bl', 'cm', 'cl'] as $pos)
                                <td class="align-top">
                                    @if(isset($spk["rolls_$pos"]) && is_array($spk["rolls_$pos"]))
                                        @forelse($spk["rolls_$pos"] as $rInfo)
                                            @php
                                                $is_share = !empty($rInfo['is_share']);
                                                $is_force = !empty($rInfo['is_force_share']);
                                                
                                                $box_class = '';
                                                $badge_share = '';
                                                
                                                if ($is_force) {
                                                    $box_class = 'border-danger bg-danger bg-opacity-10';
                                                    $badge_share = ' <span class="text-danger fw-bold small">(Anomali Forklift)</span>';
                                                } elseif ($is_share) {
                                                    // Jika share normal antar SPK berbeda
                                                    $box_class = 'border-warning bg-warning bg-opacity-10';
                                                    $badge_share = ' <span class="text-danger fw-bold small">(Share)</span>';
                                                }
                                            @endphp
                                            <div class="info-roll-box {{ $box_class }}">
                                                <b class="text-dark">{{ $rInfo['no_roll'] }}</b>{!! $badge_share !!}<br>
                                                <span class="text-success fw-bold">{{ number_format($rInfo['kg'], 2) }} Kg</span>
                                            </div>
                                        @empty
                                            <span class="text-muted small">-</span>
                                        @endforelse
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>

        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">📦 STATUS KESELURUHAN ROLL (SHIFT INI)</span>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover text-center align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>No Roll</th>
                            <th>Kertas</th>
                            <th>Lebar</th>
                            <th>Awal (Kg)</th>
                            <th>Terpakai (Kg)</th>
                            <th>Status Fisik</th>
                            <th>Kondisi Pemakaian (Teori vs Aktual)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaksiRolls as $r)
                            @php
                                $awal = floatval($r->sisa_kilo_awal);
                                $akhir = floatval($r->sisa_kilo_akhir);
                                $pakai = ($akhir <= 0) ? $awal : ($awal - $akhir);
                                $is_dipakai = $pakai > 0;
                                
                                $status_pakai = $is_dipakai ? '<span class="badge bg-primary text-white">✅ SUDAH DIPAKAI</span>' : '<span class="badge bg-secondary">❌ BELUM DIPAKAI</span>';

                                $lebar = floatval($r->stockKertas->lebar ?? 0);
                                $lebar = $lebar > 500 ? ($lebar / 10) : $lebar;
                                $gsm = terjemahkanKodeBlade($r->stockKertas->gsm ?? '');
                                if (in_array(substr($gsm, 0, 1), ['B', 'T'])) $gsm = 'K' . substr($gsm, 1);
                                
                                $kondisi = '<span class="badge bg-success">✅ NORMAL</span>';
                                if($gsm !== '' && $is_dipakai) {
                                    $kunci = $lebar . '_' . $gsm;
                                    $t_teori = $group_teori[$kunci] ?? 0;
                                    $t_aktual = $group_aktual[$kunci] ?? 0;

                                    if($t_teori > 0) {
                                        $selisih = (($t_aktual - $t_teori) / $t_teori) * 100;
                                        if($selisih > 15) {
                                            $kondisi = '<span class="badge bg-danger shadow-sm">⚠️ ANOMALI BOROS (+'.number_format($selisih, 1).'%)</span>';
                                        } elseif($selisih < -15) {
                                            $kondisi = '<span class="badge bg-warning text-dark shadow-sm">⚠️ ANOMALI IRIT ('.number_format($selisih, 1).'%)</span>';
                                        }
                                    } else {
                                        $kondisi = '<span class="badge bg-danger">⚠️ ANOMALI (TAK ADA DI SPK)</span>';
                                    }
                                } elseif(!$is_dipakai) {
                                    $kondisi = '-';
                                }
                            @endphp
                            <tr class="{{ $is_dipakai ? '' : 'table-secondary opacity-75' }}">
                                <td class="fw-bold text-primary">{{ $r->stockKertas->no_roll ?? 'N/A' }}</td>
                                <td class="fw-bold">{{ $r->stockKertas->gsm ?? '-' }}</td>
                                <td>{{ floatval($r->stockKertas->lebar ?? 0) }}</td>
                                <td class="text-secondary">{{ number_format($awal, 2) }}</td>
                                <td class="text-success fw-bold">{{ number_format($pakai, 2) }}</td>
                                <td>{!! $status_pakai !!}</td>
                                <td>{!! $kondisi !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5 mt-4">
            <button type="submit" class="btn btn-warning btn-lg fw-bold shadow border-2 border-dark text-dark px-5">⚙️ JALANKAN RE-RUN MATCHING V2</button>
        </div>
    </form>
</div>

<script>
    // --- JAVASCRIPT REALTIME CALCULATION ---
    function ekstrakAngkaGSM(kode) {
        if (!kode || kode === '-') return 0;
        kode = kode.toUpperCase().replace(/\s+/g, '');
        if(kode.includes('/')) kode = kode.split('/')[0];
        let match = kode.match(/\d+/);
        if(!match) return 0;
        let angka = parseInt(match[0]);
        // Bug kamus JS disinkronkan!
        if(angka === 101) angka = 100;
        if(angka === 111) angka = 110;
        if(angka === 113) angka = 112;
        if(angka === 127) angka = 125;
        if(angka === 137) angka = 135;
        if(angka === 160) {
            let hurufBelakang = kode.replace(/\d+/g, '').charAt(0);
            if(kode.match(/^\d+/) && hurufBelakang === 'W') angka = 140; 
            else angka = 150;
        }
        return angka;
    }

    function updateTeoriCard(card) {
        let lebar_mm = parseFloat(card.querySelector('.inp-lebar').value) || 0;
        let lebar_cm = lebar_mm > 500 ? lebar_mm / 10 : lebar_mm;
        let meter = parseFloat(card.querySelector('.inp-meter').value) || 0;
        let f_bm = parseFloat(card.querySelector('.inp-fbm').value) || 1.36;
        let f_cm = parseFloat(card.querySelector('.inp-fcm').value) || 1.46;

        ['db', 'bm', 'bl', 'cm', 'cl'].forEach(pos => {
            let inpGsm = card.querySelector('.inp-' + pos).value;
            let gsmInput = inpGsm;
            let lbrPakai = lebar_cm;
            
            if(inpGsm.includes('/')) {
                let pts = inpGsm.split('/');
                gsmInput = pts[0];
                let lbrKhusus = parseFloat(pts[1]) || 0;
                lbrPakai = lbrKhusus > 500 ? lbrKhusus/10 : lbrKhusus;
            }

            let gsmAngka = ekstrakAngkaGSM(gsmInput);
            let faktor = 1.0;
            if(pos === 'bm') faktor = f_bm;
            if(pos === 'cm') faktor = f_cm;

            let teoriKg = gsmAngka > 0 ? (meter * (lbrPakai / 100) * gsmAngka * faktor) / 1000 : 0;
            card.querySelector('.res-teori-' + pos).innerText = teoriKg > 0 ? teoriKg.toFixed(2) : '0.00';
        });
    }

    document.addEventListener('input', e => {
        if(e.target.classList.contains('realtime-trigger')) {
            let card = e.target.closest('.spk-card');
            if(card) updateTeoriCard(card);
        }
    });

    function hapusCard(btn) {
        if(document.querySelectorAll('.spk-card').length === 1) return alert("Minimal sisa 1 SPK!");
        btn.closest('.spk-card').remove();
    }

    function cloneCard(btn) {
        let ori = btn.closest('.spk-card');
        let clone = ori.cloneNode(true);
        ori.querySelectorAll('input').forEach((inp, idx) => clone.querySelectorAll('input')[idx].value = inp.value);
        clone.querySelector('input[name="no_spk[]"]').value = '';
        clone.querySelectorAll('.hasil-lama').forEach(tr => {
            if(!tr.querySelector('.res-teori-db')) {
                tr.querySelectorAll('td:not(:first-child)').forEach(td => td.innerHTML = '<span class="text-muted small">- Baru -</span>');
            }
        });
        ori.after(clone);
        updateTeoriCard(clone);
    }

    function injectTambahanJson() {
        let text = document.getElementById('json_paste').value.trim();
        if(!text) return alert("Paste teks JSON dulu!");
        try {
            let parsed = JSON.parse(text);
            let items = parsed.data || parsed;
            if(!Array.isArray(items)) return alert("Format JSON harus berupa Array!");

            items.forEach(d => {
                let clone = document.querySelector('.spk-card').cloneNode(true);
                clone.querySelectorAll('.hasil-lama').forEach(tr => {
                    if(!tr.querySelector('.res-teori-db')) tr.querySelectorAll('td:not(:first-child)').forEach(td => td.innerHTML = '<span class="text-muted small">- JSON -</span>');
                });
                clone.querySelector('input[name="seq[]"]').value = d.seq || '';
                clone.querySelector('input[name="no_spk[]"]').value = d.spk || d.no_spk || '';
                clone.querySelector('.inp-lebar').value = d.lebar || d.lebar_mm || '';
                clone.querySelector('.inp-meter').value = d.meter || d.panjang_m || '';
                clone.querySelector('.inp-fbm').value = d.faktor_bm || '1.36';
                clone.querySelector('.inp-fcm').value = d.faktor_cm || '1.46';
                clone.querySelector('.inp-db').value = d.db || '';
                clone.querySelector('.inp-bm').value = d.bm || '';
                clone.querySelector('.inp-bl').value = d.bl || '';
                clone.querySelector('.inp-cm').value = d.cm || '';
                clone.querySelector('.inp-cl').value = d.cl || '';

                document.getElementById('spk-container').appendChild(clone);
                updateTeoriCard(clone);
            });
            document.getElementById('json_paste').value = '';
        } catch(e) { alert("Format JSON Salah!"); }
    }

    function copyPromptAI() {
        navigator.clipboard.writeText(`Kamu adalah sistem ekstraksi OCR untuk monitor mesin corrugator pabrik kardus. Ambil data: Seq, ID SPK, Customer, Width (Lebar), dan deretan kertas (DB, BM, BL, CM, CL) serta target jalan: Length (Meter). Format SPK: ID SPK / Customer. Kembalikan HANYA JSON murni berupa objek array {"data": [...]}.`).then(() => alert("Prompt AI Tersalin!"));
    }
</script>
</body>
</html>