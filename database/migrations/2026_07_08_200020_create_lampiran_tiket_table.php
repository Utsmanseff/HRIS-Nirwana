<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_tiket', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tiket_id')->constrained('tiket')->cascadeOnDelete();
            $t->string('path');
            $t->string('mime');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_tiket');
    }
};
