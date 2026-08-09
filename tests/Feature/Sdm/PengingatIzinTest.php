<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Enums\SeverityPengingat;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Support\PengingatIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatIzinTest extends TestCase
{
    use RefreshDatabase;

    private function jenis(KodeJenisIzin $kode): JenisIzin
    {
        return JenisIzin::where('kode', $kode->value)->firstOrFail();
    }

    public function test_str_pakai_ambang_90_hari(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => $this->jenis(KodeJenisIzin::Str)->id,
            'berlaku_akhir' => now()->addDays(60),
        ]);

        $list = PengingatIzin::semua();
        $this->assertCount(1, $list);
        $this->assertSame(SeverityPengingat::AkanBerakhir, $list->first()->severity);
    }

    public function test_di_luar_ambang_belum_diingatkan(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => $this->jenis(KodeJenisIzin::Sip)->id,
            'berlaku_akhir' => now()->addDays(120),   // ambang default 90
        ]);

        $this->assertCount(0, PengingatIzin::semua());
    }

    public function test_ambang_kustom_per_jenis_dihormati(): void
    {
        // HRD boleh menyetel ambang per jenis; yang dibaca ambang jenisnya, bukan konstanta.
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $sik = $this->jenis(KodeJenisIzin::Sik);
        $sik->update(['ambang_hari' => 30]);

        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => $sik->id,
            'berlaku_akhir' => now()->addDays(60),   // di dalam 90, di luar 30
        ]);

        $this->assertCount(0, PengingatIzin::semua());

        $sik->update(['ambang_hari' => 90]);
        $this->assertCount(1, PengingatIzin::semua());
    }

    public function test_hanya_baris_terbaru_per_jenis_dievaluasi(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $sip = $this->jenis(KodeJenisIzin::Sip);

        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id, 'jenis_izin_id' => $sip->id,
            'berlaku_akhir' => now()->subDays(5),      // lama, sudah lewat
        ]);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id, 'jenis_izin_id' => $sip->id,
            'berlaku_akhir' => now()->addYears(3),     // perpanjangan
        ]);

        $this->assertCount(0, PengingatIzin::semua());
    }

    public function test_izin_lewat_jadi_terlewat(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => $this->jenis(KodeJenisIzin::Sip)->id,
            'berlaku_akhir' => now()->subDays(3),
        ]);

        $this->assertSame(SeverityPengingat::Terlewat, PengingatIzin::semua()->first()->severity);
    }

    public function test_jenis_nonaktif_dilewati(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $sik = $this->jenis(KodeJenisIzin::Sik);
        $sik->update(['aktif' => false]);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id, 'jenis_izin_id' => $sik->id,
            'berlaku_akhir' => now()->addDays(5),
        ]);

        $this->assertCount(0, PengingatIzin::semua());
    }

    public function test_karyawan_nonaktif_dilewati(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'nonaktif']);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => $this->jenis(KodeJenisIzin::Sip)->id,
            'berlaku_akhir' => now()->subDays(3),
        ]);

        $this->assertCount(0, PengingatIzin::semua());
    }
}
