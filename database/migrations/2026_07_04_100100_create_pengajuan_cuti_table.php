<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_cuti', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->foreignId('jenis_cuti_id')->constrained('jenis_cuti');
            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai');
            $t->unsignedSmallInteger('jumlah_hari');
            $t->text('alasan')->nullable();
            $t->string('lampiran_path')->nullable();
            $t->enum('status', ['diajukan', 'diproses', 'disetujui', 'ditolak', 'dibatalkan'])->default('diajukan');
            $t->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->text('alasan_batal')->nullable();
            $t->timestamps();
            $t->index(['karyawan_id', 'status']);
            $t->index(['jenis_cuti_id', 'status', 'tanggal_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cuti');
    }
};
