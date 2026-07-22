<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generalisasi rencana pengganti: `pengganti_cuti` (khusus cuti) menjadi
 * `penugasan_pengganti` (cuti + lowongan jadwal karyawan nonaktif).
 *
 * Sengaja TIDAK memakai Schema::rename() / ->change(): tabel lama ditunjuk FK
 * dari `jadwal`, dan `pengajuan_cuti_id` harus berubah jadi nullable — dua
 * operasi yang perilakunya beda antara MySQL dan sqlite. Gantinya: buat tabel
 * baru, salin isi (id dipertahankan supaya FK tetap cocok), buang yang lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_pengganti', function (Blueprint $t) {
            $t->id();
            $t->string('tipe', 20)->default('cuti');
            $t->foreignId('pengajuan_cuti_id')->nullable()->constrained('pengajuan_cuti')->cascadeOnDelete();
            $t->foreignId('karyawan_digantikan_id')->nullable()->constrained('karyawan')->cascadeOnDelete();
            $t->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai')->nullable();   // null = lowongan terbuka
            $t->string('status', 20)->default('aktif');
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['pengajuan_cuti_id', 'status']);
            $t->index(['tipe', 'karyawan_digantikan_id', 'status']);
        });

        // Salin isi tabel lama; semuanya bertipe 'cuti'.
        $sumber = DB::table('pengganti_cuti as p')
            ->join('pengajuan_cuti as c', 'c.id', '=', 'p.pengajuan_cuti_id')
            ->select([
                'p.id',
                DB::raw("'cuti' as tipe"),
                'p.pengajuan_cuti_id',
                'c.karyawan_id as karyawan_digantikan_id',
                'p.karyawan_id',
                'p.tanggal_mulai',
                'p.tanggal_selesai',
                'p.status',
                'p.dibuat_oleh',
                'p.created_at',
                'p.updated_at',
            ]);

        DB::table('penugasan_pengganti')->insertUsing([
            'id', 'tipe', 'pengajuan_cuti_id', 'karyawan_digantikan_id', 'karyawan_id',
            'tanggal_mulai', 'tanggal_selesai', 'status', 'dibuat_oleh', 'created_at', 'updated_at',
        ], $sumber);

        Schema::table('jadwal', function (Blueprint $t) {
            $t->foreignId('pengganti_id')->nullable()->after('dibuat_oleh')
                ->constrained('penugasan_pengganti')->nullOnDelete();
        });

        DB::table('jadwal')->whereNotNull('pengganti_cuti_id')
            ->update(['pengganti_id' => DB::raw('pengganti_cuti_id')]);

        // Lepas FK dulu baru buang kolom. MySQL: hindari error 1553 (index dipakai
        // FK). sqlite: DROP COLUMN native menolak kolom yang masih ada di definisi
        // FK, jadi dropForeign (yang membangun ulang tabel) tetap wajib.
        Schema::table('jadwal', function (Blueprint $t) {
            $t->dropForeign(['pengganti_cuti_id']);
            $t->dropColumn('pengganti_cuti_id');
        });

        Schema::dropIfExists('pengganti_cuti');
    }

    public function down(): void
    {
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

        // Hanya baris cuti yang punya padanan di skema lama.
        $sumber = DB::table('penugasan_pengganti')
            ->where('tipe', 'cuti')
            ->whereNotNull('pengajuan_cuti_id')
            ->whereNotNull('tanggal_selesai')
            ->select([
                'id', 'pengajuan_cuti_id', 'karyawan_id', 'tanggal_mulai',
                'tanggal_selesai', 'status', 'dibuat_oleh', 'created_at', 'updated_at',
            ]);

        DB::table('pengganti_cuti')->insertUsing([
            'id', 'pengajuan_cuti_id', 'karyawan_id', 'tanggal_mulai',
            'tanggal_selesai', 'status', 'dibuat_oleh', 'created_at', 'updated_at',
        ], $sumber);

        Schema::table('jadwal', function (Blueprint $t) {
            $t->foreignId('pengganti_cuti_id')->nullable()->after('dibuat_oleh')
                ->constrained('pengganti_cuti')->nullOnDelete();
        });

        DB::table('jadwal')
            ->whereIn('pengganti_id', fn ($q) => $q->select('id')->from('pengganti_cuti'))
            ->update(['pengganti_cuti_id' => DB::raw('pengganti_id')]);

        Schema::table('jadwal', function (Blueprint $t) {
            $t->dropForeign(['pengganti_id']);
            $t->dropColumn('pengganti_id');
        });

        Schema::dropIfExists('penugasan_pengganti');
    }
};
