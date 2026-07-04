<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checker Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding-bottom: 80px; }
        .card-lebar { border: 2px solid #343a40; border-radius: 10px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header-lebar { background-color: #343a40; color: white; padding: 12px 15px; font-size: 1.2rem; font-weight: bold; }
        .header-stand { background-color: #0d6efd; color: white; padding: 8px 12px; font-weight: bold; border-radius: 8px 8px 0 0; }
        .kertas-block { background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        
        .input-kode { text-transform: uppercase; font-weight: bold; text-align: center; font-size: 0.85rem; }
        .inp-depan { border-radius: 6px 0 0 6px; border-right: none; }
        .inp-middle { border-radius: 0; background-color: #fff3cd; color: #856404; }
        .inp-belakang { border-radius: 0 6px 6px 0; border-left: none; }
        
        .row-tersimpan { background-color: #d1e7dd; border: 1px solid #0f5132; border-radius: 6px; padding: 5px; }
        
        /* Tambahan Visual Kotak Kg Bisa Diedit */
        .inp-kg:focus { background-color: #e7f1ff !important; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    </style>
</head>
<body>

<div class="bg-primary text-white text-center py-3 shadow-sm sticky-top">
    <h5 class="mb-0 fw-bold">📱 WMS CHECKER</h5>
    <small class="opacity-75">Sistem Nyicil Persiapan Roll</small>
</div>

<div class="container py-3">

<!-- MENU NAVIGASI -->
    <div class="d-flex justify-content-center gap-2 mb-4">
        <a href="{{ url('/checker') }}" class="btn btn-primary fw-bold shadow-sm px-4">📋 ANTREAN</a>
        <a href="{{ url('/checker/riwayat') }}" class="btn btn-outline-secondary fw-bold bg-white shadow-sm px-4">🕒 RIWAYAT</a>
    </div>
    
    <div class="card bg-white border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-secondary" style="font-size: 0.85rem;">📥 Masukkan Jadwal Baru:</span>
                
                <form action="{{ url('/checker/task/reset') }}" method="POST" onsubmit="return confirm('💥 PERINGATAN KERAS! 💥\n\nYakin mau MENGHAPUS SEMUA jadwal dan data roll persiapan?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger fw-bold shadow-sm">⚠️ RESET SEMUA</button>
                </form>
            </div>

            <form action="{{ url('/checker/store') }}" method="POST">
                @csrf
                <div class="input-group">
                    <textarea name="json_data" class="form-control" rows="1" placeholder='Paste JSON di sini...' required style="font-size:0.8rem; font-family:monospace;"></textarea>
                    <button type="submit" class="btn btn-dark fw-bold px-3">➕ EXTRACT</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($tasks as $t)
    @php 
        $posisiArray = json_decode($t->data_spk, true); 
        if(!is_array($posisiArray)) continue; 
        $savedTaskScans = isset($scans[$t->id]) ? $scans[$t->id] : collect();
    @endphp
    
    <div class="card-lebar bg-white shadow-sm">
        <div class="header-lebar d-flex justify-content-between align-items-center">
            <span>🚀 LEBAR {{ $t->lebar_cm }} cm</span>
            
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark fs-6">{{ $t->status }}</span>
                <form action="{{ url('/checker/task/delete/'.$t->id) }}" method="POST" class="m-0" onsubmit="return confirm('Yakin mau menghapus jadwal Lebar {{ $t->lebar_cm }} cm ini?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger py-0 px-2 fw-bold border-light" style="font-size: 1rem;" title="Hapus Jadwal Ini">🗑️</button>
                </form>
            </div>
        </div>

        <div class="p-2 bg-light">
            @foreach($posisiArray as $grup)
                <div class="mb-4 border border-primary rounded-3 shadow-sm bg-white">
                    <div class="header-stand fs-5">📍 Posisi: {{ $grup['judul'] }}</div>

                    <div class="p-2">
                        @foreach($grup['kertas_list'] as $dataKertas)
                            @php 
                                $gsmAsli = $dataKertas['kertas_info']['gsm_asli'];
                                $gsmBaca = $dataKertas['kertas_info']['gsm_baca'];
                                $middleCode = $dataKertas['kertas_info']['middle_code'];
                                
                                $uid = 'spk-' . md5($t->id . $grup['id_pos'] . $gsmAsli); 
                                
                                $estimasiRoll = ceil($dataKertas['estimasi_kg'] / 800); 
                                if($estimasiRoll < 1) $estimasiRoll = 1;

                                $rollTersimpan = $savedTaskScans->where('posisi', $grup['id_pos'])->where('gsm_asli', $gsmAsli);
                                $sisaInput = $estimasiRoll - $rollTersimpan->count();
                                if($sisaInput < 1 && $rollTersimpan->count() == 0) $sisaInput = 1;
                                if($sisaInput < 0) $sisaInput = 0; 
                            @endphp

                            <div class="kertas-block shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0">{{ $gsmAsli }} <small class="text-muted fs-6">({{ $gsmBaca }})</small></h5>
                                        <div class="text-primary fw-bold" style="font-size: 0.85rem;">Total Lari: {{ number_format($dataKertas['total_meter'], 0) }} M</div>
                                        <div class="text-danger fw-bold" style="font-size: 0.85rem;">Estimasi Butuh: ± {{ number_format($dataKertas['estimasi_kg'], 0) }} Kg</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning text-dark mb-1">Butuh ~{{ $estimasiRoll }} Roll</span><br>
                                        <button class="btn btn-sm btn-outline-secondary fw-bold mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uid }}" style="font-size: 0.7rem;">
                                            Lihat SPK 👇
                                        </button>
                                    </div>
                                </div>

                                <div class="collapse mb-2" id="{{ $uid }}">
                                    <div class="card card-body p-2 bg-light border-0 shadow-inner" style="font-size: 0.75rem;">
                                        <span class="fw-bold mb-1 text-primary">Daftar Jalan SPK:</span>
                                        <ul class="mb-0 ps-3 text-dark fw-bold">
                                            @foreach($dataKertas['spk_detail'] as $sd)
                                                <li><span class="text-muted">Seq {{ $sd['seq'] }} :</span> {{ $sd['spk_nama'] }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>

                                <div class="container-input-roll bg-light p-2 rounded border" 
                                     data-task="{{ $t->id }}" data-pos="{{ $grup['id_pos'] }}" data-gsmasli="{{ $gsmAsli }}" data-gsmbaca="{{ $gsmBaca }}">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-secondary" style="font-size: 0.85rem;">Scan & Simpan Roll:</span>
                                        <button type="button" class="btn btn-sm btn-success py-1 px-3 fw-bold shadow-sm" onclick="tambahBarisInput(this)">➕ ROLL</button>
                                    </div>
                                    
                                    <div class="area-input-dinamis">
                                        @foreach($rollTersimpan as $saved)
                                            <div class="row g-1 align-items-center mt-1 row-tersimpan">
                                                <div class="col-8">
                                                    <div class="fw-bold text-success">✅ {{ $saved->no_roll }}</div>
                                                </div>
                                                <div class="col-3 text-end">
                                                    <span class="fw-bold text-dark">{{ $saved->berat_kg }} Kg</span>
                                                </div>
                                                <div class="col-1 text-center">
                                                    <button type="button" class="btn btn-sm btn-danger py-0 px-2 fw-bold" onclick="hapusRollDatabase(this, {{ $saved->id }})">✖</button>
                                                </div>
                                            </div>
                                        @endforeach

                                        @for ($i = 0; $i < $sisaInput; $i++)
                                            <div class="row g-1 align-items-center baris-input-roll mt-1">
                                                <div class="col-7">
                                                    <div class="input-group input-group-sm shadow-sm">
                                                        <input type="text" class="form-control input-kode inp-depan" placeholder="DEPAN" style="width: 35%;">
                                                        <input type="text" class="form-control input-kode inp-middle" value="{{ $middleCode }}" style="width: 25%;">
                                                        <input type="text" class="form-control input-kode inp-belakang" placeholder="BELAKANG" style="width: 40%;" onchange="cekKgDatabase(this)">
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" class="form-control form-control-sm text-end fw-bold text-primary bg-white border-primary inp-kg shadow-sm" placeholder="✏️ Kg..." title="Klik untuk edit Kg fisik">
                                                </div>
                                                <div class="col-2 text-center d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" onclick="simpanRollKeDatabase(this)">💾</button>
                                                    <button type="button" class="btn btn-sm btn-danger btn-hapus-baris {{ ($i == 0 && $rollTersimpan->count() == 0) ? 'd-none' : '' }}" onclick="this.closest('.baris-input-roll').remove()">✖</button>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>

                                    <template class="template-baris">
                                        <div class="row g-1 align-items-center baris-input-roll mt-1">
                                            <div class="col-7">
                                                <div class="input-group input-group-sm shadow-sm">
                                                    <input type="text" class="form-control input-kode inp-depan" placeholder="DEPAN" style="width: 35%;">
                                                    <input type="text" class="form-control input-kode inp-middle" value="{{ $middleCode }}" style="width: 25%;">
                                                    <input type="text" class="form-control input-kode inp-belakang" placeholder="BELAKANG" style="width: 40%;" onchange="cekKgDatabase(this)">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <input type="text" class="form-control form-control-sm text-end fw-bold text-primary bg-white border-primary inp-kg shadow-sm" placeholder="✏️ Kg..." title="Klik untuk edit Kg fisik">
                                            </div>
                                            <div class="col-2 text-center d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" onclick="simpanRollKeDatabase(this)">💾</button>
                                                <button type="button" class="btn btn-sm btn-danger btn-hapus-baris" onclick="this.closest('.baris-input-roll').remove()">✖</button>
                                            </div>
                                        </div>
                                    </template>
                                    
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="p-3 bg-white text-center border-top">
            <form action="{{ url('/checker/task/submit/'.$t->id) }}" method="POST" onsubmit="return confirm('Sudah yakin semua roll selesai discan? Data akan dipindah ke Riwayat!')">
                @csrf
                <button type="submit" class="btn btn-success btn-lg fw-bold w-100 py-3 shadow-sm">✅ SUBMIT PERSIAPAN LEBAR {{ $t->lebar_cm }}</button>
            </form>
        </div>
    </div>
    @endforeach
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function tambahBarisInput(btn) {
        let container = btn.closest('.container-input-roll'); 
        let templateHTML = container.querySelector('.template-baris').innerHTML;
        container.querySelector('.area-input-dinamis').insertAdjacentHTML('beforeend', templateHTML);
    }

    function cekKgDatabase(inputBelakang) {
        let baris = inputBelakang.closest('.baris-input-roll');
        let depan = baris.querySelector('.inp-depan').value.trim().toUpperCase();
        let middle = baris.querySelector('.inp-middle').value.trim().toUpperCase();
        let belakang = baris.querySelector('.inp-belakang').value.trim().toUpperCase();
        let kgBox = baris.querySelector('.inp-kg');

        if (depan && middle && belakang) {
            let fullRoll = depan + middle + belakang;
            kgBox.value = "Load...";

            fetch('{{ url("/checker/scan/fetch-kg") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ no_roll: fullRoll })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    kgBox.value = data.kg; 
                } else {
                    kgBox.value = ""; // Dikosongkan agar bisa diketik manual kalau darurat
                    alert("❌ Roll " + fullRoll + " tidak ditemukan! Silakan ketik Kg secara manual jika fisik ada.");
                }
            }).catch(err => { kgBox.value = ""; });
        }
    }

    function simpanRollKeDatabase(btn) {
        let baris = btn.closest('.baris-input-roll');
        let container = btn.closest('.container-input-roll');

        let taskId = container.getAttribute('data-task');
        let pos = container.getAttribute('data-pos');
        let gsmAsli = container.getAttribute('data-gsmasli');
        let gsmBaca = container.getAttribute('data-gsmbaca');

        let depan = baris.querySelector('.inp-depan').value.trim().toUpperCase();
        let middle = baris.querySelector('.inp-middle').value.trim().toUpperCase();
        let belakang = baris.querySelector('.inp-belakang').value.trim().toUpperCase();
        
        // Membaca angka apapun yang diketik oleh Checker!
        let kgValue = parseFloat(baris.querySelector('.inp-kg').value);

        if(!depan || !belakang || !kgValue || isNaN(kgValue)) {
            return alert("Lengkapi kode roll dan pastikan kolom Kg sudah terisi!");
        }

        let fullRoll = depan + middle + belakang;
        btn.innerHTML = "⏳"; 

        fetch('{{ url("/checker/scan/save") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                task_id: taskId, posisi: pos, no_roll: fullRoll, 
                gsm_asli: gsmAsli, gsm_terjemahan: gsmBaca, berat_kg: kgValue
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                baris.outerHTML = `
                    <div class="row g-1 align-items-center mt-1 row-tersimpan">
                        <div class="col-8"><div class="fw-bold text-success">✅ ${fullRoll}</div></div>
                        <div class="col-3 text-end"><span class="fw-bold text-dark">${kgValue} Kg</span></div>
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-sm btn-danger py-0 px-2 fw-bold" onclick="hapusRollDatabase(this, ${data.id})">✖</button>
                        </div>
                    </div>
                `;
            } else {
                alert("Gagal menyimpan data!");
                btn.innerHTML = "💾";
            }
        });
    }

    function hapusRollDatabase(btn, scanId) {
        if(!confirm("Hapus roll ini dari memori?")) return;
        
        let baris = btn.closest('.row-tersimpan');
        btn.innerHTML = "⏳";

        fetch('{{ url("/checker/scan/delete") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ id: scanId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) baris.remove();
        });
    }
</script>

</body>
</html>