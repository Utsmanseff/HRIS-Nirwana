<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengganti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PenugasanPenggantiModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    public function test_kolom_baru_ada(): void
    {
        $this->assertTrue(Schema::hasColumn('org_units', 'pakai_pengganti'));
        $this->assertTrue(Schema::hasColumn('jadwal', 'pengganti_id'));
        $this->assertTrue(Schema::hasTable('penugasan_pengganti'));
    }

    public function test_relasi_dan_cast(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        $pengganti = Karyawan::factory()->create();

        $baris = PenugasanPengganti::create([
            'pengajuan_cuti_id' => $cuti->id,
            'karyawan_digantikan_id' => $cuti->karyawan_id,
            'karyawan_id' => $pengganti->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-03',
            'status' => StatusPengganti::Aktif,
        ]);

        $this->assertInstanceOf(StatusPengganti::class, $baris->status);
        $this->assertSame('2026-08-01', $baris->tanggal_mulai->toDateString());
        $this->assertSame($cuti->id, $baris->pengajuan->id);
        $this->assertSame($pengganti->id, $baris->karyawan->id);
    }

    public function test_scope_aktif_dan_usulan(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        PenugasanPengganti::factory()->create(['pengajuan_cuti_id' => $cuti->id]);
        PenugasanPengganti::factory()->usulan()->create(['pengajuan_cuti_id' => $cuti->id]);

        $this->assertCount(1, PenugasanPengganti::aktif()->get());
        $this->assertCount(1, PenugasanPengganti::usulan()->get());
    }

    public function test_hapus_pengajuan_menghapus_baris_pengganti(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        PenugasanPengganti::factory()->create(['pengajuan_cuti_id' => $cuti->id]);

        $cuti->delete();

        $this->assertSame(0, PenugasanPengganti::count());
    }

    public function test_pengajuan_punya_relasi_pengganti_urut_tanggal(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        PenugasanPengganti::factory()->create([
            'pengajuan_cuti_id' => $cuti->id, 'tanggal_mulai' => '2026-08-05', 'tanggal_selesai' => '2026-08-07',
        ]);
        PenugasanPengganti::factory()->create([
            'pengajuan_cuti_id' => $cuti->id, 'tanggal_mulai' => '2026-08-01', 'tanggal_selesai' => '2026-08-04',
        ]);

        $urut = $cuti->pengganti()->get()->map(fn ($p) => $p->tanggal_mulai->toDateString())->all();

        $this->assertSame(['2026-08-01', '2026-08-05'], $urut);
    }

    public function test_jadwal_bertanda_pengganti(): void
    {
        $rencana = PenugasanPengganti::factory()->create();
        $biasa = \App\Models\Jadwal::factory()->create();
        $salinan = \App\Models\Jadwal::factory()->create(['pengganti_id' => $rencana->id]);

        $this->assertNull($biasa->penugasan);
        $this->assertSame($rencana->id, $salinan->penugasan->id);
        $this->assertSame([$salinan->id], \App\Models\Jadwal::salinanPengganti()->pluck('id')->all());
    }

    public function test_org_unit_pakai_pengganti_boolean(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        // Default kolom ditegakkan DB → baca ulang, bukan instance hasil create().
        $this->assertFalse($unit->fresh()->pakai_pengganti);

        $unit->update(['pakai_pengganti' => true]);

        $this->assertTrue($unit->fresh()->pakai_pengganti);
    }

    public function test_scope_cuti_dan_lowongan_memisahkan_baris(): void
    {
        $a = PenugasanPengganti::factory()->create();                       // default: tipe cuti
        $b = PenugasanPengganti::factory()->lowongan()->create();

        $this->assertSame([$a->id], PenugasanPengganti::cuti()->pluck('id')->all());
        $this->assertSame([$b->id], PenugasanPengganti::lowongan()->pluck('id')->all());
    }

    public function test_relasi_karyawan_digantikan(): void
    {
        $digantikan = Karyawan::factory()->create(['nama_lengkap' => 'Budi']);
        $baris = PenugasanPengganti::factory()->create(['karyawan_digantikan_id' => $digantikan->id]);

        $this->assertSame('Budi', $baris->karyawanDigantikan->nama_lengkap);
    }

    public function test_label_keterangan_bercabang_per_tipe(): void
    {
        $digantikan = Karyawan::factory()->create(['nama_lengkap' => 'Budi']);

        $cuti = PenugasanPengganti::factory()->create(['karyawan_digantikan_id' => $digantikan->id]);
        $low = PenugasanPengganti::factory()->lowongan()->create(['karyawan_digantikan_id' => $digantikan->id]);

        $this->assertSame('Pengganti cuti — Budi', $cuti->label());
        $this->assertSame('Mengisi jadwal kosong — Budi', $low->label());
        $this->assertFalse($cuti->terbuka());
        $this->assertTrue($low->terbuka());
    }
}
