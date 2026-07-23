<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengganti cuti: unit boleh mengaktifkan mekanisme pengganti; rencana penugasan
 * disimpan di `pengganti_cuti`; baris `jadwal` hasil salinan ditandai FK ke rencana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_units', function (Blueprint $t) {
            $t->boolean('pakai_pengganti')->default(false)->after('aktif');
        });

        Schema::create('pengganti_cuti', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pengajuan_cuti_id')->constrained('pengajuan_cuti')->cascadeOnDelete();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai');
            $t->string('status', 20)->default('aktif');
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['pengajuan_cuti_id', 'status']);
        });

        Schema::table('jadwal', function (Blueprint $t) {
            $t->foreignId('pengganti_cuti_id')->nullable()->after('dibuat_oleh')
                ->constrained('pengganti_cuti')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Urutan: lepas FK dari `jadwal` dulu, baru tabel rencana boleh di-drop.
        Schema::table('jadwal', function (Blueprint $t) {
            if (DB::getDriverName() === 'mysql') {
                $t->dropForeign(['pengganti_cuti_id']);
            }
            $t->dropColumn('pengganti_cuti_id');
        });

        Schema::dropIfExists('pengganti_cuti');

        Schema::table('org_units', function (Blueprint $t) {
            $t->dropColumn('pakai_pengganti');
        });
    }
};
