<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Forklift V2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <a href="{{ url('/versi2/scan-shift') }}" class="btn btn-dark fw-bold shadow-sm">⬅️ KEMBALI</a>
        <h4 class="fw-bold text-primary mb-0 d-none d-md-block">⚡ V2: SCAN FORKLIFT</h4>
        <div class="text-end">
            <span class="badge bg-primary fs-6 mb-1 shadow-sm px-3 py-2">
                📢 {{ $shift->shift }} ({{ date('d-m-Y', strtotime($shift->tanggal)) }})
            </span>
            <div class="small text-muted fw-bold" style="font-size: 0.82rem;">
                <span class="me-2">👤 Ka. Shift: <b class="text-dark text-uppercase">{{ $shift->kepala_shift }}</b></span>
                @if($shift->checker)
                    <span class="border-start border-secondary ps-2">📋 Checker: <b class="text-dark text-uppercase">{{ $shift->checker }}</b></span>
                @endif
            </div>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success fw-bold">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger fw-bold border-2 border-danger shadow-sm">{{ session('error') }}</div> @endif

    <div class="card shadow-sm border-0 mb-4 bg-primary bg-opacity-10">
        <div class="card-body text-center p-4">
            <h5 class="fw-bold text-primary mb-3">📷 SCAN BARCODE ROLL NAIK MESIN</h5>
            <form action="{{ url('/versi2/api/shift/'.$shift->id.'/ambil-roll') }}" method="POST" class="d-flex justify-content-center">
                @csrf
                <div class="input-group input-group-lg" style="max-width: 500px;">
                    <input type="text" name="no_roll" class="form-control text-center fw-bold text-uppercase" placeholder="TAP SCANNER DI SINI..." autofocus required>
                    <button class="btn btn-primary fw-bold px-4" type="submit">INPUT</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold">Daftar Roll Terpakai (Global Pool)</span>
            <form action="{{ url('/versi2/scan-shift/'.$shift->id.'/dashboard') }}" method="GET" style="max-width: 300px;" class="w-100">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Cari No. Roll / Jenis / GSM..." value="{{ $search ?? '' }}">
                    @if(!empty($search)) <a href="{{ url('/versi2/scan-shift/'.$shift->id.'/dashboard') }}" class="btn btn-secondary">❌</a> @endif
                    <button class="btn btn-primary fw-bold" type="submit">🔍 TRACK</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">No. Roll</th>
                            <th width="35%" class="text-start">Spesifikasi Kertas</th>
                            <th width="12%">Awal (Kg)</th>
                            <th width="15%">Sisa Akhir (Kg)</th>
                            <th width="13%" class="table-success">Pemakaian (Kg)</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rolls as $r)
                        @php 
                            $sudah_diinput = $r->updated_at->format('Y-m-d H:i:s') != $r->created_at->format('Y-m-d H:i:s');
                            $pemakaian_kg = floatval($r->sisa_kilo_awal) - floatval($r->sisa_kilo_akhir);
                        @endphp
                        <tr style="{{ $sudah_diinput ? 'opacity: 0.5; background-color: #f1f3f5;' : '' }}">
                            <td class="fw-bold text-primary">{{ $r->stockKertas->no_roll ?? 'N/A' }}</td>
                            <td class="text-start fw-bold" style="font-family: monospace; font-size: 0.95rem;">
                                <span class="text-muted">Jns:</span> <span class="text-dark me-2">{{ $r->stockKertas->jenis ?? '-' }}</span>
                                <span class="text-muted">GSM:</span> <span class="text-danger me-2">{{ $r->stockKertas->gsm ?? '-' }}</span>
                                <span class="text-muted">Lbr:</span> <span class="text-primary">{{ $r->stockKertas->lebar ?? 0 }}</span>
                            </td>
                            <td class="text-secondary fw-bold">{{ floatval($r->sisa_kilo_awal) }} Kg</td>
                            <td>
                                <form action="{{ url('/versi2/shift/kembali-roll/'.$r->id) }}" method="POST" class="d-flex justify-content-center">
                                    @csrf
                                    <div class="input-group input-group-sm" style="max-width: 140px;">
                                        <input type="number" step="0.01" name="sisa_kilo_akhir" class="form-control text-center fw-bold" value="{{ floatval($r->sisa_kilo_akhir) }}">
                                        <button class="btn btn-success fw-bold" type="submit">SAVE</button>
                                    </div>
                                </form>
                            </td>
                            <td class="table-success fw-bold text-success fs-6">{{ number_format($pemakaian_kg, 2) }} Kg</td>
                            <td>
                                <form action="{{ url('/versi2/shift/batal-roll/'.$r->id) }}" method="POST" onsubmit="return confirm('Batalkan roll ini?');">
                                    @csrf <button class="btn btn-sm btn-outline-danger py-0">❌</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted py-4">Data tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>