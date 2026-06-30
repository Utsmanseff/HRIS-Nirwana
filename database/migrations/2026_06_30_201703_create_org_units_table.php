<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('org_units')->nullOnDelete();
            $t->string('nama');
            $t->enum('tipe', ['bidang', 'divisi', 'unit'])->default('divisi');
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};
