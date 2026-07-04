<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiRollV2 extends Model
{
    protected $table = 'transaksi_rolls_v2';
    
    // Pastikan stock_kerta_id masuk ke fillable
    protected $guarded = [];

    public function shiftV2() {
        return $this->belongsTo(ShiftV2::class, 'shift_v2_id');
    }

    // GANTI INI: Relasikan ke model StockKerta asli Anda
    public function stockKertas() {
        // Sesuaikan dengan nama Model asli Anda (misal: StockKertas atau StockKerta)
        return $this->belongsTo(StockKertas::class, 'stock_kertas_id');
    }
}