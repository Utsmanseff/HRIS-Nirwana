<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyesuaian_saldo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->date('periode_mulai'); // = anchor + N tahun (menandai periode yang disesuaikan)
            $t->tinyInteger('delta');  // ± hari
            $t->string('alasan');
            $t->foreignId('dibuat_oleh')->constrained('users');
            $t->timestamps();
            $t->index(['karyawan_id', 'periode_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyesuaian_saldo');
    }
};
