<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat V2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ url('/') }}" class="btn btn-outline-dark fw-bold">⬅️ MENU UTAMA</a>
        <h3 class="fw-bold text-primary mb-0">📜 RIWAYAT V2 (GLOBAL POOL)</h3>
    </div>

    @if(session('success')) <div class="alert alert-success fw-bold">{{ session('success') }}</div> @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode Sesi V2</th>
                            <th>Tanggal Hitung</th>
                            <th>Total SPK</th>
                            <th>Total Aktual (Kg)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kalkulasis as $k)
                        @php $data_spk = is_array($k->data_spk) ? $k->data_spk : json_decode($k->data_spk, true); @endphp
                        <tr>
                            <td class="fw-bold text-primary">{{ $k->kode_sesi }}</td>
                            <td>{{ $k->created_at->format('d M Y H:i') }}</td>
                            <td><span class="badge bg-secondary">{{ count($data_spk ?? []) }} SPK</span></td>
                            <td class="text-danger fw-bold">{{ number_format($k->total_aktual_semua, 2) }} Kg</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ url('/versi2/riwayat/'.$k->id) }}" class="btn btn-sm btn-info fw-bold text-white shadow-sm">👁️ DETAIL & REVISI</a>
                                    
                                    <form action="{{ url('/versi2/delete/'.$k->id) }}" method="POST" onsubmit="return confirm('Hapus Sesi V2 Ini?');">
                                        @csrf <button class="btn btn-sm btn-danger fw-bold shadow-sm">🗑️ HAPUS</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted py-4">Belum ada riwayat pencocokan V2.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $kalkulasis->links() }}
        </div>
    </div>
</div>
</body>
</html>