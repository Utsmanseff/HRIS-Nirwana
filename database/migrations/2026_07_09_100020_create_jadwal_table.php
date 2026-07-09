<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->date('tanggal');
            $t->foreignId('shift_id')->constrained('shift')->cascadeOnDelete();
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['karyawan_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};
