<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kamera Scanner Stock Kertas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        #reader { 
            width: 100%; 
            max-width: 500px; 
            margin: 0 auto; 
            border-radius: 10px; 
            overflow: hidden; 
            border: 3px solid #343a40; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            background: #fff;
        }
        #reader button {
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="mb-3">
        <a href="{{ url('/search') }}" class="btn btn-outline-dark fw-bold shadow-sm">
            ⬅️ KEMBALI KE DAFTAR KERTAS
        </a>
    </div>

    <div class="text-center mb-4">
        <h4 class="fw-bold">Scan Barcode Roll Kertas</h4>
        <p class="text-muted small">Arahkan kamera HP ke barcode pada roll kertas</p>
    </div>

    <div class="mb-4">
        <div id="reader"></div>
    </div>

    <div id="result-container" class="card shadow-sm d-none">
        <div class="card-header bg-success text-white fw-bold text-center fs-5">
            ✅ Roll Ditemukan!
        </div>
        <div class="card-body">
            <table class="table table-sm table-borderless mb-0 fs-5">
                <tr>
                    <th width="40%">No Roll</th>
                    <td>: <span id="res-no-roll" class="fw-bold text-primary"></span></td>
                </tr>
                <tr>
                    <th>Saldo / Sisa</th>
                    <td>: <span id="res-sisa" class="fw-bold text-danger"></span></td>
                </tr>
                <tr>
                    <th>Jenis / GSM</th>
                    <td>: <span id="res-jenis-gsm"></span></td>
                </tr>
                <tr>
                    <th>Lebar</th>
                    <td>: <span id="res-lebar"></span></td>
                </tr>
                <tr>
                    <th>Lokasi</th>
                    <td>: <span id="res-lokasi" class="badge bg-secondary"></span></td>
                </tr>
            </table>
            
            <button class="btn btn-primary btn-lg fw-bold w-100 mt-4 shadow-sm" onclick="resetScanner()">📷 SCAN ROLL LAIN</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    let html5QrcodeScanner;

    function onScanSuccess(decodedText, decodedResult) {
        html5QrcodeScanner.clear();

        let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-84.wav');
        audio.play().catch(e => console.log("Audio autoplay diblokir:", e));

        fetch(`/api/check-roll/${decodedText}`)
            .then(response => response.json())
            .then(res => {
                if(res.success) {
                    document.getElementById('res-no-roll').innerText = res.data.no_roll;
                    document.getElementById('res-sisa').innerText = res.data.sisa_kertas + " Kg"; 
                    document.getElementById('res-jenis-gsm').innerText = `${res.data.jenis} / ${res.data.gsm}`;
                    document.getElementById('res-lebar').innerText = res.data.lebar;
                    document.getElementById('res-lokasi').innerText = res.data.lokasi + " (" + res.data.wilayah + ")";
                    
                    document.getElementById('result-container').classList.remove('d-none');
                } else {
                    alert(res.message);
                    resetScanner();
                }
            })
            .catch(err => {
                alert("Gagal mengambil data ke server.");
                resetScanner();
            });
    }

    function startScanner() {
        if(document.getElementById('reader')) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                fps: 30, // 🚀 NAIKKAN FPS ke 30 agar lebih responsif
                rememberLastUsedCamera: true,
                qrbox: { width: 300, height: 100 }, // 🎯 Perkecil sedikit tingginya agar fokus ke tengah
                videoConstraints: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                // ⚡ PANGKAS FORMAT: Sisakan yang benar-benar dipakai pabrik.
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,// Format paling umum untuk stiker roll/karton
                    Html5QrcodeSupportedFormats.CODE_39, // Buka komen ini jika tidak terbaca
                    Html5QrcodeSupportedFormats.QR_CODE, // Buka komen ini jika barcodenya bentuk kotak
                    Html5QrcodeSupportedFormats.EAN_13,
                ]
            });
            html5QrcodeScanner.render(onScanSuccess);
        }
    }

    function resetScanner() {
        document.getElementById('result-container').classList.add('d-none');
        startScanner();
    }

    window.addEventListener('DOMContentLoaded', startScanner);
</script>

</body>
</html>