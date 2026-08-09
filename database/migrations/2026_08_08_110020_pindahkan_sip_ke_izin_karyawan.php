<?php

use App\Enums\KodeJenisIzin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master di-seed di dalam migrasi supaya penyalinan punya jenis_izin_id yang sah
        // tanpa bergantung urutan seeder.
        $now = now();
        foreach ([[KodeJenisIzin::Str, 'STR', 90], [KodeJenisIzin::Sip, 'SIP', 90],
            [KodeJenisIzin::Sik, 'SIK', 90], [KodeJenisIzin::Sertifikat, 'Sertifikat Kompetensi', 90]] as [$kode, $nama, $ambang]) {
            $ada = DB::table('jenis_izin')->where('kode', $kode->value)->exists();
            if (! $ada) {
                DB::table('jenis_izin')->insert([
                    'kode' => $kode->value, 'nama' => $nama, 'ambang_hari' => $ambang,
                    'aktif' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $sipId = DB::table('jenis_izin')->where('kode', KodeJenisIzin::Sip->value)->value('id');

        DB::table('karyawan')
            ->whereNotNull('sip_berlaku_akhir')
            ->orderBy('id')
            ->select('id', 'sip_nomor', 'sip_berlaku_mulai', 'sip_berlaku_akhir')
            ->chunk(200, function ($baris) use ($sipId, $now) {
                $isi = [];
                foreach ($baris as $k) {
                    $isi[] = [
                        'karyawan_id' => $k->id,
                        'jenis_izin_id' => $sipId,
                        'nomor' => $k->sip_nomor,
                        'berlaku_mulai' => $k->sip_berlaku_mulai,
                        'berlaku_akhir' => $k->sip_berlaku_akhir,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($isi) {
                    DB::table('izin_karyawan')->insert($isi);
                }
            });

        Schema::table('karyawan', function (Blueprint $t) {
            // Tak ada foreign key pada kolom ini, jadi dropColumn aman di semua driver.
            $t->dropColumn(['sip_nomor', 'sip_berlaku_mulai', 'sip_berlaku_akhir']);
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $t) {
            $t->string('sip_nomor')->nullable()->after('email');
            $t->date('sip_berlaku_mulai')->nullable()->after('sip_nomor');
            $t->date('sip_berlaku_akhir')->nullable()->after('sip_berlaku_mulai');
        });

        $sipId = DB::table('jenis_izin')->where('kode', KodeJenisIzin::Sip->value)->value('id');
        if (! $sipId) {
            return;
        }

        // Kembalikan hanya baris SIP terbaru per karyawan (kolom lama cuma muat satu nilai).
        DB::table('izin_karyawan')->where('jenis_izin_id', $sipId)->orderBy('berlaku_akhir')
            ->get()->each(function ($izin) {
                DB::table('karyawan')->where('id', $izin->karyawan_id)->update([
                    'sip_nomor' => $izin->nomor,
                    'sip_berlaku_mulai' => $izin->berlaku_mulai,
                    'sip_berlaku_akhir' => $izin->berlaku_akhir,
                ]);
            });

        // Baris SIP dibuang setelah disalin balik: tanpa ini, rollback lalu migrate ulang
        // akan menggandakan riwayat SIP diam-diam.
        DB::table('izin_karyawan')->where('jenis_izin_id', $sipId)->delete();
    }
};
