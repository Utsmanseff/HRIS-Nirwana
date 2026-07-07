<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Livewire\Disiplin\PersetujuanDisiplin;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PersetujuanDisiplinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function hierarki(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();

        $hrdKar = Karyawan::factory()->create();
        $hrdUser = User::factory()->create(['karyawan_id' => $hrdKar->id]);
        $hrdUser->assignRole(Role::Hrd->value);

        return compact('dir', 'bidang', 'unit', 'kabid', 'koor', 'staff', 'hrdKar', 'hrdUser');
    }

    protected function loginKaryawan(Karyawan $kar): User
    {
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $this->actingAs($user);

        return $user;
    }

    /** Usulan dari koordinator → rantai [Kabid, HRD], tahap aktif = Kabid. */
    protected function usulan(array $h): SanksiDisiplin
    {
        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran1)->create([
            'karyawan_id' => $h['staff']->id,
            'pengusul_id' => $h['koor']->id,
            'status' => StatusSanksi::Diajukan,
            'uraian' => 'Mangkir tanpa kabar.',
        ]);
        RantaiSanksi::bangunUntuk($sanksi);

        return $sanksi;
    }

    public function test_approver_tahap_aktif_melihat_di_perlu_aksi(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['kabid']);
        $this->usulan($h);

        Livewire::test(PersetujuanDisiplin::class)
            ->assertOk()
            ->assertSee('Mangkir tanpa kabar.');
    }

    public function test_approver_lain_tak_melihat_usulan_bukan_tahapnya(): void
    {
        $h = $this->hierarki();
        // HRD login: tahap aktif masih Kabid (urutan 1) → HRD belum lihat di perlu-aksi.
        $this->actingAs($h['hrdUser']);
        $this->usulan($h);

        Livewire::test(PersetujuanDisiplin::class)
            ->assertDontSee('Mangkir tanpa kabar.'); // di tab perlu-aksi default
    }

    public function test_staff_biasa_ditolak(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['staff']);

        Livewire::test(PersetujuanDisiplin::class)->assertForbidden();
    }

    public function test_hrd_tab_semua_melihat_semua_sanksi(): void
    {
        $h = $this->hierarki();
        $this->actingAs($h['hrdUser']);
        $this->usulan($h);

        Livewire::test(PersetujuanDisiplin::class)
            ->set('tab', 'semua')
            ->assertSee('Mangkir tanpa kabar.');
    }
}
