<?php

namespace Tests\Feature\Cuti;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesPengganti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProsesPenggantiSinkronTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $nonaktif;

    private Karyawan $pengganti;

    private Shift $shift;

    private User $aktor;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = OrgUnit::factory()->create();
        $this->shift = Shift::factory()->create([
            'org_unit_id' => $unit->id, 'kode' => 'P', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $this->nonaktif = Karyawan::factory()->create([
            'org_unit_id' => $unit->id, 'status' => 'nonaktif', 'tanggal_nonaktif' => '2026-08-01',
        ]);
        $this->pengganti = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $this->aktor = User::factory()->create();

        Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => '2026-08-03', 'shift_id' => $this->shift->id]);
        ProsesPengganti::tetapkan($this->nonaktif, $this->pengganti, $this->aktor);
    }

    public function test_jadwal_baru_si_nonaktif_ikut_tersalin_saat_sinkron(): void
    {
        // Bulan berikutnya lahir dari pola (TerapkanPola tak memfilter status karyawan).
        Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => '2026-09-04', 'shift_id' => $this->shift->id]);

        $dibuat = ProsesPengganti::sinkronSemuaLowongan();

        $this->assertSame(1, $dibuat);
        $this->assertDatabaseHas('jadwal', [
            'karyawan_id' => $this->pengganti->id,
            'tanggal' => '2026-09-04 00:00:00',
            'shift_id' => $this->shift->id,
        ]);
    }

    public function test_sinkron_idempoten(): void
    {
        Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => '2026-09-04', 'shift_id' => $this->shift->id]);

        ProsesPengganti::sinkronSemuaLowongan();
        $this->assertSame(0, ProsesPengganti::sinkronSemuaLowongan());
    }

    public function test_sinkron_kasus_lowongan_lewat_karyawan(): void
    {
        Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => '2026-09-04', 'shift_id' => $this->shift->id]);

        $this->assertSame(1, ProsesPengganti::sinkronKasus($this->nonaktif));
    }
}
