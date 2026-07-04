<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencocokan AI V2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/') }}" class="btn btn-outline-dark fw-bold">⬅️ KEMBALI</a>
        <h3 class="fw-bold mb-0" style="color: #4b0082;">🧠 V2: PENCOCOKAN AI (GLOBAL POOL)</h3>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-info h-100" style="border-width: 2px;">
                <div class="card-header bg-info text-white fw-bold">🤖 OPSI 1: UPLOAD FOTO MONITOR</div>
                <div class="card-body bg-white d-flex flex-column justify-content-between">
                    <form id="form-ai" class="row g-2">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">1. Foto Spesifikasi Kertas (Kiri)</label>
                            <input type="file" id="foto_spek" class="form-control form-control-sm" accept="image/*" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">2. Foto Meter Lari (Kanan)</label>
                            <input type="file" id="foto_meter" class="form-control form-control-sm" accept="image/*" required>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="button" class="btn btn-info btn-sm fw-bold w-100 text-white shadow-sm" onclick="prosesAI()">⚡ EKSTRAK VIA FOTO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-secondary h-100" style="border-width: 2px; border-style: dashed;">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">📋 OPSI 2: PASTE JSON AI LUAR</span>
                    <button type="button" class="btn btn-sm btn-light fw-bold text-dark py-0" onclick="copyPromptAI()">
                        📄 COPY PROMPT AI
                    </button>
                </div>
                <div class="card-body bg-white d-flex flex-column justify-content-between">
                    <div class="w-100">
                        <label class="small fw-bold text-muted">Tempelkan (Paste) Teks JSON Hasil Ekstraksi Di Sini:</label>
                        <textarea id="json_paste" class="form-control form-control-sm fw-bold" rows="3" style="font-family: monospace; font-size: 0.75rem;" placeholder='{ "data": [ {"seq": "1", "spk": "29035 / ROMAN", "lebar": "1800", "meter": "1516", "db": "160KE", "bm": "150MC", "bl": "160BB"} ] }'></textarea>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-secondary btn-sm fw-bold w-100 shadow-sm" onclick="isiViaJsonPaste()">🔌 INJECT DATA JSON</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ url('/versi2/pencocokan/store') }}" method="POST">
        @csrf
        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">DATA SPK MONITOR (TEORI)</span>
                <select name="shift_v2_id" class="form-select form-select-sm fw-bold" style="width: auto;" required>
                    <option value="">-- Pilih Shift V2 Target --</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}">{{ $s->shift }} ({{ date('d M', strtotime($s->tanggal)) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 text-center align-middle" style="font-size: 0.85rem;">
                        <thead class="table-light align-middle">
                            <tr>
                                <th rowspan="2" width="5%">Seq</th>
                                <th rowspan="2" width="15%">No SPK / Cust</th>
                                <th rowspan="2" width="8%">Lebar (mm)</th>
                                <th rowspan="2" width="10%">Total Meter</th>
                                <th colspan="5">Kertas (Tembak Ukuran pakai '/')</th>
                                <th rowspan="2" width="5%">Aksi</th>
                            </tr>
                            <tr>
                                <th>DB</th><th>BM</th><th>BL</th><th>CM</th><th>CL</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-spk">
                            <tr>
                                <td><input type="text" name="seq[]" class="form-control form-control-sm"></td>
                                <td><input type="text" name="no_spk[]" class="form-control form-control-sm text-uppercase" required></td>
                                <td><input type="number" name="lebar_mm[]" class="form-control form-control-sm" required></td>
                                <td><input type="number" name="panjang_m[]" class="form-control form-control-sm text-primary" required></td>
                                <td><input type="text" name="gsm_db[]" class="form-control form-control-sm text-uppercase"></td>
                                <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm text-uppercase bg-warning bg-opacity-25"></td>
                                <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm text-uppercase"></td>
                                <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm text-uppercase bg-warning bg-opacity-25"></td>
                                <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm text-uppercase"></td>
                                <td><button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="this.closest('tr').remove()">❌</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light text-center">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-4" onclick="tambahBaris()">➕ TAMBAH BARIS MANUAL</button>
            </div>
        </div>

        <div class="text-end mb-5">
            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm px-5">⚙️ JALANKAN MAK COMBLANG V2</button>
        </div>
    </form>
</div>

<script>
    function tambahBaris() {
        let tr = document.querySelector('#tbody-spk tr').cloneNode(true);
        tr.querySelectorAll('input').forEach(i => i.value = '');
        document.getElementById('tbody-spk').appendChild(tr);
    }

    // FUNGSIONALITAS OPSI 2: INJECT VIA PASTE JSON TEKS
    function isiViaJsonPaste() {
        let rawText = document.getElementById('json_paste').value.trim();
        if(!rawText) return alert("Kolom Teks JSON masih kosong, silakan paste dulu!");

        try {
            let parsed = JSON.parse(rawText);
            // Toleransi struktur: bisa langsung berupa array [] atau dibungkus object { data: [] }
            let itemArray = parsed.data || parsed; 

            if(!Array.isArray(itemArray)) {
                return alert("Format JSON salah! Data SPK harus berbentuk Array / Daftar Berderet.");
            }

            // Bersihkan isi tabel lama sebelum dimasuki penghuni baru
            document.getElementById('tbody-spk').innerHTML = '';

            itemArray.forEach(d => {
                // Mapping toleran: membaca variasi key huruf besar/kecil dari AI luar
                let seq = d.seq || '';
                let spk = d.spk || d.no_spk || '';
                let lebar = d.lebar || d.lebar_mm || '';
                let meter = d.meter || d.panjang_m || '';
                let db = d.db || d.gsm_db || '';
                let bm = d.bm || d.gsm_bm || '';
                let bl = d.bl || d.gsm_bl || '';
                let cm = d.cm || d.gsm_cm || '';
                let cl = d.cl || d.gsm_cl || '';

                let trHtml = `<tr>
                    <td><input type="text" name="seq[]" class="form-control form-control-sm" value="${seq}"></td>
                    <td><input type="text" name="no_spk[]" class="form-control form-control-sm text-uppercase" value="${spk}" required></td>
                    <td><input type="number" name="lebar_mm[]" class="form-control form-control-sm" value="${lebar}" required></td>
                    <td><input type="number" name="panjang_m[]" class="form-control form-control-sm text-primary" value="${meter}" required></td>
                    <td><input type="text" name="gsm_db[]" class="form-control form-control-sm text-uppercase" value="${db}"></td>
                    <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm text-uppercase bg-warning bg-opacity-25" value="${bm}"></td>
                    <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm text-uppercase" value="${bl}"></td>
                    <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm text-uppercase bg-warning bg-opacity-25" value="${cm}"></td>
                    <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm text-uppercase" value="${cl}"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="this.closest('tr').remove()">❌</button></td>
                </tr>`;
                document.getElementById('tbody-spk').insertAdjacentHTML('beforeend', trHtml);
            });

            alert("⚡ Sukses! " + itemArray.length + " data SPK berhasil di-inject ke tabel!");
        } catch (error) {
            alert("❌ Gagal membaca susunan teks! Pastikan teks yang di-paste adalah JSON valid.\nError: " + error.message);
        }
    }

    // FUNGSIONALITAS OPSI 1: UPLOAD FOTO MONITOR
    async function prosesAI() {
        let fs = document.getElementById('foto_spek').files[0];
        let fm = document.getElementById('foto_meter').files[0];
        if(!fs || !fm) return alert("Upload kedua foto dulu!");

        let fd = new FormData();
        fd.append('foto_spek', fs);
        fd.append('foto_meter', fm);
        fd.append('_token', '{{ csrf_token() }}');

        try {
            document.body.style.cursor = 'wait';
            let res = await fetch("{{ url('/versi2/pencocokan/scan-ai') }}", { method: 'POST', body: fd });
            let json = await res.json();
            
            if(json.success) {
                document.getElementById('tbody-spk').innerHTML = '';
                json.data.forEach(d => {
                    let tr = `<tr>
                        <td><input type="text" name="seq[]" class="form-control form-control-sm" value="${d.seq||''}"></td>
                        <td><input type="text" name="no_spk[]" class="form-control form-control-sm text-uppercase" value="${d.spk||''}" required></td>
                        <td><input type="number" name="lebar_mm[]" class="form-control form-control-sm" value="${d.lebar||''}" required></td>
                        <td><input type="number" name="panjang_m[]" class="form-control form-control-sm text-primary" value="${d.meter||''}" required></td>
                        <td><input type="text" name="gsm_db[]" class="form-control form-control-sm text-uppercase" value="${d.db||''}"></td>
                        <td><input type="text" name="gsm_bm[]" class="form-control form-control-sm text-uppercase bg-warning bg-opacity-25" value="${d.bm||''}"></td>
                        <td><input type="text" name="gsm_bl[]" class="form-control form-control-sm text-uppercase" value="${d.bl||''}"></td>
                        <td><input type="text" name="gsm_cm[]" class="form-control form-control-sm text-uppercase bg-warning bg-opacity-25" value="${d.cm||''}"></td>
                        <td><input type="text" name="gsm_cl[]" class="form-control form-control-sm text-uppercase" value="${d.cl||''}"></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="this.closest('tr').remove()">❌</button></td>
                    </tr>`;
                    document.getElementById('tbody-spk').insertAdjacentHTML('beforeend', tr);
                });
                alert("Ekstraksi AI Berhasil!");
            } else { alert("Gagal: " + json.message); }
        } catch(e) { alert("Error: " + e); } finally { document.body.style.cursor = 'default'; }
    }
    // FUNGSIONALITAS COPY PROMPT AI LUAR
    function copyPromptAI() {
            const promptText = `Kamu adalah sistem ekstraksi OCR untuk monitor mesin corrugator pabrik kardus. 
    Aku memberikan foto dari layar monitor. 
    Foto berisi spesifikasi: Seq, ID SPK, Customer, Width (Lebar), dan deretan kertas (DB, BM, BL, CM, CL) serta target jalan: Length (Meter).

    ATURAN WAJIB (HARUS DIIKUTI 100%):
    1. GABUNGKAN data berdasarkan nomor 'Seq' yang sama.
    2. FORMAT SPK: Gabungkan ID SPK dan Customer dengan separator garis miring ' / '. (Contoh: ID '12345' dan Customer 'MERCU' -> '12345 / MERCU'). Masukkan ke key 'spk'.
    3. PEMISAHAN KERTAS (SANGAT PENTING): JANGAN PERNAH menggabungkan semua kertas ke dalam satu key 'db'! Pisahkan kertas tersebut sesuai urutannya ke key 'db', 'bm', 'bl', 'cm', 'cl'. Kosongkan string ("") jika tidak ada kertas di posisi tersebut.
    4. KEMBALIKAN HANYA JSON murni dengan key 'data' berupa array of objects. Dilarang memberikan teks pengantar atau penutup Markdown (seperti \`\`\`json).

    Contoh Format Output Yang Benar:
    {
    "data": [
        {"seq": "10", "spk": "12345 / KARTA", "lebar": "1650", "meter": "1500", "db": "160WS", "bm": "150SD", "bl": "160KS", "cm": "", "cl": ""}
    ]
    }`;

            // Proses Copy ke Clipboard
            navigator.clipboard.writeText(promptText).then(() => {
                alert("✅ Prompt Sakti berhasil disalin!\n\nSilakan Paste di ChatGPT / Claude / Bot Telegram, lalu kirimkan fotonya.");
            }).catch(err => {
                alert("❌ Gagal menyalin teks: " + err);
            });
        }
</script>
</body>
</html>