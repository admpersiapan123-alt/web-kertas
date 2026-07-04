<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Sesi Shift Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">

<div class="container" style="max-width: 500px;">
    
    <div class="mb-3 text-center">
        <a href="{{ url('/') }}" class="btn btn-sm btn-outline-secondary">← Kembali ke Dashboard</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-header bg-primary text-white text-center py-3" style="border-radius: 12px 12px 0 0;">
            <h5 class="fw-bold mb-0">📝 MULAI SESI SHIFT BARU</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ url('/versi2/simpan-sesi') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Shift</label>
                    <select name="shift_ke" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Shift --</option>
                        <option value="1">Shift 1 (Pagi)</option>
                        <option value="2">Shift 2 (Sore)</option>
                        <option value="3">Shift 3 (Malam)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Nama Kepala Shift</label>
                    <input type="text" name="nama_kepala_shift" class="form-control" placeholder="Masukkan nama..." required autocomplete="off">
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">MULAI SCAN ROLL 🚀</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>