<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->enum('jenis', ['percobaan_unpaid', 'percobaan', 'pkwt', 'tetap']);
            $t->date('tanggal_mulai');
            $t->date('tanggal_akhir')->nullable();
            $t->string('keterangan')->nullable();
            $t->string('dokumen_path')->nullable();
            $t->timestamps();
            $t->index(['karyawan_id', 'tanggal_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak');
    }
};
