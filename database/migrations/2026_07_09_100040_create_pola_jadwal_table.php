<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pola_jadwal', function (Blueprint $t) {
            $t->id();
            $t->foreignId('template_id')->constrained('template_jadwal')->cascadeOnDelete();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->smallInteger('posisi');                 // 0..n-1
            $t->foreignId('shift_id')->nullable()->constrained('shift')->nullOnDelete(); // null = libur
            $t->timestamps();

            $t->unique(['template_id', 'karyawan_id', 'posisi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pola_jadwal');
    }
};
