<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Livewire\Cuti\CutiDetail;
use App\Models\ApprovalCuti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesApproval;
use App\Support\ProsesPengganti;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProsesPenggantiHookTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $pemohon;

    protected Karyawan $b;

    protected Shift $pagi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();

        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->b = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
    }

    private function cutiDenganJadwal(StatusPengajuanCuti $status): PengajuanCuti
    {
        // Jenis cuti sakit: lolos guard jatah (bukan cuti tahunan) — fokus test ini hook pengganti.
        $cuti = PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-02', 2)
            ->jenis(KodeJenisCuti::CutiSakit)
            ->status($status)
            ->create(['karyawan_id' => $this->pemohon->id]);
        foreach (['2026-08-01', '2026-08-02'] as $t) {
            Jadwal::factory()->create([
                'karyawan_id' => $this->pemohon->id, 'tanggal' => $t, 'shift_id' => $this->pagi->id,
            ]);
        }

        return $cuti;
    }

    public function test_approval_final_menggenerate_salinan(): void
    {
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');
        $pengaju = User::factory()->create(['karyawan_id' => $this->pemohon->id]);

        $cuti = $this->cutiDenganJadwal(StatusPengajuanCuti::Diajukan);
        ProsesPengganti::tetapkan($cuti, $this->b, $pengaju);
        $this->assertSame(0, Jadwal::salinanPengganti()->count());

        $step = ApprovalCuti::create([
            'pengajuan_cuti_id' => $cuti->id, 'urutan' => 1, 'approver_id' => $hrd->id,
            'peran' => PeranApproval::Hrd, 'status' => StatusApproval::Menunggu,
        ]);

        ProsesApproval::setujui($step, $userHrd);

        $this->assertSame(2, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
    }

    public function test_batal_hrd_membersihkan_salinan_dan_rencana(): void
    {
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');

        $cuti = $this->cutiDenganJadwal(StatusPengajuanCuti::Disetujui);
        ProsesPengganti::tetapkan($cuti, $this->b, $userHrd);
        $this->assertSame(2, Jadwal::salinanPengganti()->count());

        ProsesApproval::batalkanOlehHrd($cuti->fresh(), $userHrd, 'Salah input.');

        $this->assertSame(0, Jadwal::salinanPengganti()->count());
        $this->assertSame(0, PenugasanPengganti::count());
    }

    public function test_batal_mandiri_pemohon_membersihkan_rencana(): void
    {
        $cuti = $this->cutiDenganJadwal(StatusPengajuanCuti::Diajukan);
        $userPemohon = User::factory()->create(['karyawan_id' => $this->pemohon->id]);
        ProsesPengganti::tetapkan($cuti, $this->b, $userPemohon);

        Livewire::actingAs($userPemohon)->test(CutiDetail::class, ['pengajuan' => $cuti])
            ->call('batalkan');

        $this->assertSame(0, PenugasanPengganti::count());
    }
}
