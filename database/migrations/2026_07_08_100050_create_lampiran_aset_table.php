<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_aset', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $t->string('tipe'); // sertifikat/faktur/manual/garansi
            $t->string('path');
            $t->string('mime');
            $t->date('tanggal')->nullable();
            $t->date('berlaku_sampai')->nullable(); // info-only
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_aset');
    }
};
