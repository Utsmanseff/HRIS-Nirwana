<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiket', function (Blueprint $t) {
            $t->id();
            $t->string('nomor')->unique();
            $t->string('jenis')->default('perbaikan');   // enum JenisTiket
            $t->string('tim');                            // enum TimTeknis
            $t->foreignId('inventaris_id')->nullable()->constrained('aset')->nullOnDelete();
            $t->foreignId('jadwal_pemeliharaan_id')->nullable()->constrained('jadwal_pemeliharaan')->nullOnDelete();
            $t->string('judul');
            $t->text('deskripsi');
            $t->foreignId('pelapor_id')->nullable()->constrained('karyawan')->nullOnDelete();
            $t->string('unit_pelapor')->nullable();       // snapshot unit pelapor
            $t->foreignId('dibuat_oleh')->constrained('users');
            $t->string('prioritas')->default('sedang');   // enum PrioritasTiket
            $t->string('status')->default('baru');        // enum StatusTiket
            $t->dateTime('waktu_lapor');
            $t->dateTime('waktu_respon')->nullable();
            $t->dateTime('waktu_selesai')->nullable();
            $t->foreignId('penyelesai_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('catatan_penyelesaian')->nullable();
            $t->timestamps();

            $t->index(['tim', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiket');
    }
};
