<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengganti;
use App\Enums\TipePengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProsesPenggantiLowonganTest extends TestCase
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

        foreach (['2026-08-03', '2026-08-05'] as $tgl) {
            Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => $tgl, 'shift_id' => $this->shift->id]);
        }
    }

    public function test_tetapkan_lowongan_langsung_menyalin_jadwal_tanpa_approval(): void
    {
        $baris = ProsesPengganti::tetapkan($this->nonaktif, $this->pengganti, $this->aktor);

        $this->assertSame(TipePengganti::Lowongan, $baris->tipe);
        $this->assertNull($baris->pengajuan_cuti_id);
        $this->assertSame($this->nonaktif->id, $baris->karyawan_digantikan_id);
        $this->assertNull($baris->tanggal_selesai);
        $this->assertSame(StatusPengganti::Aktif, $baris->status);

        $this->assertSame(2, Jadwal::where('karyawan_id', $this->pengganti->id)
            ->whereNotNull('pengganti_id')->count());
    }

    public function test_karyawan_aktif_tak_bisa_jadi_kasus_lowongan(): void
    {
        $aktif = Karyawan::factory()->create();

        $this->expectException(ProsesPenggantiException::class);
        ProsesPengganti::tetapkan($aktif, $this->pengganti, $this->aktor);
    }

    public function test_bentrok_jam_memblokir_penetapan_lowongan(): void
    {
        Jadwal::create(['karyawan_id' => $this->pengganti->id, 'tanggal' => '2026-08-03', 'shift_id' => $this->shift->id]);

        // Baris pengganti pada tanggal & shift yang sama = irisan penuh.
        $this->expectException(ProsesPenggantiException::class);
        ProsesPengganti::tetapkan($this->nonaktif, $this->pengganti, $this->aktor);
    }
}
