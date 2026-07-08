<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Shift Kerja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-3" style="max-width: 600px;">


    <div class="text-start mb-3">
        <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
    </div>
    
    <h4 class="fw-bold text-center mb-3">Sistem Cetak Roll Forklift</h4>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold small">BUAT SESI BARU</div>
        <div class="card-body p-3">
            <form action="{{ url('/shift/store') }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-5">
                        <input type="text" name="kepala_shift" class="form-control form-control-sm" placeholder="Nama Checker" required>
                    </div>
                    <div class="col-3">
                        <select name="shift_ke" class="form-select form-select-sm" required>
                            <option value="">Shift...</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100 mt-2">+ Mulai Mencatat</button>
            </form>
        </div>
    </div>

    <h6 class="fw-bold mb-2">Riwayat Sesi Kerja (History)</h6>
    @foreach($shifts as $s)
    <div class="card shadow-sm mb-2">
        <div class="card-body p-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold text-dark">{{ $s->kepala_shift }}</h6>
                <small class="text-muted">{{ date('d M Y', strtotime($s->tanggal)) }}</small> | 
                @if($s->status == 'aktif')
                    <span class="badge bg-success small">Aktif</span>
                @else
                    <span class="badge bg-secondary small">Selesai</span>
                @endif
            </div>
            <div class="btn-group">
                <a href="{{ url('/shift/'.$s->id.'/dashboard') }}" class="btn btn-primary btn-sm">Buka HP</a>
                <a href="{{ url('/shift/'.$s->id.'/edit') }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            </div>
        </div>
    </div>
    @endforeach

</div>
</body>
</html>