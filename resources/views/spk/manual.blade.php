<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Rinci Per SPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .form-control { border-color: #ced4da; }
        .form-control:focus { border-color: #0d6efd; box-shadow: none; }
        .bg-flute { background-color: #fff3cd !important; } 
        .spk-card { border-left: 5px solid #0d6efd; transition: all 0.3s ease; }
        
        .grid-header { background-color: #e9ecef; border-radius: 5px 5px 0 0; }
        .input-readonly { background-color: transparent !important; border: 1px dashed #ced4da; cursor: not-allowed; }
        /* Box Aktual Eceran: Dibuat lebih terang agar jelas bisa diketik */
        .input-aktual { background-color: #fff !important; border-color: #dc3545 !important; color: #dc3545; font-weight: bold; }
        .input-aktual:focus { background-color: #fff5f5 !important; box-shadow: 0 0 5px rgba(220,53,69,0.5) !important; }
        .input-aktual.bg-warning { background-color: #fff3cd !important; cursor: not-allowed; } /* Jika di-lock oleh bulk */
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 1000px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/hitung-spk') }}" class="btn btn-outline-dark fw-bold shadow-sm">⬅️ KEMBALI</a>
        <h3 class="fw-bold mb-0">🧮 Kalkulator Manual (Hybrid Mode)</h3>
        <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="simpanData()">💾 SAVE SEMUA</button>        
    </div>

    @if ($errors->any())
        <div class="alert alert-danger fw-bold shadow-sm mb-4">
            ⚠️ Gagal Menyimpan! Pastikan semua input wajib terisi.
        </div>
    @endif

    <form id="form-spk-multi" action="{{ url('/hitung-spk/manual/store') }}" method="POST">
        @csrf
        <div id="spk-container">
            
            @php $oldSpks = old('no_spk', ['']); @endphp

            @foreach($oldSpks as $index => $spk_val)
            <div class="card shadow-sm border-0 mb-4 spk-card" id="spk-{{ $index + 1 }}">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 judul-spk">SPK #{{ $index + 1 }}</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-warning fw-bold me-2" onclick="cloneCard(this)">📄 CLONE</button>
                        <button type="button" class="btn btn-sm btn-danger fw-bold btn-hapus" onclick="hapusCard(this)" {{ count($oldSpks) == 1 ? 'disabled' : '' }}>❌ HAPUS</button>
                    </div>
                </div>
                <div class="card-body bg-white">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">NOMOR SPK / CUSTOM</label>
                            <input type="text" name="no_spk[]" class="form-control fw-bold text-uppercase" placeholder="Cth: CIPTA" value="{{ old('no_spk.'.$index) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">LEBAR KERTAS (cm)</label>
                            <div class="input-group">
                                <input type="number" name="lebar_cm[]" class="form-control fw-bold text-center input-lebar" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()" value="{{ old('lebar_cm.'.$index) }}" required>
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">PANJANG LARI (Meter)</label>
                            <div class="input-group">
                                <input type="number" name="panjang_m[]" class="form-control fw-bold text-center input-panjang" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()" value="{{ old('panjang_m.'.$index) }}" required>
                                <span class="input-group-text">m</span>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-white shadow-sm">
                        <div class="row g-2 text-center align-items-end fw-bold small grid-header p-2 mb-2">
                            <div class="col-2 text-start text-muted">PARAMETER</div>
                            <div class="col">DB (1.0)</div>
                            <div class="col text-primary">
                                BM (Faktor)<br>
                                <input type="number" step="0.01" name="faktor_bm[]" class="form-control form-control-sm text-center text-primary fw-bold mx-auto mt-1 input-faktor-bm" value="{{ old('faktor_bm.'.$index, '1.35') }}" style="width: 60px;" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()"> 
                            </div>
                            <div class="col">BL (1.0)</div>
                            <div class="col text-primary">
                                CM (Faktor)<br>
                                <input type="number" step="0.01" name="faktor_cm[]" class="form-control form-control-sm text-center text-primary fw-bold mx-auto mt-1 input-faktor-cm" value="{{ old('faktor_cm.'.$index, '1.43') }}" style="width: 60px;" onkeyup="hitungKalkulator()" onchange="hitungKalkulator()">
                            </div>
                            <div class="col">CL (1.0)</div>
                            <div class="col-2 text-success">TOTAL SPK</div>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-2">
                            <div class="col-2 text-start fw-bold small text-muted">1. INPUT GSM</div>
                            <div class="col"><input type="text" name="gsm_db[]" class="form-control fw-bold text-center input-db" placeholder="GSM" onkeyup="hitungKalkulator()" value="{{ old('gsm_db.'.$index) }}"></div>
                            <div class="col"><input type="text" name="gsm_bm[]" class="form-control fw-bold text-center bg-flute input-bm" placeholder="GSM" onkeyup="hitungKalkulator()" value="{{ old('gsm_bm.'.$index) }}"></div>
                            <div class="col"><input type="text" name="gsm_bl[]" class="form-control fw-bold text-center input-bl" placeholder="GSM" onkeyup="hitungKalkulator()" value="{{ old('gsm_bl.'.$index) }}"></div>
                            <div class="col"><input type="text" name="gsm_cm[]" class="form-control fw-bold text-center bg-flute input-cm" placeholder="GSM" onkeyup="hitungKalkulator()" value="{{ old('gsm_cm.'.$index) }}"></div>
                            <div class="col"><input type="text" name="gsm_cl[]" class="form-control fw-bold text-center input-cl" placeholder="GSM" onkeyup="hitungKalkulator()" value="{{ old('gsm_cl.'.$index) }}"></div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row g-2 text-center align-items-center mb-3">
                            <div class="col-2 text-start fw-bold small text-secondary">2. TEORI (Kg)</div>
                            <div class="col"><input type="text" class="form-control text-center input-readonly kg-db" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control text-center input-readonly kg-bm" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control text-center input-readonly kg-bl" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control text-center input-readonly kg-cm" value="0.00" readonly tabindex="-1"></div>
                            <div class="col"><input type="text" class="form-control text-center input-readonly kg-cl" value="0.00" readonly tabindex="-1"></div>
                            <div class="col-2"><input type="text" class="form-control text-center fw-bold input-readonly text-secondary total-teori-card" value="0.00" readonly tabindex="-1"></div>
                        </div>

                        <div class="row g-2 text-center align-items-center pt-2 border-top border-danger">
                            <div class="col-2 text-start fw-bold small text-danger">3. AKTUAL (Kg)<br><small class="text-muted fw-normal" style="font-size: 0.65rem;">(Bisa diketik manual)</small></div>
                            <div class="col"><input type="number" step="0.01" name="akt_db[]" class="form-control text-center input-aktual akt-db" onkeyup="hitungKalkulator()" value="{{ old('akt_db.'.$index, '0.00') }}"></div>
                            <div class="col"><input type="number" step="0.01" name="akt_bm[]" class="form-control text-center input-aktual akt-bm" onkeyup="hitungKalkulator()" value="{{ old('akt_bm.'.$index, '0.00') }}"></div>
                            <div class="col"><input type="number" step="0.01" name="akt_bl[]" class="form-control text-center input-aktual akt-bl" onkeyup="hitungKalkulator()" value="{{ old('akt_bl.'.$index, '0.00') }}"></div>
                            <div class="col"><input type="number" step="0.01" name="akt_cm[]" class="form-control text-center input-aktual akt-cm" onkeyup="hitungKalkulator()" value="{{ old('akt_cm.'.$index, '0.00') }}"></div>
                            <div class="col"><input type="number" step="0.01" name="akt_cl[]" class="form-control text-center input-aktual akt-cl" onkeyup="hitungKalkulator()" value="{{ old('akt_cl.'.$index, '0.00') }}"></div>
                            <div class="col-2"><input type="text" class="form-control text-center fw-bold text-danger border-danger input-readonly total-aktual-card" value="0.00" readonly tabindex="-1"></div>
                        </div>

                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mb-5">
            <button type="button" class="btn btn-outline-primary fw-bold px-5 py-2 shadow-sm" onclick="tambahCardKosong()">➕ TAMBAH SPK KOSONG</button>
        </div>

        <div class="card shadow-sm border-info mb-4" style="border-width: 2px; border-style: dashed;">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-info mb-0">📦 INPUT BORONGAN (LIVE SYNC)</h6>
                <span class="badge bg-success">⚡ Otomatis</span>
            </div>
            <div class="card-body bg-white">
                <p class="small text-muted mb-3">Kotak kelompok kertas di bawah ini akan terbuat otomatis secara <b>Real-Time</b> saat Anda mengisi, meng-clone, atau menghapus SPK di atas.</p>
                <div id="container-kelompok" class="row g-3">
                    </div>
            </div>
        </div>

        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white fw-bold fs-5 text-center">
                📊 GRAND TOTAL TEORI
            </div>
            <div class="card-body">
                <div class="row text-center fw-bold mb-2 border-bottom pb-2">
                    <div class="col-2 text-muted"></div>
                    <div class="col-2">DB</div><div class="col-2">BM</div><div class="col-2">BL</div><div class="col-2">CM</div><div class="col-2">CL</div>
                </div>
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
    </form>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => hitungKalkulator(false));

    function reindexSPK() {
        let cards = document.querySelectorAll('.spk-card');
        cards.forEach((card, index) => {
            let nomorUrut = index + 1;
            card.id = "spk-" + nomorUrut;
            card.querySelector('.judul-spk').innerText = "SPK #" + nomorUrut;
        });
    }

    // 1. FUNGSI LIVE SYNC KELOMPOK (DIPANGGIL OTOMATIS)
    function generateKelompokOtomatis() {
        let cards = document.querySelectorAll('.spk-card');
        let kelompok = {}; 

        cards.forEach((card, index) => {
            let lebarRaw = parseFloat(card.querySelector('.input-lebar').value) || 0;
            let lebarCm = lebarRaw > 500 ? lebarRaw / 10 : lebarRaw;
            let meter = parseFloat(card.querySelector('.input-panjang').value) || 0;

            ['db', 'bm', 'bl', 'cm', 'cl'].forEach(pos => {
                let gsmMentah = card.querySelector('.input-' + pos).value.trim().toUpperCase();
                if(gsmMentah && gsmMentah !== '-') {
                    let l_pakai = lebarCm;
                    let g_pakai = gsmMentah;
                    
                    if(gsmMentah.includes('/')) {
                        let parts = gsmMentah.split('/');
                        g_pakai = parts[0];
                        let khusus = parseFloat(parts[1]);
                        l_pakai = khusus > 500 ? khusus / 10 : khusus;
                    }

                    let key = l_pakai + '_' + g_pakai + '_' + pos.toUpperCase();
                    if(!kelompok[key]) kelompok[key] = { meter: 0, baris: [] };
                    
                    kelompok[key].meter += meter;
                    kelompok[key].baris.push(index + 1);
                }
            });
        });

        // SIMPAN ANGKA KG YANG SEDANG DIKETIK ADMIN (Agar tidak hilang saat Live Sync)
        let savedBulkValues = {};
        document.querySelectorAll('.input-bulk-kg').forEach(input => {
            savedBulkValues[input.name] = input.value;
        });

        let html = '';
        if (Object.keys(kelompok).length === 0) {
            html = '<div class="col-12"><div class="alert alert-secondary py-2 text-center mb-0 border-0 shadow-sm">Ketik spesifikasi kertas di atas, kelompok akan muncul di sini...</div></div>';
        } else {
            for (let k in kelompok) {
                let val = savedBulkValues[`bulk_aktual[${k}]`] || ''; // Kembalikan angka ketikan Admin
                html += `
                <div class="col-md-4">
                    <div class="card border-info shadow-sm">
                        <div class="card-body p-2 bg-light">
                            <h6 class="fw-bold text-dark mb-1 text-center border-bottom border-info pb-1">${k}</h6>
                            <small class="text-muted d-block text-center mb-2" style="font-size: 0.7rem;">Total: <b class="text-primary">${kelompok[k].meter} m</b> | Baris: ${kelompok[k].baris.join(', ')}</small>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-info text-white fw-bold">Aktual</span>
                                <input type="number" step="0.01" name="bulk_aktual[${k}]" class="form-control fw-bold text-danger text-center input-bulk-kg" placeholder="Ketik Kg" onkeyup="hitungKalkulator(true)" onchange="hitungKalkulator(true)" value="${val}">
                                <span class="input-group-text">Kg</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
        }
        document.getElementById('container-kelompok').innerHTML = html;
    }

    // 2. FUNGSI OTAK KALKULASI UTAMA
    function hitungKalkulator(isFromBulk = false) {
        // Jika ketikan bukan berasal dari kotak Kg Borongan, jalankan Live Sync!
        if (!isFromBulk) {
            generateKelompokOtomatis();
        }

        let cards = document.querySelectorAll('.spk-card');
        let gtDB = 0, gtBM = 0, gtBL = 0, gtCM = 0, gtCL = 0;
        let totalMeterAll = 0; 
        let kelompokMeter = {};

        // Kumpulkan total meter per grup
        cards.forEach(card => {
            let lebarRaw = parseFloat(card.querySelector('.input-lebar').value) || 0;
            let lebarCm = lebarRaw > 500 ? lebarRaw / 10 : lebarRaw;
            let meter = parseFloat(card.querySelector('.input-panjang').value) || 0;

            ['db', 'bm', 'bl', 'cm', 'cl'].forEach(pos => {
                let gsmRaw = card.querySelector('.input-' + pos).value.trim().toUpperCase();
                if (gsmRaw && gsmRaw !== '-') {
                    let g_pakai = gsmRaw, l_pakai = lebarCm;
                    if(gsmRaw.includes('/')) {
                        let parts = gsmRaw.split('/');
                        g_pakai = parts[0];
                        let khusus = parseFloat(parts[1]);
                        l_pakai = khusus > 500 ? khusus/10 : khusus;
                    }
                    let key = l_pakai + '_' + g_pakai + '_' + pos.toUpperCase();
                    if(!kelompokMeter[key]) kelompokMeter[key] = 0;
                    kelompokMeter[key] += meter;
                }
            });
        });

        // Eksekusi Teori & Aktual
        cards.forEach(card => {
            let lebarRaw = parseFloat(card.querySelector('.input-lebar').value) || 0;
            let lebarCm = lebarRaw > 500 ? lebarRaw / 10 : lebarRaw;
            let meter = parseFloat(card.querySelector('.input-panjang').value) || 0;
            let fBM = parseFloat(card.querySelector('.input-faktor-bm').value) || 1.35;
            let fCM = parseFloat(card.querySelector('.input-faktor-cm').value) || 1.43;

            let totalTeoriCard = 0, totalAktualCard = 0;

            ['db', 'bm', 'bl', 'cm', 'cl'].forEach(pos => {
                let gsmRaw = card.querySelector('.input-' + pos).value.trim().toUpperCase();
                let fMult = (pos === 'bm') ? fBM : (pos === 'cm' ? fCM : 1.0);
                let l_pakai = lebarCm, g_pakai = gsmRaw, gsmBersih = 0;
                
                if (gsmRaw && gsmRaw !== '-') {
                    if(gsmRaw.includes('/')) {
                        let parts = gsmRaw.split('/');
                        g_pakai = parts[0];
                        let khusus = parseFloat(parts[1]);
                        l_pakai = khusus > 500 ? khusus/10 : khusus;
                    }
                    gsmBersih = parseFloat(g_pakai.replace(/[^0-9]/g, '')) || 0; 
                }

                let kgTeori = (meter * (l_pakai/100) * gsmBersih * fMult) / 1000;
                card.querySelector('.kg-' + pos).value = kgTeori.toFixed(2);
                totalTeoriCard += kgTeori;

                if(pos === 'db') gtDB += kgTeori;
                if(pos === 'bm') gtBM += kgTeori;
                if(pos === 'bl') gtBL += kgTeori;
                if(pos === 'cm') gtCM += kgTeori;
                if(pos === 'cl') gtCL += kgTeori;

                let aktInput = card.querySelector('.akt-' + pos);
                let key = l_pakai + '_' + g_pakai + '_' + pos.toUpperCase();
                let bulkNode = document.querySelector(`input[name="bulk_aktual[${key}]"]`);

                if (bulkNode && bulkNode.value !== '') {
                    let bulkKg = parseFloat(bulkNode.value) || 0;
                    let totMeterGrup = kelompokMeter[key] || 0;
                    let rasio = totMeterGrup > 0 ? (meter / totMeterGrup) : 0;
                    let jatah = rasio * bulkKg;
                    
                    aktInput.value = jatah.toFixed(2);
                    aktInput.readOnly = true; 
                    aktInput.classList.add('bg-warning'); 
                    totalAktualCard += jatah;
                } else {
                    aktInput.readOnly = false;
                    aktInput.classList.remove('bg-warning');
                    totalAktualCard += parseFloat(aktInput.value) || 0;
                }
            });

            card.querySelector('.total-teori-card').value = totalTeoriCard.toFixed(2);
            card.querySelector('.total-aktual-card').value = totalAktualCard.toFixed(2);
        });

        document.getElementById('gt_db').innerText = gtDB.toFixed(2);
        document.getElementById('gt_bm').innerText = gtBM.toFixed(2);
        document.getElementById('gt_bl').innerText = gtBL.toFixed(2);
        document.getElementById('gt_cm').innerText = gtCM.toFixed(2);
        document.getElementById('gt_cl').innerText = gtCL.toFixed(2);
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
        hitungKalkulator(false); // <-- isFromBulk false: Bikin Live Sync nyala saat di-clone!
        updateTombolHapus();
    }

    function tambahCardKosong() {
        let cardPertama = document.querySelector('.spk-card');
        let cardBaru = cardPertama.cloneNode(true);
        
        let inputs = cardBaru.querySelectorAll('input');
        inputs.forEach(input => {
            if(!input.classList.contains('input-faktor-bm') && !input.classList.contains('input-faktor-cm')) {
                input.value = "";
            }
        });
        
        ['db', 'bm', 'bl', 'cm', 'cl'].forEach(pos => cardBaru.querySelector('.akt-'+pos).value = "0.00");
        cardBaru.querySelector('.total-teori-card').value = "0.00";
        cardBaru.querySelector('.total-aktual-card').value = "0.00";
        document.getElementById('spk-container').appendChild(cardBaru);
        
        reindexSPK(); 
        hitungKalkulator(false); // <-- isFromBulk false: Bikin Live Sync nyala saat tambah kosong!
        updateTombolHapus();
    }

    function hapusCard(btn) {
        let card = btn.closest('.spk-card');
        card.remove();
        reindexSPK(); 
        hitungKalkulator(false); // <-- isFromBulk false: Update grup otomatis saat dihapus
        updateTombolHapus();
    }

    function updateTombolHapus() {
        let cards = document.querySelectorAll('.spk-card');
        let btns = document.querySelectorAll('.btn-hapus');
        if (cards.length === 1) { btns[0].disabled = true; } else { btns.forEach(btn => btn.disabled = false); }
    }
</script>

</body>
</html>