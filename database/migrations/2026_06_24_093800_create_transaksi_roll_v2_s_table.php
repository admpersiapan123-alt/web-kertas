<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaksi_rolls_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_v2_id')->constrained('shifts_v2')->onDelete('cascade');
            
            // GANTI INI: Sesuaikan dengan nama tabel asli Anda
            $table->foreignId('stock_kertas_id'); 
            
            // Posisi mesin resmi dihapus untuk V2
            $table->decimal('sisa_kilo_awal', 10, 2)->default(0);
            $table->decimal('sisa_kilo_akhir', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_roll_v2_s');
    }
};
