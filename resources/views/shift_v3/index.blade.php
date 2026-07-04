<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Shift V3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 900px;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-success mb-0">📊 DAFTAR SHIFT V3</h3>
        <a href="{{ url('/') }}" class="btn btn-secondary fw-bold">🏠 Menu Utama</a>
    </div>

    @if(session('success')) <div class="alert alert-success fw-bold">{{ session('success') }}</div> @endif

    <!-- FORM BUAT SHIFT BARU -->
    <div class="card shadow-sm mb-4 border-success border-2">
        <div class="card-header bg-success text-white fw-bold">➕ Buat Shift Baru</div>
        <div class="card-body bg-white">
            <form action="{{ url('/shift-v3') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label fw-bold text-muted small mb-1">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold text-muted small mb-1">Shift Ke-</label>
                    <select name="shift_ke" class="form-control fw-bold" required>
                        <option value="1">Shift 1</option><option value="2">Shift 2</option><option value="3">Shift 3</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small mb-1">Kepala Shift</label>
                    <input type="text" name="kepala_shift" class="form-control" placeholder="Nama..." required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">SIMPAN</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0 text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tgl</th>
                        <th>Shift</th>
                        <th>Kepala Shift</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $s)
                    <tr>
                        <td>{{ date('d M Y', strtotime($s->tanggal)) }}</td>
                        <td class="fw-bold text-primary">Shift {{ $s->shift_ke }}</td>
                        <td class="fw-bold">{{ $s->kepala_shift }}</td>
                        <td>
                            <!-- TOMBOL SCAN & CETAK -->
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ url('/shift-v3/scan/'.$s->id) }}" class="btn btn-sm btn-warning fw-bold text-dark">📷 SCAN</a>
                                <a href="{{ url('/shift-v3/print/'.$s->id) }}" class="btn btn-sm btn-primary fw-bold" target="_blank">🖨️ CETAK</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-4 text-muted fw-bold">Belum ada data shift di V3.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>