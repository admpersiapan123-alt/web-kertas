<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forklift V2 - Scan Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width: 600px;">
    
    <div class="mb-3">
        <a href="{{ url('/') }}" class="btn btn-sm btn-secondary">← Kembali ke Dashboard</a>
    </div>

    <div class="card shadow-sm border-0 mb-4 text-center p-3 bg-primary text-white" style="border-radius: 12px;">
        <h4 class="fw-bold mb-0">🚜 FORKLIFT SCAN (VERSI 2)</h4>
        <small class="opacity-75">Sistem Input Cepat Tanpa Posisi Mesin</small>
    </div>
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-body p-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-primary mb-1">Shift {{ $shift->shift_ke }}</span>
                <div class="small fw-bold text-dark">Kepala: {{ $shift->nama_kepala_shift }}</div>
                <div class="small text-muted">{{ \Carbon\Carbon::parse($shift->tanggal)->format('d M Y') }}</div>
            </div>
            <form action="{{ url('/versi2/akhiri-sesi') }}" method="POST" onsubmit="return confirm('Yakin ingin mengakhiri sesi shift ini?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger fw-bold shadow-sm">Akhiri Sesi 🛑</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-3">1. Scan Roll Masuk</h5>
            <form action="{{ url('/versi2/scan-masuk') }}" method="POST">
                @csrf
                <div class="input-group">
                    <input type="text" name="no_roll" class="form-control form-control-lg text-center" 
                           placeholder="Scan / Ketik No. Roll" autofocus required autocomplete="off">
                    <button class="btn btn-primary px-4 fw-bold" type="submit">INPUT</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-3">2. Roll Aktif di Mesin ({{ $rollAktif->count() }})</h5>
            
            @if($rollAktif->isEmpty())
                <div class="text-center text-muted py-4">
                    <p class="mb-0 small">Belum ada roll yang terpasang di mesin saat ini.</p>
                </div>
            @else
                <div class="list-group">
                    @foreach($rollAktif as $roll)
                        <div class="list-group-item list-group-item-action p-3 mb-2 border rounded shadow-sm">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-bold text-primary">{{ $roll->no_roll }}</h6>
                                <span class="badge bg-warning text-dark px-2 py-1 small">{{ $roll->jenis }} | {{ $roll->gsm }} GSM</span>
                            </div>
                            <p class="mb-2 text-muted small">
                                Lebar: <strong>{{ $roll->lebar }} cm</strong> | 
                                Berat Awal: <strong class="text-dark">{{ number_format($roll->berat_awal) }} Kg</strong>
                            </p>
                            
                            <form action="{{ url('/versi2/scan-keluar/' . $roll->id) }}" method="POST" class="border-top pt-2 mt-2">
                                @csrf
                                <div class="row g-2 align-items-center">
                                    <div class="col-7">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light small">Sisa (Kg)</span>
                                            <input type="number" step="0.01" name="berat_sisa" class="form-control" placeholder="0" required>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">Scan Keluar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>