<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_inventaris', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('tim'); // enum TimTeknis: it/sarana/atem
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_inventaris');
    }
};
