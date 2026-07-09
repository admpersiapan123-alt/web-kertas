<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Produksi Corrugator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .menu-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 12px;
            border: none;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            cursor: pointer;
        }
        .icon-box {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .section-title {
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 900px;">
    
    <!-- SEKSI VERSI 1 -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-dark section-title">Modul Dasar (Versi 1)</h2>
        <p class="text-muted">Operasional & Data Master Standard</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="{{ url('/shift') }}" class="text-decoration-none">
                <div class="card menu-card shadow-sm h-100 p-4 text-center">
                    <div class="icon-box">🚜</div>
                    <h5 class="fw-bold text-dark">Scan Shift Roll</h5>
                    <p class="text-muted small mb-0">Catat pemakaian kertas roll manual dengan posisi mesin.</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ url('/hitung-spk') }}" class="text-decoration-none">
                <div class="card menu-card shadow-sm h-100 p-4 text-center">
                    <div class="icon-box">🧮</div>
                    <h5 class="fw-bold text-dark">Hitung Kebutuhan</h5>
                    <p class="text-muted small mb-0">Kalkulasi tonase & kombinasi roll per SPK.</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ url('/search') }}" class="text-decoration-none">
                <div class="card menu-card shadow-sm h-100 p-4 text-center">
                    <div class="icon-box">📦</div>
                    <h5 class="fw-bold text-dark">Stock Kertas</h5>
                    <p class="text-muted small mb-0">Lihat database master roll di gudang saat ini.</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ url('/scan-finish') }}" class="text-decoration-none">
                <div class="card menu-card shadow-sm h-100 p-4 text-center">
                    <div class="icon-box">✅</div>
                    <h5 class="fw-bold text-dark">Scan Roll Finished</h5>
                    <p class="text-muted small mb-0">lihat roll yang telah selesai diproses.</p>
                </div>
            </a>
        </div>

    </div>

    <hr class="my-5 border-secondary opacity-25">

    <!-- SEKSI VERSI 2 -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary section-title">⚡ Versi 2 (Otomatis)</h2>
        <p class="text-muted">Sistem pintar tanpa alokasi posisi mesin manual</p>
    </div>

    <div class="row g-4 justify-content-center mb-5">
        <div class="col-md-4">
            <a href="{{ url('/versi2/scan-shift') }}" class="text-decoration-none">
                <div class="card menu-card border-primary shadow-sm h-100 p-4 text-center" style="border-width: 2px;">
                    <div class="icon-box">📷</div>
                    <h5 class="fw-bold text-primary">Scan Masuk / Keluar</h5>
                    <p class="text-muted small mb-0">Scan roll forklift cepat tanpa pilih posisi mesin.</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ url('/versi2/pencocokan') }}" class="text-decoration-none">
                <div class="card menu-card border-primary shadow-sm h-100 p-4 text-center" style="border-width: 2px;">
                    <div class="icon-box">⚙️</div>
                    <h5 class="fw-bold text-primary">Pencocokan AI</h5>
                    <p class="text-muted small mb-0">Hitung & bagi alokasi roll ke SPK secara otomatis.</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ url('/versi2/riwayat') }}" class="text-decoration-none">
                <div class="card menu-card border-primary shadow-sm h-100 p-4 text-center" style="border-width: 2px;">
                    <div class="icon-box">📜</div>
                    <h5 class="fw-bold text-primary">Riwayat V2</h5>
                    <p class="text-muted small mb-0">Log lengkap hasil mak-comblang otomatis sistem.</p>
                </div>
            </a>
        </div>
    </div>

    <hr class="my-5 border-secondary opacity-25">

    <!-- SEKSI BARU: VERSI 3 -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-success section-title">🔥 Versi 3 (Terbaru)</h2>
        <p class="text-muted">Sistem Track & Grouping Roll Per Lebar Mesin</p>
    </div>

    <div class="row g-4 justify-content-center pb-5">
        <div class="col-md-4">
            <a href="{{ url('/checker') }}" class="text-decoration-none">
                <div class="card menu-card shadow-sm h-100 p-4 text-center">
                    <div class="icon-box">🔍</div>
                    <h5 class="fw-bold text-dark">Checker</h5>
                    <p class="text-muted small mb-0">Tulis Planing & Jadwal Persiapan</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ url('/shift-v3') }}" class="text-decoration-none">
                <div class="card menu-card border-success shadow-sm h-100 p-4 text-center" style="border-width: 2px;">
                    <div class="icon-box">📊</div>
                    <h5 class="fw-bold text-success">Scan & Laporan V3</h5>
                    <p class="text-muted small mb-0">Kelola shift dan cetak laporan otomatis dikelompokkan per lebar jalan secara presisi.</p>
                </div>
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>