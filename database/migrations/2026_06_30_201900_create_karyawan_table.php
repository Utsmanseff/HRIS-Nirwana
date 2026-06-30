<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $t) {
            $t->id();
            $t->string('nip')->unique();
            $t->string('nama_lengkap');
            $t->string('nik')->nullable();
            $t->string('tempat_lahir')->nullable();
            $t->date('tanggal_lahir')->nullable();
            $t->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $t->string('agama')->nullable();
            $t->enum('status_nikah', ['belum', 'menikah', 'cerai'])->nullable();
            $t->string('foto_path')->nullable();
            $t->text('alamat')->nullable();
            $t->string('no_hp')->nullable();
            $t->string('email')->nullable();
            $t->string('pendidikan_terakhir')->nullable();
            $t->foreignId('org_unit_id')->constrained('org_units');
            $t->foreignId('jabatan_id')->constrained('jabatan');
            $t->foreignId('atasan_id')->nullable()->constrained('karyawan')->nullOnDelete();
            $t->date('tanggal_masuk')->nullable();
            $t->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $t->enum('alasan_nonaktif', ['resign', 'kontrak_berakhir', 'phk', 'pensiun', 'meninggal'])->nullable();
            $t->date('tanggal_nonaktif')->nullable();
            $t->timestamps();
            $t->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
