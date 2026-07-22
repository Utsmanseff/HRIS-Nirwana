<?php

namespace Tests\Feature\Cuti;

use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Livewire\Cuti\Persetujuan;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PersetujuanPenggantiTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $koor;

    protected Karyawan $pemohon;

    protected User $userKoor;

    protected PengajuanCuti $cuti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();

        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->koor = Karyawan::factory()->pimpinanUnit($this->unit)->create();
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->userKoor = User::factory()->create(['karyawan_id' => $this->koor->id]);
        $this->userKoor->assignRole('Karyawan');

        $this->cuti = PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-02', 2)
            ->create(['karyawan_id' => $this->pemohon->id]);
        ApprovalCuti::create([
            'pengajuan_cuti_id' => $this->cuti->id, 'urutan' => 1, 'approver_id' => $this->koor->id,
            'peran' => PeranApproval::Koordinator, 'status' => StatusApproval::Menunggu,
        ]);
    }

    public function test_koordinator_unit_berflag_boleh_set_pengganti(): void
    {
        $b = Karyawan::factory()->staffUnit($this->unit)->create();

        Livewire::actingAs($this->userKoor)->test(Persetujuan::class)
            ->call('tinjau', $this->cuti->id)
            ->assertViewHas('bolehSetPengganti', true)
            ->call('setPengganti', $b->id);

        $this->assertSame(1, PenggantiCuti::where('karyawan_id', $b->id)->count());
    }

    public function test_unit_tanpa_flag_tidak_boleh(): void
    {
        $this->unit->update(['pakai_pengganti' => false]);
        $b = Karyawan::factory()->staffUnit($this->unit)->create();

        Livewire::actingAs($this->userKoor)->test(Persetujuan::class)
            ->call('tinjau', $this->cuti->id)
            ->assertViewHas('bolehSetPengganti', false)
            ->call('setPengganti', $b->id);

        $this->assertSame(0, PenggantiCuti::count());
    }

    public function test_approver_hrd_tidak_boleh_set_pengganti(): void
    {
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');
        $cuti2 = PengajuanCuti::factory()->rentang('2026-09-01', '2026-09-02', 2)
            ->create(['karyawan_id' => $this->pemohon->id]);
        ApprovalCuti::create([
            'pengajuan_cuti_id' => $cuti2->id, 'urutan' => 1, 'approver_id' => $hrd->id,
            'peran' => PeranApproval::Hrd, 'status' => StatusApproval::Menunggu,
        ]);

        Livewire::actingAs($userHrd)->test(Persetujuan::class)
            ->call('tinjau', $cuti2->id)
            ->assertViewHas('bolehSetPengganti', false)
            ->call('setPengganti', $this->koor->id);

        $this->assertSame(0, PenggantiCuti::count());
    }

    public function test_pencarian_kandidat_hanya_saat_boleh(): void
    {
        Karyawan::factory()->staffUnit($this->unit)->create(['nama_lengkap' => 'Siti Aminah']);

        Livewire::actingAs($this->userKoor)->test(Persetujuan::class)
            ->call('tinjau', $this->cuti->id)
            ->set('cariPengganti', 'Siti')
            ->assertViewHas('hasilCariPengganti', fn ($h) => $h->pluck('nama_lengkap')->all() === ['Siti Aminah']);

        $this->unit->update(['pakai_pengganti' => false]);

        Livewire::actingAs($this->userKoor)->test(Persetujuan::class)
            ->call('tinjau', $this->cuti->id)
            ->set('cariPengganti', 'Siti')
            ->assertViewHas('hasilCariPengganti', fn ($h) => $h->isEmpty());
    }
}
