<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kalkulasi_spks_v2', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sesi')->unique(); // Contoh: AUTOV2-2026...
            $table->foreignId('shift_v2_id')->nullable()->constrained('shifts_v2')->onDelete('cascade');
            
            // Kolom JSON ini akan menyimpan hasil hitungan TEORI KG sebagai rasio pembaginya
            $table->json('data_spk'); 
            
            $table->decimal('total_aktual_semua', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kalkulasi_spk_v2_s');
    }
};
