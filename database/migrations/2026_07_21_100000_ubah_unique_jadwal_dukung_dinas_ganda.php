<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dinas ganda: satu karyawan bisa dijadwalkan lebih dari sekali dalam sehari
 * (mis. 00:00-08:00 lalu 16:00-00:00). Shift kembar tetap ditolak DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        // URUTAN PENTING (MySQL): index lama dipakai FK karyawan_id, jadi tak bisa
        // di-drop lebih dulu ("Cannot drop index ...: needed in a foreign key
        // constraint"). Buat index baru dulu — prefix kirinya (karyawan_id) yang
        // kemudian melayani FK — baru buang yang lama.
        Schema::table('jadwal', function (Blueprint $t) {
            $t->unique(['karyawan_id', 'tanggal', 'shift_id']);
        });

        Schema::table('jadwal', function (Blueprint $t) {
            $t->dropUnique(['karyawan_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        // Sengaja gagal keras bila sudah ada jadwal ganda — jangan hapus data diam-diam.
        Schema::table('jadwal', function (Blueprint $t) {
            $t->unique(['karyawan_id', 'tanggal']);
        });

        Schema::table('jadwal', function (Blueprint $t) {
            $t->dropUnique(['karyawan_id', 'tanggal', 'shift_id']);
        });
    }
};
