<?php

namespace Tests\Feature\Cuti;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use App\Models\User;
use App\Support\ProsesPengganti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TutupLowonganTest extends TestCase
{
    use RefreshDatabase;

    private OrgUnit $unit;

    private Karyawan $nonaktif;

    private Karyawan $pengganti;

    private Shift $shift;

    private User $aktor;

    protected function setUp(): void
    {
        parent::setUp();

        // Jejak lowongan diuji relatif "hari ini" → bekukan waktu supaya test
        // tak jadi bom waktu saat tanggal berjalan melewati data ujinya.
        Carbon::setTestNow('2026-08-02 08:00:00');

        $this->unit = OrgUnit::factory()->create();
        $this->shift = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'P', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $this->nonaktif = Karyawan::factory()->create([
            'org_unit_id' => $this->unit->id, 'status' => 'nonaktif', 'tanggal_nonaktif' => '2026-08-01',
        ]);
        $this->pengganti = Karyawan::factory()->create(['org_unit_id' => $this->unit->id]);
        $this->aktor = User::factory()->create();

        foreach (['2026-08-03', '2026-08-20'] as $tgl) {
            Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => $tgl, 'shift_id' => $this->shift->id]);
        }
        ProsesPengganti::tetapkan($this->nonaktif, $this->pengganti, $this->aktor);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tutup_menghapus_jadwal_sejak_tanggal_saja(): void
    {
        ProsesPengganti::tutupLowongan($this->nonaktif, Carbon::parse('2026-08-10'));

        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $this->nonaktif->id, 'tanggal' => '2026-08-03 00:00:00']);
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $this->nonaktif->id, 'tanggal' => '2026-08-20 00:00:00']);
    }

    public function test_salinan_pengganti_ikut_dilepas_sejak_tanggal_itu(): void
    {
        ProsesPengganti::tutupLowongan($this->nonaktif, Carbon::parse('2026-08-10'));

        $this->assertSame(1, Jadwal::where('karyawan_id', $this->pengganti->id)->count());
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $this->pengganti->id, 'tanggal' => '2026-08-20 00:00:00']);
    }

    public function test_rencana_dipotong_bukan_dihapus_bila_sudah_berjalan(): void
    {
        ProsesPengganti::tutupLowongan($this->nonaktif, Carbon::parse('2026-08-10'));

        $this->assertDatabaseHas('penugasan_pengganti', [
            'karyawan_id' => $this->pengganti->id,
            'tanggal_selesai' => '2026-08-09 00:00:00',
        ]);
    }

    public function test_keanggotaan_pola_dicabut(): void
    {
        $pola = TemplateJadwal::factory()->create(['org_unit_id' => $this->unit->id]);
        PolaJadwal::create([
            'template_id' => $pola->id, 'karyawan_id' => $this->nonaktif->id, 'posisi' => 0, 'shift_id' => $this->shift->id,
        ]);

        ProsesPengganti::tutupLowongan($this->nonaktif, Carbon::parse('2026-08-10'));

        $this->assertSame(0, PolaJadwal::where('karyawan_id', $this->nonaktif->id)->count());
    }

    public function test_daftar_lowongan_hanya_nonaktif_yang_masih_berjejak(): void
    {
        $bersih = Karyawan::factory()->create(['org_unit_id' => $this->unit->id, 'status' => 'nonaktif']);

        $ids = ProsesPengganti::lowongan([$this->unit->id])->pluck('id')->all();

        $this->assertContains($this->nonaktif->id, $ids);
        $this->assertNotContains($bersih->id, $ids);
    }

    public function test_karyawan_aktif_tak_pernah_jadi_lowongan(): void
    {
        $ids = ProsesPengganti::lowongan([$this->unit->id])->pluck('id')->all();

        $this->assertNotContains($this->pengganti->id, $ids);
    }
}
