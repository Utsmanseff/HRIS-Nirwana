<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Livewire\Disiplin\UsulDisiplin;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsulDisiplinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Direktorat > Bidang > Unit + kepala tiap tingkat + HRD. Kembalikan aktor & anggota. */
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

        return compact('dir', 'bidang', 'unit', 'kabid', 'koor', 'staff', 'hrdKar');
    }

    protected function loginKaryawan(Karyawan $kar): User
    {
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_atasan_bisa_buka_dan_lihat_usulannya(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['koor']);
        SanksiDisiplin::factory()->create([
            'karyawan_id' => $h['staff']->id,
            'pengusul_id' => $h['koor']->id,
            'uraian' => 'Mangkir tiga hari berturut.',
        ]);

        Livewire::test(UsulDisiplin::class)
            ->assertOk()
            ->assertSee('Mangkir tiga hari berturut.')
            ->assertSee('Diajukan');
    }

    public function test_karyawan_tanpa_bawahan_ditolak(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['staff']);

        Livewire::test(UsulDisiplin::class)->assertForbidden();
    }
}
