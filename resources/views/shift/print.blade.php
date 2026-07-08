<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta class="csrf-token" name="csrf-token" content="{{ csrf_token() }}"> 
    <title>Laporan Pemakaian Roll - {{ $shift->kepala_shift }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 12mm 8mm 12mm;
        }
        
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: black;
            background-color: white;
            margin: 0; padding: 0;
        }

        .header-container {
            display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;
        }
        .header-left { width: 30%; font-weight: bold; font-size: 10pt; text-align: left; line-height: 1.4; }
        .header-center { width: 40%; text-align: center; }
        .header-center h2 { margin: 0; font-size: 14pt; text-transform: uppercase; text-decoration: underline; }
        .header-right { width: 30%; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid black; padding: 2px 4px; text-align: center; vertical-align: middle; }
        th { background-color: #f2f2f2 !important; font-weight: bold; font-size: 9pt; text-transform: uppercase; height: 24px; }
        td { font-size: 9pt; height: 19px; }

        .ttd-container { display: flex; justify-content: flex-end; margin-top: 5px; }
        .ttd-box { text-align: center; width: 250px; }
        .ttd-title { font-weight: bold; font-size: 10pt; margin-bottom: 40px; }
        .ttd-name { font-weight: bold; font-size: 10pt; }

        .action-buttons {
            display: flex; justify-content: center; gap: 15px; margin: 15px auto; flex-wrap: wrap;
        }
        .btn-action {
            padding: 10px 20px; text-align: center; color: white;
            font-weight: bold; font-size: 11pt; border-radius: 5px; cursor: pointer; border: 2px solid #000;
            transition: 0.2s;
        }
        .btn-print { background-color: #0d6efd; }
        .btn-excel { background-color: #198754; }
        .btn-lock { background-color: #dc3545; } 
        .btn-unlock { background-color: #ffc107; color: black; } 

        /* CSS INPUT SILUMAN */
        .input-sisa, .input-noroll {
            width: 100%; border: none; text-align: center; font-size: 9pt;
            font-family: inherit; background-color: #fff3cd; outline: none;
            font-weight: bold; transition: 0.3s;
        }
        .input-sisa[readonly], .input-noroll[readonly] {
            background-color: transparent;
            cursor: not-allowed;
        }
        .input-sisa:focus:not([readonly]), .input-noroll:focus:not([readonly]) {
            background-color: #e7f1ff; border-bottom: 1px solid #0d6efd;
        }
        .input-sisa.tersimpan, .input-noroll.tersimpan {
            background-color: transparent !important;
        }
        
        .d-none { display: none !important; }

        .btn-delete-roll {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: bold;
            cursor: pointer;
            margin-left: 4px;
            transition: 0.2s;
        }
        .btn-delete-roll:hover {
            background-color: #b02a37;
        }

        /* CSS TOMBOL MELAYANG KIRI BAWAH */
        .btn-float-left {
            position: fixed;
            bottom: 25px;
            left: 25px;
            background-color: #6f42c1;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 11pt;
            cursor: pointer;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.4);
            z-index: 999;
            transition: 0.3s;
        }
        .btn-float-left:hover {
            background-color: #59339d;
            transform: scale(1.05);
        }
        @media print {
            .btn-float-left { display: none !important; }
        }

        @media print {
            .action-buttons { display: none; }
            .input-sisa, .input-noroll { background-color: transparent !important; border: none !important; }
            .select-posisi { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Simpan ID shift saat ini untuk referensi penambahan data baru via AJAX -->
    <input type="hidden" id="shift-id-global" value="{{ $shift->id }}">

    <div class="action-buttons">
        <button class="btn-action btn-print" onclick="window.print()">🖨️ CETAK KE KERTAS</button>
        <button class="btn-action btn-excel" onclick="exportToCSV('Laporan_Pemakaian_Roll.csv')">📊 EXPORT EXCEL/CSV</button>
        <button id="btn-toggle-lock" class="btn-action btn-lock" onclick="toggleLock()">🔒 KONDISI TERKUNCI</button>
    </div>

    @php
        $chunks = $transaksi->chunk(23);
        $totalHalaman = $chunks->count() > 0 ? $chunks->count() : 1;
    @endphp

    @forelse($chunks as $index => $chunk)
    <div style="{{ $index > 0 ? 'page-break-before: always; padding-top: 10px;' : '' }}">

        <div class="header-container">
            <div class="header-left">
                <div>Checker : {{ $shift->kepala_shift }} (Shift {{ $shift->shift_ke ?? '-' }})</div>
                <div>Tanggal : {{ date('d-M-Y', strtotime($shift->tanggal)) }}</div>
            </div>
            <div class="header-center">
                <h2>LAPORAN PEMAKAIAN KERTAS ROLL</h2>
            </div>
            <div class="header-right" style="text-align: right; font-weight: bold; font-size: 11pt;">
                @if($totalHalaman > 1)
                    Halaman {{ $index + 1 }} / {{ $totalHalaman }}
                @endif
            </div>
        </div>

        <table class="tabel-laporan">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 8%;">NO SPK</th>
                    <th rowspan="2" style="width: 15%;">NO ROLL</th>
                    <th rowspan="2" style="width: 8%;">GRAMATUR<br>(GSM)</th>
                    <th rowspan="2" style="width: 7%;">LEBAR</th>
                    <th colspan="5">BERAT AWAL (KG)</th>
                    <th rowspan="2" style="width: 8%;">BERAT<br>PAKAI</th>
                    <th rowspan="2" style="width: 8%;">BERAT<br>SISA</th>
                </tr>
                <tr>
                    <th style="width: 8%;">DB</th>
                    <th style="width: 9%;">BM/Gel BF</th>
                    <th style="width: 9%;">BL/Lap BF</th>
                    <th style="width: 9%;">CM/Gel CF</th>
                    <th style="width: 9%;">CL/Lap CF</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $dataCount = $chunk->count();
                    $emptyRows = 23 - $dataCount;
                @endphp

                <!-- 1. BARIS DATA YANG SUDAH ADA DI DATABASE -->
                @foreach($chunk as $t)
                {{-- TAMBAHKAN BARIS INI: Cek apakah no_roll ada isinya --}}
                @if(!empty($t->no_roll))
                    <tr id="baris-{{ $t->id }}">
                        <td></td> 
                        <td style="padding: 0; font-weight: bold;">
                            <div style="display: flex; align-items: center; justify-content: center;">
                                <input type="text" 
                                    class="input-noroll tersimpan" 
                                    data-id="{{ $t->id }}"
                                    value="{{ $t->no_roll ?? '' }}"
                                    onchange="handleRollInput(this)"
                                    style="flex-grow: 1;"
                                    readonly>
                                
                                <button class="btn-delete-roll d-none" data-id="{{ $t->id }}" onclick="hapusRollLangsung(this)" title="Batal Roll">✖</button>
                            </div>
                            
                            <select class="select-posisi d-none" 
                                    data-id="{{ $t->id }}" 
                                    onchange="movePosisiSistem(this)"
                                    style="width: 100%; font-size: 8pt; font-weight: bold; background-color: #e7f1ff; border: 1px solid #0d6efd; text-align: center;">
                                <option value="DB" {{ $t->posisi_mesin == 'DB' ? 'selected' : '' }}>DB</option>
                                <option value="BM" {{ $t->posisi_mesin == 'BM' ? 'selected' : '' }}>BM/Gel BF</option>
                                <option value="BL" {{ $t->posisi_mesin == 'BL' ? 'selected' : '' }}>BL/Lap BF</option>
                                <option value="CM" {{ $t->posisi_mesin == 'CM' ? 'selected' : '' }}>CM/Gel CF</option>
                                <option value="CL" {{ $t->posisi_mesin == 'CL' ? 'selected' : '' }}>CL/Lap CF</option>
                            </select>
                        </td>

                        <td id="gsm-{{ $t->id }}">{{ $t->masterKertas->gsm ?? '-' }}</td>
                        <td id="lebar-{{ $t->id }}">{{ $t->masterKertas->lebar ?? '-' }}</td>
                        
                        <td id="pos-DB-{{ $t->id }}">{{ $t->posisi_mesin == 'DB' ? $t->sisa_kilo_awal : '' }}</td>
                        <td id="pos-BM-{{ $t->id }}">{{ $t->posisi_mesin == 'BM' ? $t->sisa_kilo_awal : '' }}</td>
                        <td id="pos-BL-{{ $t->id }}">{{ $t->posisi_mesin == 'BL' ? $t->sisa_kilo_awal : '' }}</td>
                        <td id="pos-CM-{{ $t->id }}">{{ $t->posisi_mesin == 'CM' ? $t->sisa_kilo_awal : '' }}</td>
                        <td id="pos-CL-{{ $t->id }}">{{ $t->posisi_mesin == 'CL' ? $t->sisa_kilo_awal : '' }}</td>
                        
                        <td>
                            <span id="pakai-{{ $t->id }}">
                                @if(isset($t->sisa_kilo_akhir) && is_numeric($t->sisa_kilo_awal) && is_numeric($t->sisa_kilo_akhir))
                                    {{ $t->sisa_kilo_awal - $t->sisa_kilo_akhir }}
                                @endif
                            </span>
                        </td> 
                        
                        <td style="padding: 0;">
                            <input type="number" 
                                class="input-sisa {{ isset($t->sisa_kilo_akhir) ? 'tersimpan' : '' }}" 
                                data-id="{{ $t->id }}"
                                data-awal="{{ $t->sisa_kilo_awal }}"
                                value="{{ $t->sisa_kilo_akhir ?? '' }}"
                                onchange="updateSisa(this)"
                                readonly> 
                        </td> 
                    </tr>
                {{-- TAMBAHKAN BARIS INI: Penutup pengecekan --}}
                @endif
            @endforeach
                <!-- 2. BARIS KOSONG YANG SEKARANG DIUPGRADE JADI SIAP KETIK -->
                @for ($i = 0; $i < $emptyRows; $i++)
                @php $tempId = "new-" . $index . "-" . $i; @endphp
                <tr id="baris-{{ $tempId }}">
                    <td></td>
                    <td style="padding: 0; font-weight: bold;">
                        
                        <div style="display: flex; align-items: center; justify-content: center; width: 100%;">
                            <input type="text" 
                                   class="input-noroll" 
                                   data-id="" 
                                   data-temp="{{ $tempId }}"
                                   value=""
                                   onchange="handleRollInput(this)"
                                   placeholder=""
                                   style="flex-grow: 1;"
                                   readonly>
                            
                            <button class="btn-delete-roll d-none" data-id="" onclick="hapusRollLangsung(this)" title="Batal Roll">✖</button>
                        </div>
                        <select class="select-posisi d-none" 
                                data-id="" 
                                data-temp="{{ $tempId }}"
                                onchange="movePosisiSistem(this)"
                                style="width: 100%; font-size: 8pt; font-weight: bold; background-color: #e7f1ff; border: 1px solid #0d6efd; text-align: center;">
                            <option value="DB">DB</option>
                            <option value="BM">BM/Gel BF</option>
                            <option value="BL">BL/Lap BF</option>
                            <option value="CM">CM/Gel CF</option>
                            <option value="CL">CL/Lap CF</option>
                        </select>
                    </td>
                    <td id="gsm-{{ $tempId }}">-</td>
                    <td id="lebar-{{ $tempId }}">-</td>
                    
                    <td id="pos-DB-{{ $tempId }}"></td>
                    <td id="pos-BM-{{ $tempId }}"></td>
                    <td id="pos-BL-{{ $tempId }}"></td>
                    <td id="pos-CM-{{ $tempId }}"></td>
                    <td id="pos-CL-{{ $tempId }}"></td>
                    
                    <td><span id="pakai-{{ $tempId }}"></span></td>
                    
                    <td style="padding: 0;">
                        <input type="number" 
                               class="input-sisa" 
                               data-id="" 
                               data-temp="{{ $tempId }}"
                               data-awal=""
                               value=""
                               onchange="updateSisa(this)"
                               readonly>
                    </td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="ttd-container">
            <div class="ttd-box">
                <div class="ttd-title">Dibuat Oleh,</div>
                <div class="ttd-name">( ........................................ )</div>
            </div>
        </div>

    </div>
    @empty
        <div class="text-center" style="margin-top: 50px;">
            <h3>BELUM ADA TRANSAKSI DI SHIFT INI</h3>
        </div>
    @endforelse

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // 1. BACA MEMORI BROWSER: Apakah sebelumnya terbuka atau terkunci?
        // Default: true (terkunci), kecuali sessionStorage mencatat 'false'
        let isLocked = sessionStorage.getItem('laporan_locked') === 'false' ? false : true; 

        // 2. OTOMATIS BUKA KUNCI SAAT HALAMAN DIMUAT (JIKA STATUSNYA TERBUKA)
        document.addEventListener("DOMContentLoaded", function() {
            if (!isLocked) {
                isLocked = true; // Set sementara ke true, agar saat toggleLock dipanggil, dia berubah jadi false (terbuka)
                toggleLock();
            }
        });

        function toggleLock() {
            isLocked = !isLocked;
            sessionStorage.setItem('laporan_locked', isLocked);

            let btnLock = document.getElementById('btn-toggle-lock');
            let inputsSisa = document.querySelectorAll('.input-sisa');
            let inputsNoRoll = document.querySelectorAll('.input-noroll');
            let selectsPosisi = document.querySelectorAll('.select-posisi');
            let btnsDelete = document.querySelectorAll('.btn-delete-roll');

            if (isLocked) {
                btnLock.innerHTML = "🔒 KONDISI TERKUNCI";
                btnLock.className = "btn-action btn-lock";
                
                inputsSisa.forEach(input => input.setAttribute('readonly', true));
                inputsNoRoll.forEach(input => {
                    input.setAttribute('readonly', true);
                    input.placeholder = ""; 
                });
                selectsPosisi.forEach(select => select.classList.add('d-none'));
                btnsDelete.forEach(btn => btn.classList.add('d-none')); // Sembunyikan semua tombol silang
            } else {
                btnLock.innerHTML = "🔓 KONDISI TERBUKA (BISA EDIT & TAMBAH ROLL)";
                btnLock.className = "btn-action btn-unlock";
                
                inputsSisa.forEach(input => input.removeAttribute('readonly'));
                inputsNoRoll.forEach(input => {
                    input.removeAttribute('readonly');
                    if(input.value === "") input.placeholder = "+ Ketik Roll";
                });
                selectsPosisi.forEach(select => select.classList.remove('d-none'));
                
                // Munculkan tombol silang HANYA jika barisnya sudah ada ID roll-nya
                btnsDelete.forEach(btn => {
                    if (btn.getAttribute('data-id') && btn.getAttribute('data-id') !== "") {
                        btn.classList.remove('d-none');
                    }
                });
            }
        }

        // ==========================================
        // FUNGSI PINTAR: TAMBAH ROLL TANPA RELOAD
        // ==========================================
        function handleRollInput(input) {
            // 1. OTOMATIS UBAH KE HURUF BESAR! (Gak perlu repot benerin typo huruf kecil lagi)
            let noRoll = input.value.trim().toUpperCase();
            input.value = noRoll; 

            if (noRoll === "") return;

            // 2. CEK RIWAYAT KETIKAN (Biar gak nembak server 2 kali kalau teksnya sama)
            let lastVal = input.getAttribute('data-last-val');
            if (lastVal === noRoll) return; 
            
            // 3. CEK APAKAH BARIS INI SUDAH TERSIMPAN DI DB
            let existingId = input.getAttribute('data-id');
            if (existingId && existingId !== "") {
                alert("Roll ini sudah tercatat! Jika salah ketik nomor roll, mohon klik tombol silang (✖) untuk membatalkan, lalu ketik ulang di baris kosong.");
                input.value = lastVal; // Kembalikan ke teks sebelumnya agar aman
                return;
            }

            // Simpan jejak ketikan terakhir
            input.setAttribute('data-last-val', noRoll);

            let tr = input.closest('tr');
            let selectPosisi = tr ? tr.querySelector('.select-posisi') : null;
            let posisi = selectPosisi ? selectPosisi.value : 'DB';
            let tempId = input.getAttribute('data-temp');

            input.style.backgroundColor = '#fff3cd';

            fetch('{{ url("/shift/transaksi/tambah-roll-langsung") }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: JSON.stringify({
                    shift_id: document.getElementById('shift-id-global').value,
                    no_roll: noRoll,
                    posisi_mesin: posisi
                })
            })
            .then(res => {
                if (!res.ok) throw new Error("Gagal menyimpan ke server");
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    input.style.backgroundColor = '#d1e7dd'; 
                    let newId = data.new_id;
                    
                    input.setAttribute('data-id', newId);
                    input.classList.add('tersimpan');
                    
                    if(selectPosisi) selectPosisi.setAttribute('data-id', newId);
                    
                    let inputSisa = tr.querySelector('.input-sisa');
                    if(inputSisa) {
                        inputSisa.setAttribute('data-id', newId);
                        inputSisa.setAttribute('data-awal', data.sisa_kilo_awal);
                    }

                    let tdGsm = document.getElementById('gsm-' + tempId);
                    let tdLebar = document.getElementById('lebar-' + tempId);
                    if(tdGsm) { tdGsm.innerText = data.gsm; tdGsm.id = 'gsm-' + newId; }
                    if(tdLebar) { tdLebar.innerText = data.lebar; tdLebar.id = 'lebar-' + newId; }

                    let tdPos = document.getElementById('pos-' + posisi + '-' + tempId);
                    if(tdPos) tdPos.innerText = data.sisa_kilo_awal;
                    
                    ['DB', 'BM', 'BL', 'CM', 'CL'].forEach(p => {
                        let tdP = document.getElementById('pos-' + p + '-' + tempId);
                        if(tdP) tdP.id = 'pos-' + p + '-' + newId;
                    });
                    
                    let tdPakai = document.getElementById('pakai-' + tempId);
                    if(tdPakai) tdPakai.id = 'pakai-' + newId;

                    let btnHapus = input.parentElement.querySelector('.btn-delete-roll');
                    if (btnHapus) {
                        btnHapus.setAttribute('data-id', newId);
                        if (!isLocked) btnHapus.classList.remove('d-none');
                    }

                } else {
                    alert(data.message || "Gagal menambah roll!");
                    input.style.backgroundColor = '#f8d7da';
                    input.removeAttribute('data-last-val'); // Reset kalau gagal
                }
            })
            .catch(err => {
                console.error(err);
                alert("Terjadi kesalahan sistem saat menambah roll!");
                input.style.backgroundColor = '#f8d7da';
                input.removeAttribute('data-last-val'); // Reset kalau gagal
            });
        }

        // FUNGSI BARU UNTUK BATAL ROLL
        function hapusRollLangsung(btn) {
            let id = btn.getAttribute('data-id');
            if(!id) return;

            if(!confirm("Yakin ingin membatalkan dan menghapus Roll ini dari laporan? Stok master akan dikembalikan jika sebelumnya sudah diinput sisa.")) {
                return;
            }

            let tr = btn.closest('tr');
            tr.style.opacity = '0.5'; // Efek visual sedang proses

            fetch('{{ url("/shift/transaksi/batal-roll-ajax") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    tr.style.backgroundColor = '#f8d7da'; // Warna merah sebentar sebelum reload
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                } else {
                    alert(data.message || "Gagal menghapus roll!");
                    tr.style.opacity = '1';
                }
            })
            .catch(err => {
                console.error(err);
                alert("Terjadi kesalahan sistem saat menghubungi server!");
                tr.style.opacity = '1';
            });
        }

       

        // ==========================================
        // 3. FITUR PINDAH 5 POSISI SECARA LIVE
        // ==========================================
        function movePosisiSistem(select) {
            let id = select.getAttribute('data-id');
            if (!id || id === "") {
                alert("Silakan ketik NO ROLL terlebih dahulu sebelum mengganti posisi mesin!");
                return;
            }

            let posisiMesin = select.value;
            let inputNoRoll = select.closest('tr').querySelector('.input-noroll');
            let noRoll = inputNoRoll.value.trim();
            let inputSisa = document.querySelector(`.input-sisa[data-id="${id}"]`);
            let beratAwal = parseFloat(inputSisa.getAttribute('data-awal'));

            if (noRoll === "") return;

            ['DB', 'BM', 'BL', 'CM', 'CL'].forEach(p => {
                document.getElementById(`pos-${p}-${id}`).innerText = '';
            });

            if (!isNaN(beratAwal)) {
                document.getElementById(`pos-${posisiMesin}-${id}`).innerText = beratAwal;
            }

            syncRollDanPosisiToDatabase(id, noRoll, isNaN(beratAwal) ? 0 : beratAwal, posisiMesin);
        }

        function syncRollDanPosisiToDatabase(id, noRoll, beratAwal, posisiMesin) {
            fetch('{{ url("/shift/transaksi/update-roll-posisi") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    id: id,
                    no_roll: noRoll,
                    sisa_kilo_awal: beratAwal,
                    posisi_mesin: posisiMesin
                })
            });
        }

        function updateSisa(input) {
            let id = input.getAttribute('data-id');
            if (!id || id === "") {
                alert("Silakan ketik NO ROLL terlebih dahulu!");
                return;
            }

            let kiloAwal = parseFloat(input.getAttribute('data-awal'));
            let kiloAkhir = parseFloat(input.value);
            let spanPakai = document.getElementById('pakai-' + id);

            if (isNaN(kiloAkhir)) return;

            if (!isNaN(kiloAwal)) {
                spanPakai.innerText = (kiloAwal - kiloAkhir).toFixed(2);
            }

            input.style.backgroundColor = '#fff3cd';

            fetch('{{ url("/shift/transaksi/update-sisa") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id: id, sisa_kilo_akhir: kiloAkhir })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    input.style.backgroundColor = '#d1e7dd';
                } else {
                    alert("Gagal menyimpan data!");
                    input.style.backgroundColor = '#f8d7da';
                }
            });
        }

        function exportToCSV(filename) {
            let csv = [];
            csv.push("NO SPK;NO ROLL;GRAMATUR (GSM);LEBAR;DB;BM;BL;CM;CL;BERAT PAKAI;BERAT SISA");
            let rows = document.querySelectorAll("tbody tr");

            for (let i = 0; i < rows.length; i++) {
                let row = [];
                let cols = rows[i].querySelectorAll("td");
                if(cols.length < 11) continue;

                let inputNoRoll = cols[1].querySelector(".input-noroll");
                let valNoRoll = inputNoRoll ? inputNoRoll.value.trim() : "";
                if (valNoRoll === "") continue;

                for (let j = 0; j < cols.length; j++) {
                    let cellData = "";
                    if (j === 1) cellData = valNoRoll;
                    else if (j === 10) {
                        let input = cols[j].querySelector("input");
                        cellData = input ? input.value : "";
                    } else {
                        cellData = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    }
                    row.push('"' + cellData + '"');
                }
                csv.push(row.join(";"));
            }
            downloadCSVFile(csv.join("\n"), filename);
        }

        function downloadCSVFile(csv, filename) {
            let csvFile = new Blob([csv], {type: "text/csv"});
            let downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>