<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pola jadwal bernama: satu unit boleh punya beberapa pola (mis. "Pola CS IGD").
 * Nama wajib unik dalam satu unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Default membuat baris lama otomatis terisi tanpa perlu ->change()
        // (jalur change() beda perilaku MySQL vs sqlite).
        Schema::table('template_jadwal', function (Blueprint $t) {
            $t->string('nama', 60)->default('Pola Utama')->after('org_unit_id');
        });

        // URUTAN PENTING (MySQL): unique lama dipakai FK org_unit_id, tak bisa
        // di-drop duluan ("Cannot drop index ...: needed in a foreign key
        // constraint"). Unique baru berprefix org_unit_id → ia yang melayani FK.
        Schema::table('template_jadwal', function (Blueprint $t) {
            $t->unique(['org_unit_id', 'nama']);
        });

        Schema::table('template_jadwal', function (Blueprint $t) {
            $t->dropUnique(['org_unit_id']);
        });
    }

    public function down(): void
    {
        // Sengaja gagal keras bila satu unit sudah punya lebih dari satu pola.
        Schema::table('template_jadwal', function (Blueprint $t) {
            $t->unique(['org_unit_id']);
        });

        Schema::table('template_jadwal', function (Blueprint $t) {
            $t->dropUnique(['org_unit_id', 'nama']);
        });

        Schema::table('template_jadwal', function (Blueprint $t) {
            $t->dropColumn('nama');
        });
    }
};
