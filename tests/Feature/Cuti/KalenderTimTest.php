<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\KalenderTim;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class KalenderTimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function izin(): JenisCuti
    {
        return JenisCuti::where('kode', 'izin_biasa')->firstOrFail();
    }

    private function cuti(Karyawan $kar, string $mulai, string $selesai, string $status = 'disetujui'): void
    {
        PengajuanCuti::factory()->for($kar)->for($this->izin(), 'jenisCuti')->create([
            'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai,
            'jumlah_hari' => 1, 'status' => $status,
        ]);
    }

    private function hrd(): User
    {
        $u = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $u->assignRole('HRD');

        return $u;
    }

    public function test_hrd_org_wide(): void
    {
        $hrd = $this->hrd();
        $a = OrgUnit::factory()->create();
        $b = OrgUnit::factory()->create();
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $a->id, 'nama_lengkap' => 'Andi']), '2026-06-10', '2026-06-10');
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $b->id, 'nama_lengkap' => 'Bela']), '2026-06-11', '2026-06-11');

        Livewire::actingAs($hrd)->test(KalenderTim::class)
            ->assertSee('Andi')
            ->assertSee('Bela');
    }

    public function test_hrd_filter_unit(): void
    {
        $hrd = $this->hrd();
        $a = OrgUnit::factory()->create();
        $b = OrgUnit::factory()->create();
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $a->id, 'nama_lengkap' => 'Andi']), '2026-06-10', '2026-06-10');
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $b->id, 'nama_lengkap' => 'Bela']), '2026-06-11', '2026-06-11');

        Livewire::actingAs($hrd)->test(KalenderTim::class)
            ->set('unitId', (string) $a->id)
            ->assertSee('Andi')
            ->assertDontSee('Bela');
    }

    public function test_kepala_unit_terkunci_scope(): void
    {
        // Kepala unit A (non-HRD): hanya lihat unit A + turunan, bukan unit lain.
        $unitA = OrgUnit::factory()->create();
        $unitLain = OrgUnit::factory()->create();
        $kepala = Karyawan::factory()->create(['org_unit_id' => $unitA->id, 'nama_lengkap' => 'Kepala A']);
        $user = User::factory()->create(['karyawan_id' => $kepala->id]);
        $user->assignRole('Karyawan');

        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $unitA->id, 'nama_lengkap' => 'Anggota A']), '2026-06-10', '2026-06-10');
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $unitLain->id, 'nama_lengkap' => 'Orang Lain']), '2026-06-11', '2026-06-11');

        Livewire::actingAs($user)->test(KalenderTim::class)
            ->assertSee('Anggota A')
            ->assertDontSee('Orang Lain');
    }

    public function test_nav_bulan_geser_dan_reset_hari(): void
    {
        Livewire::actingAs($this->hrd())->test(KalenderTim::class)
            ->assertSet('bulan', '2026-06')
            ->set('hariAktif', '2026-06-10')
            ->call('bulanBerikutnya')
            ->assertSet('bulan', '2026-07')
            ->assertSet('hariAktif', '')
            ->call('bulanSebelumnya')
            ->assertSet('bulan', '2026-06');
    }

    public function test_pending_dan_disetujui_ditandai_beda(): void
    {
        $hrd = $this->hrd();
        $unit = OrgUnit::factory()->create();
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Disetujui Orang']), '2026-06-10', '2026-06-10', 'disetujui');
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Pending Orang']), '2026-06-11', '2026-06-11', 'diajukan');

        Livewire::actingAs($hrd)->test(KalenderTim::class)
            ->assertSeeHtml('data-status="disetujui"')
            ->assertSeeHtml('data-status="diajukan"');
    }

    public function test_pilih_hari_buka_panel_detail(): void
    {
        $hrd = $this->hrd();
        $unit = OrgUnit::factory()->create();
        $this->cuti(Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Detail Orang']), '2026-06-10', '2026-06-10', 'disetujui');

        Livewire::actingAs($hrd)->test(KalenderTim::class)
            ->call('pilihHari', '2026-06-10')
            ->assertSet('hariAktif', '2026-06-10')
            ->assertSeeHtml('data-panel="hari"')
            ->assertSee('Detail Orang')
            ->call('pilihHari', '2026-06-10')  // toggle tutup
            ->assertSet('hariAktif', '');
    }

    public function test_lebih_dari_tiga_orang_tampil_plus_n(): void
    {
        $hrd = $this->hrd();
        $unit = OrgUnit::factory()->create();
        foreach (['A', 'B', 'C', 'D', 'E'] as $n) {
            $this->cuti(Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => "Orang $n"]), '2026-06-10', '2026-06-10', 'disetujui');
        }

        Livewire::actingAs($hrd)->test(KalenderTim::class)
            ->assertSee('+2'); // 5 orang, cap 3, sisa 2
    }
}
