<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_cuti', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pengajuan_cuti_id')->constrained('pengajuan_cuti')->cascadeOnDelete();
            $t->unsignedTinyInteger('urutan');
            $t->foreignId('approver_id')->constrained('karyawan');
            $t->enum('peran', ['koordinator', 'kabid', 'hrd', 'direktur']);
            $t->enum('status', ['menunggu', 'setuju', 'tolak'])->default('menunggu');
            $t->text('catatan')->nullable();
            $t->timestamp('acted_at')->nullable();
            $t->timestamps();
            $t->unique(['pengajuan_cuti_id', 'urutan']);
            $t->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_cuti');
    }
};
