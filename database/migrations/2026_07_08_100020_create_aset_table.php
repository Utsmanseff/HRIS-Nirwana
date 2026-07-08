<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('nama');
            $t->foreignId('kategori_inventaris_id')->constrained('kategori_inventaris');
            $t->string('merk')->nullable();
            $t->string('model')->nullable();
            $t->string('no_seri')->nullable();
            $t->date('tanggal_pengadaan')->nullable();
            $t->decimal('nilai_perolehan', 15, 2)->nullable();
            $t->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $t->foreignId('penanggung_jawab_id')->nullable()->constrained('karyawan')->nullOnDelete();
            $t->string('status')->default('baik'); // enum StatusAset
            $t->text('keterangan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
