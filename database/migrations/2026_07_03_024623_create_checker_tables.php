<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // TABEL 1: Menyimpan Data Tugas Planning per Lebar Jalan
        Schema::create('checker_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tugas')->unique(); // cth: CHK-180-20260703
            $table->integer('lebar_cm');
            $table->json('data_spk'); // Menyimpan JSON SPK yang jalan di lebar ini
            $table->json('target_kebutuhan'); // Menyimpan rincian target Kg per Gramatur
            $table->enum('status', ['MENUNGGU', 'PROSES', 'SELESAI'])->default('MENUNGGU');
            $table->timestamps();
        });

        // TABEL 2: Menyimpan Roll Fisik yang di-Scan oleh Checker (Realisasi)
        Schema::create('checker_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checker_task_id')->constrained('checker_tasks')->onDelete('cascade');
            $table->string('posisi'); // TAMBAHAN BARU: db, bm, bl...
            $table->string('no_roll');
            $table->string('gsm_asli'); 
            $table->string('gsm_terjemahan'); 
            $table->decimal('berat_kg', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('checker_scans');
        Schema::dropIfExists('checker_tasks');
    }
};