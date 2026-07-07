<?php

namespace Tests\Feature\Disiplin;

use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\ApprovalSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalSanksiModelTest extends TestCase
{
    use RefreshDatabase;

    private function sanksi(): SanksiDisiplin
    {
        return SanksiDisiplin::create([
            'karyawan_id' => Karyawan::factory()->create()->id,
            'pengusul_id' => Karyawan::factory()->create()->id,
            'tingkat' => TingkatSanksi::Teguran1,
            'uraian' => 'x',
            'tanggal_kejadian' => '2026-07-01',
            'status' => StatusSanksi::Diproses,
        ]);
    }

    public function test_tahap_aktif_urutan_terkecil_menunggu(): void
    {
        $sanksi = $this->sanksi();
        $kabid = Karyawan::factory()->create();
        $hrd = Karyawan::factory()->create();

        ApprovalSanksi::create([
            'sanksi_id' => $sanksi->id, 'urutan' => 1, 'approver_id' => $kabid->id,
            'peran' => PeranApproval::Kabid, 'status' => StatusApproval::Setuju,
        ]);
        ApprovalSanksi::create([
            'sanksi_id' => $sanksi->id, 'urutan' => 2, 'approver_id' => $hrd->id,
            'peran' => PeranApproval::Hrd, 'status' => StatusApproval::Menunggu,
        ]);

        $aktif = $sanksi->tahapAktif();
        $this->assertNotNull($aktif);
        $this->assertSame(2, $aktif->urutan);
        $this->assertSame($hrd->id, $aktif->approver->id);
        $this->assertInstanceOf(PeranApproval::class, $aktif->peran);
    }
}
