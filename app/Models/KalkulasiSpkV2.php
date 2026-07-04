<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KalkulasiSpkV2 extends Model
{
    use HasFactory;

    // 1. Kasih tahu Laravel nama tabel aslinya
    protected $table = 'kalkulasi_spks_v2';

    // 2. INI DIA OBATNYA: Daftarkan kolom yang diizinkan untuk diisi otomatis!
    protected $fillable = [
        'kode_sesi', 
        'shift_v2_id', 
        'data_spk', 
        'total_aktual_semua'
    ];
    
    // 3. Casting agar kolom JSON otomatis dikonversi jadi Array di Laravel
    protected $casts = [
        'data_spk' => 'array',
    ];
}