<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_sanksi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sanksi_id')->constrained('sanksi_disiplin')->cascadeOnDelete();
            $t->unsignedTinyInteger('urutan');
            $t->foreignId('approver_id')->constrained('karyawan');
            $t->enum('peran', ['koordinator', 'kabid', 'hrd', 'direktur']);
            $t->enum('status', ['menunggu', 'setuju', 'tolak'])->default('menunggu');
            $t->string('catatan')->nullable();
            $t->timestamp('acted_at')->nullable();
            $t->timestamps();
            $t->unique(['sanksi_id', 'urutan']);
            $t->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_sanksi');
    }
};
