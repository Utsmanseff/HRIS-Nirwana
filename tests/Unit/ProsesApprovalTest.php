<?php

namespace Tests\Unit;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\CutiPerluPersetujuan;
use App\Support\ProsesApproval;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    /** Rakit pengajuan + rantai 2 tahap manual (koordinator lalu HRD). */
    private function pengajuanRantai(): array
    {
        $pemohon = Karyawan::factory()->create();
        $koor = Karyawan::factory()->create();
        $hrd = Karyawan::factory()->create();
        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);

        $p = PengajuanCuti::factory()->for($pemohon)->status(StatusPengajuanCuti::Diajukan)->create();
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $koor->id, 'peran' => 'koordinator', 'status' => StatusApproval::Menunggu]);
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 2, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => StatusApproval::Menunggu]);

        return compact('p', 'koor', 'hrd', 'userKoor', 'userHrd');
    }

    public function test_setujui_tahap_pertama_maju_ke_diproses_dan_notif_berikutnya(): void
    {
        Notification::fake();
        ['p' => $p, 'userKoor' => $userKoor, 'userHrd' => $userHrd] = $this->pengajuanRantai();

        ProsesApproval::setujui($p->tahapAktif(), $userKoor, 'ok');

        $this->assertSame(StatusPengajuanCuti::Diproses, $p->refresh()->status);
        $step1 = $p->approval()->where('urutan', 1)->first();
        $this->assertSame(StatusApproval::Setuju, $step1->status);
        $this->assertSame('ok', $step1->catatan);
        $this->assertNotNull($step1->acted_at);
        $this->assertSame(2, $p->tahapAktif()->urutan); // tahap aktif kini HRD
        Notification::assertSentTo($userHrd, CutiPerluPersetujuan::class);
    }
}