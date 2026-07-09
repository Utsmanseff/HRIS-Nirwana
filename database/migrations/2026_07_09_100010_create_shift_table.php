<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift', function (Blueprint $t) {
            $t->id();
            $t->foreignId('org_unit_id')->constrained('org_units')->cascadeOnDelete();
            $t->string('nama');
            $t->string('kode', 4);          // diketik di grid: P/SI/SO/M
            $t->string('warna', 9);         // hex #RRGGBB
            $t->time('jam_mulai');
            $t->time('jam_selesai');        // < jam_mulai → lintas hari
            $t->smallInteger('toleransi_telat')->default(10);
            $t->boolean('aktif')->default(true);
            $t->timestamps();

            $t->unique(['org_unit_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift');
    }
};
