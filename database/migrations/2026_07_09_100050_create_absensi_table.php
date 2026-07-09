<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->date('tanggal_kerja');                       // anchor = tanggal masuk
            $t->foreignId('shift_id')->nullable()->constrained('shift')->nullOnDelete();
            // snapshot shift saat masuk (null = mode catat):
            $t->string('shift_nama')->nullable();
            $t->time('shift_mulai')->nullable();
            $t->time('shift_selesai')->nullable();
            $t->smallInteger('shift_toleransi')->nullable();
            // masuk:
            $t->dateTime('jam_masuk');
            $t->string('foto_masuk_path')->nullable();       // webp; null bila dihapus retensi
            $t->decimal('lat_masuk', 10, 7);
            $t->decimal('long_masuk', 10, 7);
            $t->float('akurasi_masuk');
            $t->boolean('wajah_verif_masuk')->default(true);
            $t->json('flag_lokasi_masuk')->nullable();
            // pulang (null = sesi aktif):
            $t->dateTime('jam_pulang')->nullable();
            $t->string('foto_pulang_path')->nullable();
            $t->decimal('lat_pulang', 10, 7)->nullable();
            $t->decimal('long_pulang', 10, 7)->nullable();
            $t->float('akurasi_pulang')->nullable();
            $t->boolean('wajah_verif_pulang')->nullable();
            $t->json('flag_lokasi_pulang')->nullable();
            // evaluasi:
            $t->smallInteger('telat_menit')->nullable();
            $t->smallInteger('pulang_cepat_menit')->nullable();
            $t->timestamps();

            $t->index(['karyawan_id', 'tanggal_kerja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
