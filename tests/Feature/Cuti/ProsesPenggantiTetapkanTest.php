<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProsesPenggantiTetapkanTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $pemohon;

    protected PengajuanCuti $cuti;

    protected User $aktor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->cuti = PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-03', 3)
            ->create(['karyawan_id' => $this->pemohon->id]);
        $this->aktor = User::factory()->create(['karyawan_id' => $this->pemohon->id]);
    }

    public function test_membuat_baris_aktif_rentang_penuh(): void
    {
        $b = Karyawan::factory()->staffUnit($this->unit)->create();

        $baris = ProsesPengganti::tetapkan($this->cuti, $b, $this->aktor);

        $this->assertSame(StatusPengganti::Aktif, $baris->status);
        $this->assertSame('2026-08-01', $baris->tanggal_mulai->toDateString());
        $this->assertSame('2026-08-03', $baris->tanggal_selesai->toDateString());
        $this->assertSame($this->aktor->id, $baris->dibuat_oleh);
        $this->assertSame(1, $this->cuti->pengganti()->count());
    }

    public function test_menetapkan_ulang_mengganti_baris_lama(): void
    {
        $b = Karyawan::factory()->staffUnit($this->unit)->create();
        $c = Karyawan::factory()->staffUnit($this->unit)->create();

        ProsesPengganti::tetapkan($this->cuti, $b, $this->aktor);
        ProsesPengganti::tetapkan($this->cuti->fresh(), $c, $this->aktor);

        $baris = $this->cuti->pengganti()->get();
        $this->assertCount(1, $baris);
        $this->assertSame($c->id, $baris->first()->karyawan_id);
    }

    public function test_baris_usulan_tidak_ikut_terhapus(): void
    {
        $b = Karyawan::factory()->staffUnit($this->unit)->create();
        PenugasanPengganti::factory()->usulan()->create([
            'pengajuan_cuti_id' => $this->cuti->id, 'karyawan_id' => $b->id,
        ]);

        ProsesPengganti::tetapkan($this->cuti, $b, $this->aktor);

        $this->assertSame(1, $this->cuti->pengganti()->usulan()->count());
    }

    public function test_tolak_bila_bentrok(): void
    {
        $pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $b = Karyawan::factory()->staffUnit($this->unit)->create();
        Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => '2026-08-01', 'shift_id' => $pagi->id]);
        Jadwal::factory()->create(['karyawan_id' => $b->id, 'tanggal' => '2026-08-01', 'shift_id' => $pagi->id]);

        $this->expectException(ProsesPenggantiException::class);
        $this->expectExceptionMessageMatches('/2026-08-01/');

        ProsesPengganti::tetapkan($this->cuti, $b, $this->aktor);
    }

    public function test_tolak_pemohon_jadi_pengganti_diri_sendiri(): void
    {
        $this->expectException(ProsesPenggantiException::class);

        ProsesPengganti::tetapkan($this->cuti, $this->pemohon, $this->aktor);
    }
}
