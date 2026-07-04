<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Stock Kertas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-4">
        
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <a href="{{ url('/') }}" class="btn btn-outline-dark fw-bold shadow-sm">
                ⬅️ KEMBALI
            </a>
            <h2 class="mb-0 fw-bold">Laporan Stock Kertas</h2>
            <div class="d-flex gap-2">
                <a href="{{ url('/scan') }}" class="btn btn-success fw-bold shadow-sm">
                    📷 SCAN BARCODE
                </a>
                <a href="{{ url('/stock-kertas/import') }}" class="btn btn-primary fw-bold shadow-sm">
                    📥 IMPORT CSV
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <form action="{{ url('/search') }}" method="GET">
                    <div class="input-group shadow-sm">
                        <input type="text" name="search" class="form-control form-control-lg border-primary" 
                               placeholder="Cari Nomor Roll..." 
                               value="{{ request('search', $search ?? '') }}">
                        
                        <input type="text" name="gsm" class="form-control form-control-lg border-primary" 
                               placeholder="Cari GSM..." 
                               value="{{ request('gsm', $gsm ?? '') }}">
                        
                        <input type="text" name="lebar" class="form-control form-control-lg border-primary" 
                               placeholder="Cari Lebar..." 
                               value="{{ request('lebar', $lebar ?? '') }}">
                        
                        <button class="btn btn-primary fw-bold px-4" type="submit">🔍 CARI</button>
                        
                        @if(request('search') || request('gsm') || request('lebar'))
                            <a href="{{ url('/search') }}" class="btn btn-danger fw-bold d-flex align-items-center px-3">✖ RESET</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark text-center align-middle">
                            <tr>
                                <th>No</th>
                                <th>Jenis</th>
                                <th>GSM</th>
                                <th>Lebar</th>
                                <th>No Roll</th>
                                <th>No Roll Asli</th>
                                <th>Sisa Kertas</th>
                                <th>No PO</th>
                                <th>Wilayah</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody class="text-center align-middle">
                            @forelse ($data_kertas as $index => $kertas)
                            <tr>
                                <td>
                                    @if($data_kertas instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        {{ $data_kertas->firstItem() + $index }}
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </td>
                                <td>{{ $kertas->jenis }}</td>
                                <td>{{ $kertas->gsm }}</td>
                                <td>{{ $kertas->lebar }}</td>
                                <td><strong class="text-primary fs-5">{{ $kertas->no_roll }}</strong></td>
                                <td>{{ $kertas->no_roll_asli }}</td>
                                <td><span class="badge bg-danger fs-6">{{ $kertas->sisa_kertas }} Kg</span></td>
                                <td>{{ $kertas->no_po }}</td>
                                <td>{{ $kertas->wilayah }}</td>
                                <td>{{ $kertas->lokasi }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-danger py-4 fs-5">
                                    Data dengan filter tersebut tidak ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($data_kertas instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="d-flex justify-content-end mt-4">
            {{ $data_kertas->appends(request()->query())->links() }}
        </div>
        @endif
        
    </div>

</body>
</html>