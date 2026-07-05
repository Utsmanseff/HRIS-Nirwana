<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_cuti', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('nama');
            $t->boolean('potong_saldo')->default(false);
            $t->string('efek_penggajian')->nullable();
            $t->boolean('butuh_lampiran')->default(false);
            $t->boolean('boleh_backdate')->default(false);
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_cuti');
    }
};
