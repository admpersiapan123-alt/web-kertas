<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemakaian Roll - {{ $shift->kepala_shift }}</title>
    <style>
        /* Pengaturan Kertas A4 Landscape dengan Margin Diperkecil */
        @page {
            size: A4 landscape;
            margin: 8mm 12mm 8mm 12mm; /* Diperkecil agar ruang tabel lebih luas */
        }
        
        * {
            box-sizing: border-box; /* Mencegah padding & border menambah dimensi elemen */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt; /* Ukuran dasar dikecilkan */
            color: black;
            background-color: white;
            margin: 0;
            padding: 0;
        }

        /* Layout Kop Surat / Header */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 8px; /* Jarak bawah diperkecil */
        }
        
        /* Posisi Checker dan Tanggal di Kiri Atas */
        .header-left {
            width: 30%;
            font-weight: bold;
            font-size: 10pt; /* Dikecilkan */
            text-align: left;
            line-height: 1.4;
        }
        
        /* Judul Laporan di Tengah */
        .header-center {
            width: 40%;
            text-align: center;
        }
        .header-center h2 {
            margin: 0;
            font-size: 14pt; /* Dikecilkan */
            text-transform: uppercase;
            text-decoration: underline;
        }
        
        /* Ruang kosong di kanan agar judul simetris */
        .header-right {
            width: 30%;
        }

        /* Desain Tabel Pabrik */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px; /* Jarak bawah diperkecil */
        }
        th, td {
            border: 1px solid black;
            padding: 2px 4px; 
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f2f2f2 !important; 
            font-weight: bold;
            font-size: 9pt; /* Dikecilkan */
            text-transform: uppercase;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
            height: 24px; /* Tinggi header tabel dikecilkan */
        }
        td {
            font-size: 9pt; /* Dikecilkan */
            height: 19px; /* Dikunci lebih rapat agar 23 baris pasti muat 1 halaman */
        }

        /* Bagian Tanda Tangan Kanan Bawah */
        .ttd-container {
            display: flex;
            justify-content: flex-end; 
            margin-top: 5px;
        }
        .ttd-box {
            text-align: center;
            width: 250px;
        }
        .ttd-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 40px; /* Ruang coretan tanda tangan sedikit dikurangi */
        }
        .ttd-name {
            font-weight: bold;
            font-size: 10pt;
        }

        /* Tombol Bantuan Cetak */
        .btn-print {
            display: block;
            width: 250px;
            margin: 10px auto 15px auto;
            padding: 10px;
            text-align: center;
            background-color: #0d6efd;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 11pt;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid #000;
        }
        @media print {
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ CETAK KE KERTAS SEKARANG</button>

    @php
        // Pecah total data menjadi array-array kecil berisi maksimal 23 data
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

        <table>
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

                @foreach($chunk as $t)
                <tr>
                    <td></td> 
                    <td style="font-weight: bold;">{{ $t->no_roll }}</td>
                    <td>{{ $t->masterKertas->gsm ?? '-' }}</td>
                    <td>{{ $t->masterKertas->lebar ?? '-' }}</td>
                    
                    <td>{{ $t->posisi_mesin == 'DB' ? $t->sisa_kilo_awal : '' }}</td>
                    <td>{{ $t->posisi_mesin == 'BM' ? $t->sisa_kilo_awal : '' }}</td>
                    <td>{{ $t->posisi_mesin == 'BL' ? $t->sisa_kilo_awal : '' }}</td>
                    <td>{{ $t->posisi_mesin == 'CM' ? $t->sisa_kilo_awal : '' }}</td>
                    <td>{{ $t->posisi_mesin == 'CL' ? $t->sisa_kilo_awal : '' }}</td>
                    
                    <td>
                        @if(isset($t->sisa_kilo_akhir) && is_numeric($t->sisa_kilo_awal) && is_numeric($t->sisa_kilo_akhir))
                            {{ $t->sisa_kilo_awal - $t->sisa_kilo_akhir }}
                        @endif
                    </td> 
                    
                    <td>{{ $t->sisa_kilo_akhir ?? '' }}</td> 
                </tr>
                @endforeach

                @for ($i = 0; $i < $emptyRows; $i++)
                <tr>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
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
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>