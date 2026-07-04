<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftV2 extends Model
{
    protected $table = 'shifts_v2';
    protected $fillable = ['tanggal', 'shift', 'kepala_shift', 'checker'];

    public function transaksiRolls() {
        return $this->hasMany(TransaksiRollV2::class, 'shift_v2_id');
    }
}