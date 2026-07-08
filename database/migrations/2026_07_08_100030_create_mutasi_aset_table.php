<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_aset', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $t->foreignId('dari_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $t->foreignId('ke_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $t->date('tanggal');
            $t->foreignId('oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->text('catatan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_aset');
    }
};
