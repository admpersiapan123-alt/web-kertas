<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shifts_v2', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('shift'); // Contoh: Shift 1, Shift 2
            $table->string('kepala_shift')->nullable();
            $table->string('checker')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_v2_s');
    }
};
