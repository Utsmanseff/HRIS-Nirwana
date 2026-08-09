<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_absensi', function (Blueprint $t) {
            $t->boolean('pengingat_aktif')->default(true)->after('max_akurasi_m');
            $t->smallInteger('jeda_masuk_menit')->default(15)->after('pengingat_aktif');
            $t->smallInteger('jeda_pulang_menit')->default(30)->after('jeda_masuk_menit');
            $t->smallInteger('ambang_nyangkut_jam')->default(12)->after('jeda_pulang_menit');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_absensi', function (Blueprint $t) {
            $t->dropColumn(['pengingat_aktif', 'jeda_masuk_menit', 'jeda_pulang_menit', 'ambang_nyangkut_jam']);
        });
    }
};
