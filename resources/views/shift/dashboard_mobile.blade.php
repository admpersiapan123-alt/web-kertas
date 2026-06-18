<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Forklift Corrugator</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f6f9; 
        }
        
        /* Mobile Navbar Tabs */
        .nav-tabs {
            background-color: #fff;
            border-bottom: 2px solid #dee2e6;
        }
        .nav-tabs .nav-link { 
            width: 50%; 
            text-align: center; 
            font-weight: 800; 
            font-size: 15px; 
            padding: 15px 10px; 
            color: #6c757d; 
            border: none;
            border-bottom: 4px solid transparent;
        }
        .nav-tabs .nav-link.active { 
            color: #0d6efd !important; 
            border-bottom: 4px solid #0d6efd;
            background-color: transparent;
        }

        /* Scanner Target */
        #reader { 
            width: 100%; 
            max-width: 100%; 
            margin: 0 auto; 
            border-radius: 8px; 
            overflow: hidden; 
            border: 2px dashed #0d6efd;
        }

        /* Mobile Inputs & Selectors */
        .posisi-selector, .form-control-lg { 
            border: 2px solid #ced4da; 
            color: #212529; 
            font-weight: 800; 
            font-size: 16px; 
            height: 55px;
        }
        .posisi-selector:focus, .form-control-lg:focus {
            border-color: #0d6efd;
            box-shadow: none;
        }

        /* Cards / List Items */
        .info-label { 
            font-size: 11px; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            color: #6c757d; 
        }
        .data-value { 
            font-size: 18px; 
            font-weight: 900; 
            color: #212529; 
        }
        .card-roll { 
            border: none; 
            border-radius: 12px; 
            overflow: hidden; 
        }
        .btn-mobile {
            height: 55px; 
            font-size: 16px; 
            font-weight: 800;
        }
    </style>
</head>
<body>

<div class="bg-dark text-white p-3 text-center shadow-sm">
    <div style="font-size: 18px; font-weight: 900; letter-spacing: 1px;">{{ mb_strtoupper($shift->kepala_shift) }}</div>
    <div class="text-warning fw-bold" style="font-size: 13px;">{{ date('d M Y', strtotime($shift->tanggal)) }}</div>
</div>

<ul class="nav nav-tabs sticky-top shadow-sm" id="mobileTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan-content" type="button" role="tab">📷 SCAN ROLL</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button" role="tab">📋 DAFTAR (<span id="total-roll">{{ count($transaksi) }}</span>)</button>
    </li>
</ul>

<div class="tab-content container py-4" id="mobileTabContent">
    
    <div class="tab-pane fade show active" id="scan-content" role="tabpanel">
        @if($shift->status == 'selesai')
            <div class="alert alert-danger text-center fw-bold fs-5 shadow-sm rounded-3">SESI INI TELAH DIKUNCI!</div>
        @else
            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-body p-3 bg-white rounded-3">
                    <label class="fw-bold text-danger mb-2" style="font-size:14px;">⬇️ 1. WAJIB PILIH POSISI MESIN:</label>
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

            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-header bg-dark text-warning text-center p-2 fw-bold border-0" style="font-size:14px;">⬇️ 2. ARAHKAN KAMERA</div>
                <div class="card-body p-2 bg-white"><div id="reader"></div></div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-secondary text-white text-center p-2 fw-bold border-0" style="font-size:13px;">INPUT MANUAL (JIKA LABEL RUSAK)</div>
                <div class="card-body p-3 bg-white">
                    <input type="text" id="manual_no_roll" class="form-control form-control-lg mb-2 text-center" placeholder="KODE ROLL...">
                    <!-- <input type="text" id="manual_keterangan" class="form-control form-control-lg mb-3 text-center" placeholder="ALASAN RUSAK..."> -->
                    <button class="btn btn-primary w-100 btn-mobile shadow-sm" onclick="submitManual()">KIRIM MANUAL</button>
                </div>
            </div>
        @endif
    </div>

    <div class="tab-pane fade" id="list-content" role="tabpanel">
    
    <div class="d-flex flex-column gap-2 mb-3">
        
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-dark font-weight-bold">🔢</span>
            <input type="text" id="cari_roll" class="form-control form-control-lg border-dark fw-bold" placeholder="KETIK NO ROLL DI SINI..." onkeyup="filterDanCariRoll()" style="height:55px; font-size: 16px;">
        </div>

        <div class="row g-2">
            <div class="col-6">
                <select id="filter_posisi" class="form-select posisi-selector fw-bold shadow-sm border-dark" onchange="filterDanCariRoll()" style="height:50px; font-size:14px;">
                    <option value="ALL">🔍 SEMUA POSISI</option>
                    <option value="DB">Hanya DB</option>
                    <option value="BM">Hanya BM</option>
                    <option value="BL">Hanya BL</option>
                    <option value="CM">Hanya CM</option>
                    <option value="CL">Hanya CL</option>
                </select>
            </div>
            <div class="col-6">
                <a href="{{ url('/shift/'.$shift->id.'/print') }}" target="_blank" class="btn btn-dark w-100 shadow-sm d-flex align-items-center justify-content-center fw-bold" style="height: 50px; font-size: 13px; background-color: #212529;">
                    🖨️ PRINT FORM
                </a>
            </div>
        </div>
    </div>

    <div id="roll-list-container">
        @forelse($transaksi as $t)
        <div class="card card-roll shadow-sm mb-3 roll-item {{ $t->status != 'diambil' ? 'opacity-50 bg-light' : '' }}" 
             data-posisi="{{ explode(' ', trim($t->posisi_mesin))[0] }}" 
             data-noroll="{{ strtolower($t->no_roll) }}">
            
            <div class="{{ $t->status != 'diambil' ? 'bg-secondary text-white' : 'bg-warning text-dark' }} text-center p-2">
                <div class="info-label {{ $t->status != 'diambil' ? 'text-white' : 'text-dark' }} opacity-75">NOMOR ROLL</div>
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
                        <div class="p-2 border rounded bg-light">
                            <div class="info-label">KILO AWAL</div>
                            <div class="data-value text-danger">{{ $t->sisa_kilo_awal }} Kg</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                    <span class="badge bg-dark px-3 py-2">Jns: {{ $t->masterKertas->jenis ?? '-' }}</span>
                    <span class="badge bg-primary px-3 py-2">GSM: {{ $t->masterKertas->gsm ?? '-' }}</span>
                    <span class="badge bg-success px-3 py-2">Lbr: {{ $t->masterKertas->lebar ?? '-' }}</span>
                </div>

                @if($t->status == 'diambil' && $shift->status == 'aktif')
                    <div class="d-flex gap-2 mt-2">
                        <form action="{{ url('/shift/batal-roll/'.$t->id) }}" method="POST" onsubmit="return confirm('YAKIN HAPUS ROLL INI DARI DAFTAR?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger fw-bold shadow-sm" style="height: 55px; width: 60px;">❌</button>
                        </form>
                        
                        <form action="{{ url('/shift/kembali-roll/'.$t->id) }}" method="POST" class="flex-grow-1">
                            @csrf
                            <div class="input-group shadow-sm">
                                <input type="number" step="0.01" name="sisa_kilo_akhir" class="form-control text-center fw-bold border-secondary" placeholder="SISA KG" style="height: 55px; font-size: 16px;" required>
                                <button type="submit" class="btn btn-success fw-bold px-3" style="height: 55px;">SIMPAN</button>
                            </div>
                        </form>
                    </div>

                @else
                    <div class="d-flex justify-content-between align-items-center bg-light border border-success rounded p-2 shadow-sm" id="view-selesai-{{ $t->id }}">
                        <div class="text-success fw-bold" style="font-size: 16px;">
                            ✅ SISA: {{ $t->sisa_kilo_akhir }} Kg
                        </div>
                        
                        @if($shift->status == 'aktif')
                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold px-3 py-2" onclick="bukaRevisi({{ $t->id }})">
                            ✏️ UBAH
                        </button>
                        @endif
                    </div>

                    <div id="form-revisi-{{ $t->id }}" class="mt-2 d-none">
                        <form action="{{ url('/shift/kembali-roll/'.$t->id) }}" method="POST" class="d-flex gap-2 mb-2">
                            @csrf
                            <input type="number" step="0.01" name="sisa_kilo_akhir" class="form-control text-center fw-bold border-warning" value="{{ $t->sisa_kilo_akhir }}" style="height: 50px; font-size: 16px;" required>
                            <button type="submit" class="btn btn-warning fw-bold px-3 text-dark" style="height: 50px;">UPDATE</button>
                        </form>
                        
                        <div class="d-flex justify-content-between gap-2">
                            <button type="button" class="btn btn-secondary fw-bold flex-grow-1" onclick="tutupRevisi({{ $t->id }})">BATAL UBAH</button>
                            
                            <form action="{{ url('/shift/batal-roll/'.$t->id) }}" method="POST" onsubmit="return confirm('HAPUS ROLL INI KARENA SALAH INPUT KODE?');">
                                @csrf
                                <button type="submit" class="btn btn-danger fw-bold px-3">🗑️ HAPUS ROLL</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center p-5 fw-bold text-muted" style="font-size: 16px;">
            BELUM ADA ROLL DI-SCAN.
        </div>
        @endforelse
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const idShift = "{{ $shift->id }}";

    // Fungsi Filter
    function filterDanCariRoll() {
        let filterPosisi = document.getElementById('filter_posisi').value;
        let kataKunci = document.getElementById('cari_roll').value.toLowerCase().trim();
        let rolls = document.querySelectorAll('.roll-item');
        let count = 0;
        
        rolls.forEach(function(roll) {
            let posisi = roll.getAttribute('data-posisi');
            let noRoll = roll.getAttribute('data-noroll');
            
            // Cek apakah posisi mesin cocok AND nomor roll mengandung kata kunci yang diketik
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

    // Fungsi Kirim Data Scanner
    function kirimDataRoll(noRoll, metodeInput, keteranganText = '') {
        let posisiMesinSelect = document.getElementById('pilihan_posisi');
        let posisiMesin = posisiMesinSelect.value;
        let namaPosisi = posisiMesinSelect.options[posisiMesinSelect.selectedIndex].text;
        
        if(!posisiMesin) {
            Swal.fire({
                icon: 'warning',
                title: '<h1 style="font-size:32px; font-weight:900; color:#dc3545; margin:0;">STOP!</h1>',
                html: '<div style="font-size: 18px; font-weight:bold; line-height:1.4;">Anda Belum Memilih<br><span style="color:#0d6efd; font-size:24px;">POSISI MESIN</span></div>',
                confirmButtonText: '<span style="font-size: 18px; font-weight:bold;">PILIH DULU</span>',
                confirmButtonColor: '#dc3545',
                allowOutsideClick: false
            }).then(() => { if(metodeInput === 'scan') startScanner(); });
            return;
        }

        fetch(`/api/shift/${idShift}/ambil-roll`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ no_roll: noRoll, metode: metodeInput, keterangan: keteranganText, posisi_mesin: posisiMesin })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-84.wav').play();
                Swal.fire({
                    icon: 'success',
                    title: '<h1 style="font-size: 32px; font-weight:900; color:#198754; margin:0;">BERHASIL!</h1>',
                    html: `
                        <div style="font-size: 18px; font-weight:bold; margin-top:10px;">NO ROLL:<br>
                            <span style="font-size: 28px; color:#000; background:#ffc107; padding:5px 10px; border-radius:5px; display:inline-block; margin-top:5px;">${noRoll}</span>
                        </div>
                        <div style="font-size: 16px; font-weight:bold; margin-top:20px;">MASUK KE MESIN:<br>
                            <span style="font-size: 22px; color:#0d6efd;">${namaPosisi}</span>
                        </div>
                    `,
                    confirmButtonText: '<span style="font-size: 18px; font-weight:bold;">LANJUT SCAN</span>',
                    confirmButtonColor: '#198754',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) { window.location.reload(); }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '<h1 style="font-size: 32px; font-weight:900; color:#dc3545; margin:0;">GAGAL!</h1>',
                    html: `<div style="font-size: 18px; font-weight:bold; line-height:1.3;">${data.message}</div>`,
                    confirmButtonText: '<span style="font-size: 18px; font-weight:bold;">COBA LAGI</span>',
                    confirmButtonColor: '#dc3545',
                    allowOutsideClick: false
                }).then(() => { if(metodeInput === 'scan') startScanner(); });
            }
        }).catch(err => { 
            Swal.fire('Error', 'Koneksi Terputus!', 'error').then(() => { if(metodeInput === 'scan') startScanner(); });
        });
    }

    // Scanner Logic
    let html5QrcodeScanner;
    function onScanSuccess(decodedText) {
        html5QrcodeScanner.clear();
        kirimDataRoll(decodedText, 'scan');
    }

    function startScanner() {
        if(document.getElementById('reader')) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                fps: 10,
                rememberLastUsedCamera: true,
                
                // 1. Buat kotak scan memanjang (Landscape) khusus untuk Barcode 1D.
                // Ini membantu algoritma membuang background yang tidak perlu dan fokus ke garis barcode.
                qrbox: { width: 300, height: 120 },
                
                // 2. PAKSA iOS MENGGUNAKAN RESOLUSI TINGGI
                // Tanpa ini, iOS sering memberi resolusi buram ke browser
                videoConstraints: {
                    facingMode: "environment", // Paksa kamera belakang
                    width: { ideal: 1280 },    // Resolusi HD minimal
                    height: { ideal: 720 },
                    // advanced: [{ focusMode: "continuous" }] // Opsional: Coba paksa autofocus (hanya jalan di beberapa versi iOS terbaru)
                },

                // 3. SPESIFIKKAN FORMAT (SANGAT PENTING)
                // Barcode pabrik/roll kertas biasanya menggunakan Code 128, Code 39, atau EAN.
                // Dengan membatasi pencarian, HP tidak akan nge-lag karena mencoba mencari QR Code/PDF417.
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.QR_CODE // Sisakan QR Code jika sewaktu-waktu labelnya pakai QR
                ]
            });
            html5QrcodeScanner.render(onScanSuccess);
        }
    }
    
    window.addEventListener('DOMContentLoaded', startScanner);

    // Manual Logic
    function submitManual() {
        let noRoll = document.getElementById('manual_no_roll').value.trim();
        // let ket = document.getElementById('manual_keterangan').value.trim();
        if(!noRoll) { 
            Swal.fire({ icon: 'warning', title: 'DATA KOSONG!', text: 'Isi Kode Roll dan Alasan!', confirmButtonText: 'OKE', confirmButtonColor: '#ffc107' });
            return; 
        }
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