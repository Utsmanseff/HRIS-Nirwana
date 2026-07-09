<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_jadwal', function (Blueprint $t) {
            $t->id();
            $t->foreignId('org_unit_id')->unique()->constrained('org_units')->cascadeOnDelete();
            $t->date('tanggal_jangkar');   // posisi 0 siklus = tanggal ini
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_jadwal');
    }
};
