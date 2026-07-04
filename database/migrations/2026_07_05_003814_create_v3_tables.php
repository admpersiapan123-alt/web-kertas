<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Shift V3
        Schema::create('shifts_v3', function (Blueprint $table) {
            $table->id();
            $table->string('kepala_shift');
            $table->integer('shift_ke'); 
            $table->date('tanggal');
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();
        });

        // 2. Tabel Transaksi Roll V3 (Ada tambahan 'lebar_jalan' !)
        Schema::create('transaksi_roll_v3', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_v3_id')->constrained('shifts_v3')->onDelete('cascade');
            $table->integer('lebar_jalan'); // 👈 INI KUNCI UTAMA V3 (Misal: 150, 180, dll)
            $table->string('no_roll');
            $table->string('posisi_mesin');
            $table->dateTime('waktu_ambil');
            $table->dateTime('waktu_kembali')->nullable();
            $table->double('sisa_kilo_awal')->nullable(); 
            $table->double('sisa_kilo_akhir')->nullable(); 
            $table->enum('status', ['diambil', 'kembali'])->default('diambil');
            $table->string('metode_input'); 
            $table->string('keterangan')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_roll_v3');
        Schema::dropIfExists('shifts_v3');
    }
};