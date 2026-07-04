<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Riwayat Persiapan Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding-bottom: 80px; }
        .card-lebar { border: 2px solid #198754; border-radius: 10px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .card-merged { border-color: #0d6efd; opacity: 0.85; } /* Style kalau udah masuk V2 */
        .header-lebar { background-color: #198754; color: white; padding: 12px 15px; font-size: 1.2rem; font-weight: bold; }
        .header-merged { background-color: #0d6efd; }
    </style>
</head>
<body>

<div class="bg-success text-white text-center py-3 shadow-sm sticky-top" id="top-nav">
    <h5 class="mb-0 fw-bold">🕒 RIWAYAT PERSIAPAN</h5>
    <small class="opacity-75">Data Roll Siap Dieksekusi Mesin</small>
</div>

<div class="container py-3">
    
    <div class="d-flex justify-content-center gap-2 mb-4">
        <a href="{{ url('/checker') }}" class="btn btn-outline-secondary fw-bold bg-white shadow-sm px-4">📋 ANTREAN</a>
        <a href="{{ url('/checker/riwayat') }}" class="btn btn-success fw-bold shadow-sm px-4">🕒 RIWAYAT</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 fw-bold shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 fw-bold shadow-sm">{{ session('error') }}</div>
    @endif

    @forelse($tasks as $t)
    @php 
        $savedTaskScans = isset($scans[$t->id]) ? $scans[$t->id] : collect();
        $totalKg = $savedTaskScans->sum('berat_kg');
        $isMerged = ($t->status == 'MERGED');
    @endphp
    
    <div class="card-lebar bg-white shadow-sm {{ $isMerged ? 'card-merged' : '' }}">
        <div class="header-lebar d-flex justify-content-between align-items-center {{ $isMerged ? 'header-merged' : '' }}">
            <span>{{ $isMerged ? '📦' : '✅' }} LEBAR {{ $t->lebar_cm }} cm</span>
            <span class="badge bg-light text-dark fs-6">{{ $t->status }}</span>
        </div>

        <div class="p-3">
            <div class="alert alert-light border shadow-sm mb-3 text-center p-2">
                <small class="text-muted d-block">{{ $isMerged ? 'Sudah Di-Merge ke V2 pada:' : 'Selesai Disiapkan pada:' }}</small>
                <strong class="text-dark fs-5">{{ \Carbon\Carbon::parse($t->updated_at)->format('d M Y - H:i') }}</strong>
            </div>

            <div class="bg-light p-2 rounded border mb-3">
                <h6 class="fw-bold text-secondary mb-2 border-bottom pb-1">📦 Ringkasan Roll Persiapan:</h6>
                @if($savedTaskScans->count() > 0)
                    <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                        @foreach($savedTaskScans as $roll)
                            <li class="fw-bold text-dark">
                                Posisi <span class="text-primary">{{ strtoupper($roll->posisi) }}</span>: 
                                {{ $roll->no_roll }} <span class="text-success">({{ $roll->berat_kg }} Kg)</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span class="text-danger italic">Tugas ini disubmit tanpa scan roll satupun!</span>
                @endif
            </div>

            @if(!$isMerged)
                <div class="mb-3 p-3 border border-primary bg-primary bg-opacity-10 rounded">
                    <h6 class="fw-bold text-primary mb-2">Pindahkan ke Scan Forklift (V1)</h6>
                    <form action="{{ url('/checker/task/push/'.$t->id) }}" method="POST" onsubmit="return confirm('Pindahkan data ke Scan Shift V1? Kiloan akan otomatis diganti dengan data mutlak komputer!')">
                        @csrf
                        <div class="input-group shadow-sm">
                            <!-- NAMA SELECT-NYA UBAH JADI shifts_id SESUAI CONTROLLER MAS -->
                            <select name="shifts_id" class="form-select fw-bold" required>
                                <option value="">-- Pilih Shift V1 --</option>
                                @foreach($shifts as $sh)
                                    <option value="{{ $sh->id }}">
                                        Shift {{ $sh->shift_ke }} | {{ \Carbon\Carbon::parse($sh->tanggal)->format('d M Y') }} ({{ $sh->kepala_shift }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary fw-bold px-3">🚀 PUSH</button>
                        </div>
                    </form>
                </div>

                <form action="{{ url('/checker/task/revert/'.$t->id) }}" method="POST" class="w-100" onsubmit="return confirm('Kembalikan ke antrean Checker?')">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning fw-bold w-100 shadow-sm text-dark">✏️ KEMBALIKAN KE ANTREAN (EDIT)</button>
                </form>
            @else
                <div class="alert alert-info text-center fw-bold border-primary mb-3 shadow-sm">
                    ✔️ DATA SUDAH TERKIRIM KE SCAN SHIFT (V1)
                </div>
                
                <form action="{{ url('/checker/task/unmerge/'.$t->id) }}" method="POST" class="w-100" onsubmit="return confirm('⚠️ PERINGATAN!\n\nYakin ingin BATAL MERGE? Data roll ini akan ditarik/dihapus dari antrean sistem V1!')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger fw-bold w-100 shadow-sm">⏪ BATAL MERGE (TARIK DATA DARI V1)</button>
                </form>
            @endif

        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <h4 class="text-muted fw-bold">Belum ada riwayat persiapan.</h4>
    </div>
    @endforelse

</div>

<script>
    if(document.querySelector('.header-merged')) {
        document.getElementById('top-nav').classList.replace('bg-success', 'bg-primary');
    }
</script>

</body>
</html>