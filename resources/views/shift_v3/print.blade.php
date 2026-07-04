<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laporan Roll (Track Per Lebar) - {{ $shift->kepala_shift }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 12mm 8mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; color: black; background-color: white; margin: 0; padding: 0; }

        .header-container { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px; }
        .header-left { width: 30%; font-weight: bold; font-size: 10pt; line-height: 1.4; }
        .header-center { width: 40%; text-align: center; }
        .header-center h2 { margin: 0; font-size: 14pt; text-transform: uppercase; text-decoration: underline; }
        .header-right { width: 30%; text-align: right; font-weight: bold; font-size: 11pt; }

        /* Judul Per Lebar */
        .title-lebar { 
            background-color: #0d6efd; color: white; padding: 4px 10px; font-size: 10pt; 
            font-weight: bold; border-radius: 4px 4px 0 0; border: 1px solid black; border-bottom: none;
            display: inline-block; margin-top: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid black; padding: 2px 4px; text-align: center; vertical-align: middle; }
        th { background-color: #f2f2f2 !important; font-weight: bold; font-size: 9pt; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .ttd-container { display: flex; justify-content: flex-end; margin-top: 15px; }
        .ttd-box { text-align: center; width: 250px; }
        .ttd-title { font-weight: bold; font-size: 10pt; margin-bottom: 40px; }

        .btn-print { display: block; width: 250px; margin: 10px auto; padding: 10px; text-align: center; background-color: #0d6efd; color: white; font-weight: bold; font-size: 11pt; border-radius: 5px; cursor: pointer; border: 2px solid #000; }
        
        /* CSS INPUT SILUMAN */
        .input-sisa { width: 100%; border: none; text-align: center; font-size: 9pt; font-family: inherit; background-color: #fff3cd; outline: none; font-weight: bold; transition: 0.3s; }
        .input-sisa:focus { background-color: #e7f1ff; border-bottom: 1px solid #0d6efd; }
        .input-sisa.tersimpan { background-color: transparent !important; }

        @media print {
            .btn-print { display: none; }
            .input-sisa { background-color: transparent !important; border: none !important; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ CETAK LAPORAN V3</button>

    <div class="header-container">
        <div class="header-left">
            <div>Checker : {{ $shift->kepala_shift }} (Shift {{ $shift->shift_ke ?? '-' }})</div>
            <div>Tanggal : {{ date('d-M-Y', strtotime($shift->tanggal)) }}</div>
        </div>
        <div class="header-center">
            <h2>LAPORAN PEMAKAIAN ROLL (TRACK PER LEBAR)</h2>
        </div>
        <div class="header-right">
            <span>Form Laporan V3</span>
        </div>
    </div>

    @forelse($groupedTransaksi as $lebarJalan => $transaksiList)
        <div>
            <div class="title-lebar">TRACK LEBAR: {{ $lebarJalan }} CM</div>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 15%;">NO ROLL</th>
                        <th rowspan="2" style="width: 8%;">GSM</th>
                        <th rowspan="2" style="width: 7%;">LEBAR FISIK</th>
                        <th colspan="5">BERAT AWAL (KG)</th>
                        <th rowspan="2" style="width: 8%;">PAKAI</th>
                        <th rowspan="2" style="width: 8%;">SISA</th>
                    </tr>
                    <tr>
                        <th style="width: 8%;">DB</th>
                        <th style="width: 9%;">BM</th>
                        <th style="width: 9%;">BL</th>
                        <th style="width: 9%;">CM</th>
                        <th style="width: 9%;">CL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaksiList as $t)
                    <tr>
                        <td style="font-weight: bold;">{{ $t->no_roll }}</td>
                        <td>{{ $t->gsm ?? '-' }}</td>
                        <td style="font-weight: bold; color: {{ ($t->lebar_master != $lebarJalan) ? 'red' : 'black' }};">
                            {{ $t->lebar_master ?? '-' }} 
                            {!! ($t->lebar_master != $lebarJalan) ? '⚠️' : '' !!}
                        </td>
                        
                        <td>{{ $t->posisi_mesin == 'DB' ? $t->sisa_kilo_awal : '' }}</td>
                        <td>{{ $t->posisi_mesin == 'BM' ? $t->sisa_kilo_awal : '' }}</td>
                        <td>{{ $t->posisi_mesin == 'BL' ? $t->sisa_kilo_awal : '' }}</td>
                        <td>{{ $t->posisi_mesin == 'CM' ? $t->sisa_kilo_awal : '' }}</td>
                        <td>{{ $t->posisi_mesin == 'CL' ? $t->sisa_kilo_awal : '' }}</td>
                        
                        <td>
                            <span id="pakai-{{ $t->id }}">
                                @if(isset($t->sisa_kilo_akhir) && is_numeric($t->sisa_kilo_awal) && is_numeric($t->sisa_kilo_akhir))
                                    {{ number_format($t->sisa_kilo_awal - $t->sisa_kilo_akhir, 2) }}
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
                                   title="Ketik sisa akhir lalu tekan Tab/Enter">
                        </td> 
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="text-center" style="margin-top: 50px;">
            <h3>BELUM ADA TRANSAKSI DI SHIFT INI</h3>
        </div>
    @endforelse

    @if(count($groupedTransaksi) > 0)
    <div class="ttd-container">
        <div class="ttd-box">
            <div class="ttd-title">Dibuat Oleh,</div>
            <div class="ttd-name">( ........................................ )</div>
        </div>
    </div>
    @endif

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function updateSisa(input) {
            let id = input.getAttribute('data-id');
            let kiloAwal = parseFloat(input.getAttribute('data-awal'));
            let kiloAkhir = parseFloat(input.value);
            let spanPakai = document.getElementById('pakai-' + id);

            if (isNaN(kiloAkhir)) return;

            if (!isNaN(kiloAwal)) {
                let terpakai = kiloAwal - kiloAkhir;
                spanPakai.innerText = terpakai.toFixed(2);
            }

            input.style.backgroundColor = '#fff3cd';

            fetch('{{ url("/shift-v3/transaksi/update-sisa") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ id: id, sisa_kilo_akhir: kiloAkhir })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    input.style.backgroundColor = '#d1e7dd';
                    setTimeout(() => { input.classList.add('tersimpan'); }, 500);
                } else {
                    alert("Gagal menyimpan data!");
                    input.style.backgroundColor = '#f8d7da';
                }
            })
            .catch(err => {
                alert("Terjadi kesalahan, cek koneksi / refresh halaman!");
                input.style.backgroundColor = '#f8d7da';
            });
        }
    </script>
</body>
</html>