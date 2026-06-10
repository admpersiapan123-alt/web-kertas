<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matching Otomatis (Monitor ke Forklift)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .bg-flute { background-color: #fff3cd !important; }
        .table-input th { font-size: 0.85rem; vertical-align: middle; }
        .table-input td { padding: 0.3rem; }
        .form-control-sm { font-weight: bold; text-align: center; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4 px-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/hitung-spk') }}" class="btn btn-outline-dark fw-bold shadow-sm">⬅️ KEMBALI</a>
        <h3 class="fw-bold mb-0">⚙️ Auto-Match: Monitor Mesin -> Forklift</h3>
        <button type="submit" form="formSapuJagat" class="btn btn-success fw-bold shadow-sm px-4">🚀 PROSES PENCOCOKAN DATA</button>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger fw-bold shadow-sm mb-4">⚠️ Pastikan semua kolom wajib diisi.</div>
    @endif

    <form action="{{ url('/hitung-spk/sapujagat/store') }}" method="POST" id="formSapuJagat">
        @csrf
        
        <div class="card shadow-sm border-dark mb-4" style="max-width: 600px;">
            <div class="card-header bg-dark text-white fw-bold">1. PILIH DATA SHIFT FORKLIFT</div>
            <div class="card-body bg-white">
                <select name="shift_id" class="form-select fw-bold border-dark" required>
                    <option value="">-- Pilih Laporan Shift (Sumber Berat Timbangan) --</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">Shift {{ $shift->shift_ke }} | {{ \Carbon\Carbon::parse($shift->tanggal)->format('d-M-Y') }} | Opr: {{ $shift->kepala_shift }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card shadow-sm border-primary mb-4" style="border-width: 2px;">
            <div class="card shadow-sm border-success mb-4" style="border-width: 2px; border-style: dashed;">
                    <div class="card-body bg-light">
                        <h6 class="fw-bold text-success mb-3">🤖 AI AUTO-FILL (Upload Foto Monitor)</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="small fw-bold">1. Foto Spesifikasi (SPK, Lebar, Kertas)</label>
                                <input type="file" id="foto_spek" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">2. Foto Meteran (Meter Lari/Length)</label>
                                <input type="file" id="foto_meter" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success btn-sm fw-bold w-100 py-2" id="btn-scan-ai" onclick="prosesScanAI()">
                                    ✨ BACA DENGAN AI (GROQ)
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm border-primary mb-4" style="border-width: 2px; border-style: dashed;">
                        <div class="card-body bg-light">
                            <h6 class="fw-bold text-primary mb-3">🧠 PLAN B: BACA VIA CHATGPT APP (Anti Rate-Limit)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-dark">1. Salin Prompt Rahasia Ini:</label>
                                    <div class="input-group input-group-sm mb-2 shadow-sm">
                                        <textarea id="prompt-chatgpt" class="form-control text-muted" rows="3" readonly style="font-size: 0.75rem;">Kamu sistem OCR corrugator. Aku kasih 2 foto. ATURAN WAJIB: 1. Gabung data berdasar 'Seq'. 2. SPK: Gabung ID & Customer pakai separator ' / ' ke key 'spk'. 3. LEBAR & METER: Buat key 'lebar' (dari Width). Untuk key 'meter', WAJIB AMBIL ANGKA DARI KOLOM 'Total' pada gambar kedua! DILARANG KERAS mengambil angka dari kolom 'Length'. 4. KERTAS: Pisah sesuai urutan ke key 'db', 'bm', 'bl', 'cm', 'cl'. Kosongkan ("") jika posisi tidak dipakai. 5. KEMBALIKAN HANYA JSON murni array of objects di dalam key 'data'. Tanpa markdown.</textarea>
                                        <button class="btn btn-primary fw-bold" type="button" onclick="copyPrompt()">📋 COPY</button>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.7rem;">*Paste di ChatGPT HP Anda, lalu upload 2 foto monitor.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-dark">2. Paste Hasil JSON dari ChatGPT di sini:</label>
                                    <textarea id="input-json-manual" class="form-control form-control-sm mb-2 shadow-sm" rows="3" placeholder='{"data": [{"seq": "10", "spk": "123 / BAA", ...}]}'></textarea>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold w-100 py-2 shadow-sm" onclick="prosesPasteJSON()">
                                        ⚡ EKSTRAK JSON KE TABEL
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                
                <span>2. SALIN DAFTAR SPK DARI MONITOR CORRUGATOR</span>
                <button type="button" class="btn btn-sm btn-warning fw-bold text-dark shadow-sm" onclick="tambahSpk()">➕ TAMBAH BARIS</button>
            </div>
            <div class="card-body bg-white p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 align-middle table-input">
                        <thead class="table-light text-center">
                            <tr>
                                <th width="5%">Seq</th>
                                <th width="15%">Custom (SPK)</th>
                                <th width="8%">Width (Lebar)</th>
                                <th width="10%">Total Lari (m)</th>
                                <th width="8%">GSM DB</th>
                                <th width="8%" class="text-primary">GSM BM</th>
                                <th width="8%">GSM BL</th>
                                <th width="8%" class="text-primary">GSM CM</th>
                                <th width="8%">GSM CL</th>
                                <th width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-spk">
                            <tr>
                                <td><input type="text" name="seq[]" class="form-control form-control-sm" placeholder="10" required></td>
                                <td><input type="text" name="no_spk[]" class="form-control form-control-sm text-uppercase" placeholder="SUN PA" required></td>
                                <td><input type="number" name="lebar_mm[]" class="form-control form-control-sm" placeholder="1750" required></td>
                                <td><input type="number" name="panjang_m[]" class="form-control form-control-sm text-primary" placeholder="1991" required></td>
                                
                                <td><input type="text" name="gsm_db[]" class="form-control form-control-sm" placeholder="-"></td>
                                <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm bg-flute" placeholder="-"></td>
                                <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm" placeholder="-"></td>
                                <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm bg-flute" placeholder="-"></td>
                                <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm" placeholder="-"></td>
                                
                                <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm py-0 btn-hapus" onclick="hapusSpk(this)" disabled>❌</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light small text-muted">
                * Ketik sandi huruf dari monitor (misal: 160WS, 150SD).<br>
                * <b>Trik Tembak Ukuran:</b> Jika ada roll yang beda lebar dengan SPK, ketik pakai garis miring. Contoh: <code>127TF/165</code> (artinya kertas 127TF tapi pakai fisik lebar 165).<br>
                * Kosongkan kolom jika kertas tidak dipakai (misal Single Wall).
            </div>
        </div>
    </form>
</div>

<script>
    function tambahSpk() {
        let tbody = document.getElementById('tbody-spk');
        let tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="seq[]" class="form-control form-control-sm" placeholder="Seq" required></td>
            <td><input type="text" name="no_spk[]" class="form-control form-control-sm text-uppercase" placeholder="SPK" required></td>
            <td><input type="number" name="lebar_mm[]" class="form-control form-control-sm" placeholder="Lebar" required></td>
            <td><input type="number" name="panjang_m[]" class="form-control form-control-sm text-primary" placeholder="Meter" required></td>
            <td><input type="text" name="gsm_db[]" class="form-control form-control-sm" placeholder="-"></td>
            <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm bg-flute" placeholder="-"></td>
            <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm" placeholder="-"></td>
            <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm bg-flute" placeholder="-"></td>
            <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm" placeholder="-"></td>
            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm py-0 btn-hapus" onclick="hapusSpk(this)">❌</button></td>
        `;
        tbody.appendChild(tr);
        cekTombolHapus();
        tr.querySelector('input[name="seq[]"]').focus();
    }
    function hapusSpk(btn) { btn.closest('tr').remove(); cekTombolHapus(); }
    function cekTombolHapus() {
        let btns = document.querySelectorAll('.btn-hapus');
        btns.forEach((btn, index) => btn.disabled = (btns.length === 1));
    }
    function prosesScanAI() {
        let fotoSpek = document.getElementById('foto_spek').files[0];
        let fotoMeter = document.getElementById('foto_meter').files[0];
        let btn = document.getElementById('btn-scan-ai');

        if (!fotoSpek || !fotoMeter) {
            alert("Upload kedua foto monitornya dulu, Mas!");
            return;
        }

        let formData = new FormData();
        formData.append('foto_spek', fotoSpek);
        formData.append('foto_meter', fotoMeter);
        formData.append('_token', '{{ csrf_token() }}');

        // Ubah tampilan tombol jadi loading
        btn.innerHTML = '⏳ AI SEDANG MEMBACA...';
        btn.disabled = true;

        fetch('{{ url("/hitung-spk/sapujagat/scan-ai") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data.length > 0) {
                // Bersihkan tabel saat ini
                document.getElementById('tbody-spk').innerHTML = '';
                
                // Looping hasil AI dan isi ke tabel otomatis
                res.data.forEach(item => {
                    tambahSpkAuto(item);
                });
                alert("✨ Magic selesai! Data monitor berhasil disalin. Silakan cek ulang dan edit jika ada anomali sebelum diproses.");
            } else {
                alert("Gagal membaca gambar: " + (res.message || "Pastikan foto tidak buram/silau."));
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan jaringan atau server.");
        })
        .finally(() => {
            // Kembalikan tombol ke semula
            btn.innerHTML = '✨ BACA DENGAN AI (GROQ)';
            btn.disabled = false;
        });
    }

    // Fungsi helper untuk menyuntikkan data AI ke tabel form
    // Fungsi helper untuk menyuntikkan data AI ke tabel form
    function tambahSpkAuto(data) {
        let tbody = document.getElementById('tbody-spk');
        let tr = document.createElement('tr');
        
        // RADAR PINTAR: Tangkap berbagai kemungkinan nama key dari AI
        let v_seq = data.seq || data.Seq || '';
        let v_spk = data.spk || data.Spk || data.Customer || '';
        let v_lebar = data.lebar || data.Lebar || data.width || data.Width || '';
        let v_meter = data.meter || data.Meter || data.length || data.Length || data.panjang || '';
        let v_db = data.db || data.DB || '';
        let v_bm = data.bm || data.BM || '';
        let v_bl = data.bl || data.BL || '';
        let v_cm = data.cm || data.CM || '';
        let v_cl = data.cl || data.CL || '';

        tr.innerHTML = `
            <td><input type="text" name="seq[]" class="form-control form-control-sm" value="${v_seq}" required></td>
            <td><input type="text" name="no_spk[]" class="form-control form-control-sm text-uppercase" value="${v_spk}" required></td>
            <td><input type="number" name="lebar_mm[]" class="form-control form-control-sm" value="${v_lebar}" required></td>
            <td><input type="number" name="panjang_m[]" class="form-control form-control-sm text-primary" value="${v_meter}" required></td>
            <td><input type="text" name="gsm_db[]" class="form-control form-control-sm" value="${v_db}"></td>
            <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm bg-flute" value="${v_bm}"></td>
            <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm" value="${v_bl}"></td>
            <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm bg-flute" value="${v_cm}"></td>
            <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm" value="${v_cl}"></td>
            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm py-0 btn-hapus" onclick="hapusSpk(this)">❌</button></td>
        `;
        tbody.appendChild(tr);
        cekTombolHapus();
    }

    // ==========================================
    // FUNGSI UNTUK PLAN B (CHATGPT MANUAL)
    // ==========================================

    // 1. Fungsi Copy Prompt ke Clipboard
    function copyPrompt() {
        let copyText = document.getElementById("prompt-chatgpt");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Untuk support HP/Mobile
        navigator.clipboard.writeText(copyText.value);
        
        alert("✅ Prompt berhasil disalin! Silakan buka aplikasi ChatGPT/Gemini, paste teksnya, lalu kirim bersama 2 foto monitor.");
    }

    // 2. Fungsi Mengubah JSON Paste Menjadi Baris Tabel
    function prosesPasteJSON() {
        let jsonString = document.getElementById('input-json-manual').value.trim();
        
        if (!jsonString) {
            alert("⚠️ Paste dulu hasil JSON dari ChatGPT di kotak yang disediakan!");
            return;
        }

        try {
            // PERTAHANAN: ChatGPT kadang masih bandel ngasih markdown ```json meskipun dilarang
            jsonString = jsonString.replace(/```json/g, '').replace(/```/g, '');
            
            // Ubah teks menjadi Objek Javascript
            let res = JSON.parse(jsonString);
            
            if (res.data && Array.isArray(res.data) && res.data.length > 0) {
                // Bersihkan tabel saat ini
                document.getElementById('tbody-spk').innerHTML = '';
                
                // Looping hasil dari ChatGPT dan masukkan ke tabel
                res.data.forEach(item => {
                    tambahSpkAuto(item); // Memanggil fungsi yang sama dengan milik Groq
                });
                
                // Bersihkan kotak teks setelah sukses
                document.getElementById('input-json-manual').value = '';
                alert("✨ Magic sukses! Data dari ChatGPT sudah masuk ke tabel otomatis.");
            } else {
                alert("❌ Format JSON tidak sesuai. Pastikan ada key 'data' berupa array di dalamnya.");
            }
        } catch (e) {
            alert("❌ Gagal membaca JSON! Pastikan yang dipaste benar-benar format kurung kurawal JSON, bukan teks biasa. \n\nError Detail: " + e.message);
        }
    }
</script>
</body>
</html>