<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izin_karyawan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->foreignId('jenis_izin_id')->constrained('jenis_izin')->restrictOnDelete();
            $t->string('nomor')->nullable();
            $t->date('berlaku_mulai')->nullable();
            $t->date('berlaku_akhir');
            $t->timestamps();

            $t->index(['karyawan_id', 'jenis_izin_id', 'berlaku_akhir']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_karyawan');
    }
};
