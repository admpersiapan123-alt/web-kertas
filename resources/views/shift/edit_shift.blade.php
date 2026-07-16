<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Edit Sesi Shift</title>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark fw-bold">Edit Informasi Sesi</div>
        <div class="card-body">
            
            <!-- FORM UPDATE -->
            <form action="{{ url('/shift/'.$shift->id.'/update') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="small fw-bold">Judul / Kepala Shift</label>
                    <input type="text" name="kepala_shift" class="form-control" value="{{ $shift->kepala_shift }}" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ $shift->tanggal }}" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Shift Ke</label>
                    <select name="shift_ke" class="form-select" required>
                        <option value="1" {{ $shift->shift_ke == 1 ? 'selected' : '' }}>Shift 1</option>
                        <option value="2" {{ $shift->shift_ke == 2 ? 'selected' : '' }}>Shift 2</option>
                        <option value="3" {{ $shift->shift_ke == 3 ? 'selected' : '' }}>Shift 3</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="small fw-bold">Status Sesi</label>
                    <select name="status" class="form-select">
                        <option value="aktif" {{ $shift->status == 'aktif' ? 'selected' : '' }}>Aktif (Bisa Di-scan)</option>
                        <option value="selesai" {{ $shift->status == 'selesai' ? 'selected' : '' }}>Selesai / Terkunci</option>
                    </select>
                </div>
                
                <div class="d-flex gap-2 mb-3">
                    <a href="{{ url('/shift') }}" class="btn btn-secondary w-50">Kembali</a>
                    <button type="submit" class="btn btn-success w-50">Simpan</button>
                </div>
            </form>

            <hr> <!-- Garis pemisah untuk area berbahaya (Hapus) -->

            <!-- FORM HAPUS -->
            <form action="{{ url('/shift/'.$shift->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus sesi shift ini beserta semua data di dalamnya?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100">🗑️ Hapus Sesi Ini</button>
            </form>

        </div>
    </div>
</div>
</body>
</html>