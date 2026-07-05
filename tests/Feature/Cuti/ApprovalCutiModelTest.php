<?php

namespace Tests\Feature\Cuti;

use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalCutiModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_baris_approval_terhubung_pengajuan_dan_approver(): void
    {
        $this->seed(JenisCutiSeeder::class);
        $pengajuan = PengajuanCuti::factory()->create();
        $approver = Karyawan::factory()->create();

        $row = ApprovalCuti::create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'urutan' => 1,
            'approver_id' => $approver->id,
            'peran' => PeranApproval::Koordinator,
            'status' => StatusApproval::Menunggu,
        ]);

        $this->assertTrue($row->pengajuan->is($pengajuan));
        $this->assertTrue($row->approver->is($approver));
        $this->assertInstanceOf(PeranApproval::class, $row->peran);
        $this->assertSame(StatusApproval::Menunggu, $row->status);
    }

    public function test_pengajuan_punya_banyak_approval_terurut(): void
    {
        $this->seed(JenisCutiSeeder::class);
        $pengajuan = PengajuanCuti::factory()->create();
        $a = Karyawan::factory()->create();
        $b = Karyawan::factory()->create();

        ApprovalCuti::create(['pengajuan_cuti_id' => $pengajuan->id, 'urutan' => 2, 'approver_id' => $b->id, 'peran' => PeranApproval::Hrd, 'status' => StatusApproval::Menunggu]);
        ApprovalCuti::create(['pengajuan_cuti_id' => $pengajuan->id, 'urutan' => 1, 'approver_id' => $a->id, 'peran' => PeranApproval::Koordinator, 'status' => StatusApproval::Menunggu]);

        $this->assertSame([1, 2], $pengajuan->approval->pluck('urutan')->all());
    }
}
