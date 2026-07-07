<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanksi_disiplin', function (Blueprint $t) {
            $t->id();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->foreignId('pengusul_id')->constrained('karyawan');
            $t->unsignedTinyInteger('tingkat'); // TingkatSanksi 1..6
            $t->text('uraian');
            $t->date('tanggal_kejadian');
            $t->enum('status', ['diajukan', 'diproses', 'diterbitkan', 'ditolak', 'dicabut'])->default('diajukan');
            $t->string('nomor_surat')->nullable()->unique();
            $t->date('tanggal_terbit')->nullable();
            $t->date('berlaku_sampai')->nullable();
            $t->foreignId('diterbitkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->string('alasan_tolak')->nullable();
            $t->foreignId('dicabut_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->string('alasan_cabut')->nullable();
            $t->timestamp('dicabut_pada')->nullable();
            $t->string('surat_path')->nullable();
            $t->timestamps();
            $t->index(['karyawan_id', 'status']);
            $t->index(['status', 'berlaku_sampai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanksi_disiplin');
    }
};
