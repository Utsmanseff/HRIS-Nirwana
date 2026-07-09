<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_absensi', function (Blueprint $t) {
            $t->id();
            $t->decimal('office_lat', 10, 7);
            $t->decimal('office_long', 10, 7);
            $t->smallInteger('radius_m')->default(100);
            $t->smallInteger('max_akurasi_m')->default(30);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_absensi');
    }
};
