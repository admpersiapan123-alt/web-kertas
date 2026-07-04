<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V2 - Manajemen Shift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #f8f9fa; }</style>
</head>
<body>
<div class="container py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/') }}" class="btn btn-outline-dark fw-bold">⬅️ MENU UTAMA</a>
        <h3 class="fw-bold text-primary mb-0">⚡ V2: Buka Shift Baru</h3>
    </div>

    @if(session('success')) <div class="alert alert-success fw-bold">{{ session('success') }}</div> @endif

    <div class="card shadow-sm border-primary border-top border-4 mb-4">
        <div class="card-body p-4">
            <form action="{{ url('/versi2/scan-shift/store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-muted">TANGGAL PRODUKSI</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-muted">SHIFT</label>
                        <select name="shift" class="form-select" required>
                            <option value="Shift 1">Shift 1 (Pagi)</option>
                            <option value="Shift 2">Shift 2 (Sore)</option>
                            <option value="Shift 3">Shift 3 (Malam)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-muted">KEPALA SHIFT</label>
                        <input type="text" name="kepala_shift" class="form-control text-uppercase" placeholder="Nama Ka. Shift" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-muted">CHECKER</label>
                        <input type="text" name="checker" class="form-control text-uppercase" placeholder="Nama Checker">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">🚀 BUKA SHIFT V2</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h5 class="fw-bold text-dark mb-3">📂 Riwayat Shift V2</h5>
    <div class="list-group shadow-sm">
        @foreach($shifts as $s)
        <a href="{{ url('/versi2/scan-shift/'.$s->id.'/dashboard') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
            <div>
                <h6 class="mb-1 fw-bold text-dark">{{ $s->shift }} - {{ date('d M Y', strtotime($s->tanggal)) }}</h6>
                <small class="text-muted">Ka. Shift: {{ $s->kepala_shift }} | Checker: {{ $s->checker ?? '-' }}</small>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">MASUK DASHBOARD ➡️</span>
        </a>
        @endforeach
    </div>
</div>
</body>
</html>