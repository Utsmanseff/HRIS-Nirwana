<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pemeliharaan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $t->string('nama');
            $t->unsignedInteger('interval_bulan');
            $t->date('terakhir_dilakukan')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pemeliharaan');
    }
};
