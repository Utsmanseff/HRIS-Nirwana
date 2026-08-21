<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kewenangan mengusulkan sanksi untuk seluruh karyawan menempel pada JABATAN, bukan
     * pada orangnya: ganti pemegang, kewenangannya ikut. Null = jabatan biasa.
     */
    public function up(): void
    {
        Schema::table('jabatan', function (Blueprint $table) {
            $table->string('rute_pengawas', 20)->nullable()->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropColumn('rute_pengawas');
        });
    }
};
