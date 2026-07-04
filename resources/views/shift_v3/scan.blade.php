<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner V3 Track Lebar</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; }
        .nav-tabs { background-color: #fff; border-bottom: 2px solid #dee2e6; }
        .nav-tabs .nav-link { width: 50%; text-align: center; font-weight: 800; font-size: 15px; padding: 15px 10px; color: #6c757d; border: none; border-bottom: 4px solid transparent; }
        .nav-tabs .nav-link.active { color: #198754 !important; border-bottom: 4px solid #198754; background-color: transparent; }
        #reader { width: 100%; max-width: 100%; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 2px dashed #198754; }
        
        .posisi-selector, .form-control-lg { border: 2px solid #ced4da; color: #212529; font-weight: 800; font-size: 16px; height: 55px; }
        .posisi-selector:focus, .form-control-lg:focus { border-color: #198754; box-shadow: none; }
        
        .info-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
        .data-value { font-size: 18px; font-weight: 900; color: #212529; }
        .card-roll { border: none; border-radius: 12px; overflow: hidden; }
        .btn-mobile { height: 55px; font-size: 16px; font-weight: 800; }
    </style>
</head>
<body>

<div class="bg-success text-white p-3 text-center shadow-sm d-flex justify-content-between align-items-center">
    <a href="{{ url('/shift-v3') }}" class="btn btn-sm btn-dark fw-bold">⬅ KEMBALI</a>
    <div>
        <div style="font-size: 18px; font-weight: 900; letter-spacing: 1px;">V3 - {{ mb_strtoupper($shift->kepala_shift) }}</div>
        <div class="text-warning fw-bold" style="font-size: 13px;">{{ date('d M Y', strtotime($shift->tanggal)) }}</div>
    </div>
    <div style="width: 70px;"></div>
</div>

<ul class="nav nav-tabs sticky-top shadow-sm" id="mobileTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan-content" type="button" role="tab">📷 SCAN ROLL</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button" role="tab">📋 DAFTAR (<span id="total-roll">{{ count($scans) }}</span>)</button>
    </li>
</ul>

<div class="tab-content container py-4" id="mobileTabContent">
    
    <div class="tab-pane fade show active" id="scan-content" role="tabpanel">
        @if($shift->status == 'selesai')
            <div class="alert alert-danger text-center fw-bold fs-5 shadow-sm rounded-3">SESI INI TELAH DIKUNCI!</div>
        @else
            <div class="card shadow-sm border-0 mb-3 rounded-3 border-start border-4 border-primary">
                <div class="card-body p-3 bg-white rounded-3">
                    <label class="fw-bold text-primary mb-2" style="font-size:14px;">📏 1. WAJIB ISI LEBAR JALAN:</label>
                    <input type="number" id="input_lebar_jalan" class="form-control form-control-lg text-center fw-bold bg-light text-primary border-primary" placeholder="Contoh: 180" required>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3 rounded-3 border-start border-4 border-danger">
                <div class="card-body p-3 bg-white rounded-3">
                    <label class="fw-bold text-danger mb-2" style="font-size:14px;">⬇️ 2. WAJIB PILIH POSISI MESIN:</label>
                    <select id="pilihan_posisi" class="form-select posisi-selector text-center shadow-sm">
                        <option value="">-- PILIH POSISI DISINI --</option>
                        <option value="DB">DB (Double Backer)</option>
                        <option value="BM">BM (Gelombang BF)</option>
                        <option value="BL">BL (Lapisan BF)</option>
                        <option value="CM">CM (Gelombang CF)</option>
                        <option value="CL">CL (Lapisan CF)</option>
                    </select>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 rounded-3 border-start border-4 border-success">
                <div class="card-header bg-dark text-warning text-center p-2 fw-bold border-0" style="font-size:14px;">📷 3. ARAHKAN KAMERA</div>
                <div class="card-body p-2 bg-white"><div id="reader"></div></div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-secondary text-white text-center p-2 fw-bold border-0" style="font-size:13px;">INPUT MANUAL (JIKA LABEL RUSAK)</div>
                <div class="card-body p-3 bg-white">
                    <input type="text" id="manual_no_roll" class="form-control form-control-lg mb-2 text-center text-uppercase" placeholder="KODE ROLL...">
                    <button class="btn btn-success w-100 btn-mobile shadow-sm" onclick="submitManual()">KIRIM MANUAL</button>
                </div>
            </div>
        @endif
    </div>

    <div class="tab-pane fade" id="list-content" role="tabpanel">
        
        <div class="d-flex flex-column gap-2 mb-3">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-dark font-weight-bold">🔢</span>
                <input type="text" id="cari_roll" class="form-control form-control-lg border-dark fw-bold text-uppercase" placeholder="CARI KODE ROLL..." onkeyup="filterDanCariRoll()" style="height:55px; font-size: 16px;">
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <select id="filter_posisi" class="form-select posisi-selector fw-bold shadow-sm border-dark" onchange="filterDanCariRoll()" style="height:50px; font-size:14px;">
                        <option value="ALL">🔍 SEMUA POSISI</option>
                        <option value="DB">Hanya DB</option><option value="BM">Hanya BM</option><option value="BL">Hanya BL</option>
                        <option value="CM">Hanya CM</option><option value="CL">Hanya CL</option>
                    </select>
                </div>
                <div class="col-6">
                    <a href="{{ url('/shift-v3/print/'.$shift->id) }}" target="_blank" class="btn btn-dark w-100 shadow-sm d-flex align-items-center justify-content-center fw-bold" style="height: 50px; font-size: 13px;">
                        🖨️ PRINT LAPORAN
                    </a>
                </div>
            </div>
        </div>

        <div id="roll-list-container">
            @forelse($scans as $t)
            <div class="card card-roll shadow-sm mb-3 roll-item {{ $t->status != 'diambil' ? 'opacity-50 bg-light' : '' }}" 
                 data-posisi="{{ explode(' ', trim($t->posisi_mesin))[0] }}" 
                 data-noroll="{{ strtolower($t->no_roll) }}">
                
                <div class="{{ $t->status != 'diambil' ? 'bg-secondary text-white' : 'bg-success text-white' }} text-center p-2">
                    <div class="info-label text-white opacity-75">NOMOR ROLL</div>
                    <div style="font-size: 26px; font-weight: 900; letter-spacing: 1px;">{{ $t->no_roll }}</div>
                </div>
                
                <div class="card-body p-3 bg-white">
                    <div class="row text-center mb-3 mx-0 g-2">
                        <div class="col-6">
                            <div class="p-2 border rounded bg-light">
                                <div class="info-label">POSISI MESIN</div>
                                <div class="data-value text-primary">{{ $t->posisi_mesin }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded bg-light border-primary">
                                <div class="info-label">LEBAR JALAN</div>
                                <div class="data-value text-primary">{{ $t->lebar_jalan }} CM</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                        <span class="badge bg-danger px-3 py-2 fs-6">Awal: {{ $t->sisa_kilo_awal }} Kg</span>
                        <span class="badge bg-dark px-2 py-2">Jns: {{ $t->jenis ?? '-' }}</span>
                        <span class="badge bg-secondary px-2 py-2">Lbr Fisik: {{ $t->master_lebar ?? '-' }}</span>
                    </div>

                    @php $currentPos = explode(' ', trim($t->posisi_mesin))[0] ?? ''; @endphp

                    @if($t->status == 'diambil' && $shift->status == 'aktif')
                        <div class="mt-2">
                            <form action="{{ url('/shift-v3/kembali-roll/'.$t->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="posisi_mesin" value="{{ $currentPos }}">
                                <label class="fw-bold text-success mb-1" style="font-size:12px;">⬇️ INPUT SISA KG JIKA ROLL KEMBALI:</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-12">
                                        <input type="number" step="0.01" name="sisa_kilo_akhir" class="form-control text-center fw-bold border-success shadow-sm text-danger" placeholder="MASUKKAN SISA KG..." style="height: 55px; font-size: 16px;" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" onclick="if(confirm('YAKIN HAPUS ROLL INI?')) { document.getElementById('hapus-roll-{{$t->id}}').submit(); }" class="btn btn-outline-danger fw-bold shadow-sm" style="height: 50px; width: 60px;">❌</button>
                                    <button type="submit" class="btn btn-success fw-bold flex-grow-1 shadow-sm" style="height: 50px;">💾 ROLL KEMBALI</button>
                                </div>
                            </form>
                            
                            <form id="hapus-roll-{{$t->id}}" action="{{ url('/shift-v3/batal-roll/'.$t->id) }}" method="POST" class="d-none">@csrf</form>

                            <hr class="my-3" style="border-top: 2px dashed #dee2e6;">

                            <form action="{{ url('/shift-v3/ubah-posisi/'.$t->id) }}" method="POST" class="mb-3">
                                @csrf
                                <label class="fw-bold text-primary mb-1" style="font-size:12px;">🔄 UBAH POSISI MESIN:</label>
                                <div class="input-group shadow-sm">
                                    <select name="posisi_mesin" class="form-select text-center fw-bold border-primary shadow-sm" style="height: 50px; font-size: 14px;" required>
                                        <option value="DB" {{ $currentPos == 'DB' ? 'selected' : '' }}>DB</option>
                                        <option value="BM" {{ $currentPos == 'BM' ? 'selected' : '' }}>BM</option>
                                        <option value="BL" {{ $currentPos == 'BL' ? 'selected' : '' }}>BL</option>
                                        <option value="CM" {{ $currentPos == 'CM' ? 'selected' : '' }}>CM</option>
                                        <option value="CL" {{ $currentPos == 'CL' ? 'selected' : '' }}>CL</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary fw-bold px-3">SIMPAN</button>
                                </div>
                            </form>
                        </div>

                    @else
                        <div class="d-flex justify-content-between align-items-center bg-light border border-success rounded p-2 shadow-sm" id="view-selesai-{{ $t->id }}">
                            <div class="text-success fw-bold" style="font-size: 16px;">✅ SISA: {{ $t->sisa_kilo_akhir }} Kg</div>
                            @if($shift->status == 'aktif')
                                <button type="button" class="btn btn-sm btn-outline-dark fw-bold px-3 py-2" onclick="bukaRevisi({{ $t->id }})">✏️ UBAH</button>
                            @endif
                        </div>

                        <div id="form-revisi-{{ $t->id }}" class="mt-2 d-none">
                            <form action="{{ url('/shift-v3/kembali-roll/'.$t->id) }}" method="POST" class="mb-2">
                                @csrf
                                <div class="row g-2 mb-2">
                                    <div class="col-5">
                                        <select name="posisi_mesin" class="form-select text-center fw-bold border-warning shadow-sm" style="height: 50px; font-size: 15px;" required>
                                            <option value="DB" {{ $currentPos == 'DB' ? 'selected' : '' }}>DB</option>
                                            <option value="BM" {{ $currentPos == 'BM' ? 'selected' : '' }}>BM</option>
                                            <option value="BL" {{ $currentPos == 'BL' ? 'selected' : '' }}>BL</option>
                                            <option value="CM" {{ $currentPos == 'CM' ? 'selected' : '' }}>CM</option>
                                            <option value="CL" {{ $currentPos == 'CL' ? 'selected' : '' }}>CL</option>
                                        </select>
                                    </div>
                                    <div class="col-7">
                                        <input type="number" step="0.01" name="sisa_kilo_akhir" class="form-control text-center fw-bold border-warning shadow-sm" value="{{ $t->sisa_kilo_akhir }}" style="height: 50px; font-size: 16px;" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 fw-bold text-dark shadow-sm mb-2" style="height: 50px;">💾 UPDATE DATA</button>
                            </form>
                            
                            <div class="d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-secondary fw-bold flex-grow-1 shadow-sm" onclick="tutupRevisi({{ $t->id }})">BATAL</button>
                                <form action="{{ url('/shift-v3/batal-roll/'.$t->id) }}" method="POST" onsubmit="return confirm('HAPUS ROLL INI?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger fw-bold px-3 shadow-sm">🗑️ HAPUS</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center p-5 fw-bold text-muted" style="font-size: 16px;">BELUM ADA ROLL DI-SCAN.</div>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const idShift = "{{ $shift->id }}";

    function filterDanCariRoll() {
        let filterPosisi = document.getElementById('filter_posisi').value;
        let kataKunci = document.getElementById('cari_roll').value.toLowerCase().trim();
        let rolls = document.querySelectorAll('.roll-item');
        let count = 0;
        
        rolls.forEach(function(roll) {
            let posisi = roll.getAttribute('data-posisi');
            let noRoll = roll.getAttribute('data-noroll');
            
            let cocokPosisi = (filterPosisi === 'ALL' || posisi.startsWith(filterPosisi));
            let cocokNoRoll = (kataKunci === '' || noRoll.includes(kataKunci));
            
            if(cocokPosisi && cocokNoRoll) {
                roll.style.display = 'block';
                count++;
            } else {
                roll.style.display = 'none';
            }
        });
        document.getElementById('total-roll').innerText = count;
    }

    function kirimDataRoll(noRoll, metodeInput) {
        let lebarJalan = document.getElementById('input_lebar_jalan').value;
        let posisiMesinSelect = document.getElementById('pilihan_posisi');
        let posisiMesin = posisiMesinSelect.value;
        let namaPosisi = posisiMesinSelect.options[posisiMesinSelect.selectedIndex].text;
        
        // Validasi 1: Lebar Jalan Wajib Diisi!
        if(!lebarJalan) {
            Swal.fire({
                icon: 'warning', title: '<h1 style="color:#0d6efd; margin:0;">STOP!</h1>',
                html: '<b>Anda Belum Mengisi<br><span style="color:#0d6efd; font-size:24px;">LEBAR JALAN</span></b>',
                confirmButtonText: 'ISI DULU', confirmButtonColor: '#0d6efd', allowOutsideClick: false
            }).then(() => { if(metodeInput === 'scan') startScanner(); });
            return;
        }

        // Validasi 2: Posisi Mesin Wajib Diisi!
        if(!posisiMesin) {
            Swal.fire({
                icon: 'warning', title: '<h1 style="color:#dc3545; margin:0;">STOP!</h1>',
                html: '<b>Anda Belum Memilih<br><span style="color:#dc3545; font-size:24px;">POSISI MESIN</span></b>',
                confirmButtonText: 'PILIH DULU', confirmButtonColor: '#dc3545', allowOutsideClick: false
            }).then(() => { if(metodeInput === 'scan') startScanner(); });
            return;
        }

        // KIRIM KE API V3
        fetch(`/shift-v3/scan-ajax/${idShift}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ no_roll: noRoll, metode: metodeInput, posisi_mesin: posisiMesin, lebar_jalan: lebarJalan })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-84.wav').play();
                Swal.fire({
                    icon: 'success', title: '<h1 style="color:#198754; margin:0;">BERHASIL!</h1>',
                    html: `<b>NO ROLL:</b><br><span style="font-size: 28px; background:#ffc107; padding:5px 10px; border-radius:5px;">${noRoll}</span>
                           <br><br><b>LEBAR: <span style="color:#0d6efd;">${lebarJalan} CM</span></b> | <b>POSISI: <span style="color:#dc3545;">${posisiMesin}</span></b>`,
                    confirmButtonText: 'LANJUT SCAN', confirmButtonColor: '#198754', allowOutsideClick: false
                }).then((result) => { if (result.isConfirmed) { window.location.reload(); } });
            } else {
                Swal.fire({
                    icon: 'error', title: '<h1 style="color:#dc3545; margin:0;">GAGAL!</h1>',
                    html: `<b>${data.message}</b>`, confirmButtonText: 'COBA LAGI', confirmButtonColor: '#dc3545', allowOutsideClick: false
                }).then(() => { if(metodeInput === 'scan') startScanner(); });
            }
        }).catch(err => { 
            Swal.fire('Error', 'Koneksi Terputus!', 'error').then(() => { if(metodeInput === 'scan') startScanner(); });
        });
    }

    let html5QrcodeScanner;
    function onScanSuccess(decodedText) {
        html5QrcodeScanner.clear();
        kirimDataRoll(decodedText, 'scan');
    }

    function startScanner() {
        if(document.getElementById('reader')) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                fps: 10, rememberLastUsedCamera: true, qrbox: { width: 300, height: 120 },
                videoConstraints: { facingMode: "environment", width: { ideal: 1280 }, height: { ideal: 720 } },
                formatsToSupport: [ Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.CODE_39, Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.QR_CODE ]
            });
            html5QrcodeScanner.render(onScanSuccess);
        }
    }
    window.addEventListener('DOMContentLoaded', startScanner);

    function submitManual() {
        let noRoll = document.getElementById('manual_no_roll').value.trim();
        if(!noRoll) { Swal.fire('DATA KOSONG!', 'Isi Kode Roll!', 'warning'); return; }
        kirimDataRoll(noRoll, 'manual');
    }

    function bukaRevisi(id) {
        document.getElementById('view-selesai-' + id).classList.add('d-none');
        document.getElementById('form-revisi-' + id).classList.remove('d-none');
    }
    function tutupRevisi(id) {
        document.getElementById('form-revisi-' + id).classList.add('d-none');
        document.getElementById('view-selesai-' + id).classList.remove('d-none');
    }
</script>
</body>
</html>