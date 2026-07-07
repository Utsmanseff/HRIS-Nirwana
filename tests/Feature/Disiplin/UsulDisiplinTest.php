<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Enums\TingkatSanksi;
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

    public function test_cari_hanya_menemukan_bawahan_dalam_unit(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['koor']);

        $unitLain = OrgUnit::create(['nama' => 'Gizi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $h['bidang']->id]);
        $luar = Karyawan::factory()->staffUnit($unitLain)->create(['nama_lengkap' => 'Orang Luar']);
        $staff = $h['staff'];
        $staff->update(['nama_lengkap' => 'Budi Bawahan']);

        Livewire::test(UsulDisiplin::class)
            ->set('cari', 'Budi')
            ->assertSee('Budi Bawahan')
            ->set('cari', 'Orang Luar')
            ->assertDontSee('Orang Luar');
    }

    public function test_pilih_bawahan_set_karyawan_terpilih(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['koor']);
        $staff = $h['staff'];

        Livewire::test(UsulDisiplin::class)
            ->call('pilihKaryawan', $staff->id)
            ->assertSet('karyawanId', $staff->id)
            ->assertSet('cari', '');
    }

    public function test_pilih_non_bawahan_diabaikan(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['koor']);
        $unitLain = OrgUnit::create(['nama' => 'Gizi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $h['bidang']->id]);
        $luar = Karyawan::factory()->staffUnit($unitLain)->create();

        Livewire::test(UsulDisiplin::class)
            ->call('pilihKaryawan', $luar->id)
            ->assertSet('karyawanId', null);
    }

    public function test_pilih_bawahan_dengan_sanksi_aktif_set_saran_tingkat(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['koor']);
        $staff = $h['staff'];
        SanksiDisiplin::factory()
            ->diterbitkan(TingkatSanksi::Teguran1)
            ->create(['karyawan_id' => $staff->id]);

        Livewire::test(UsulDisiplin::class)
            ->call('pilihKaryawan', $staff->id)
            ->assertSet('tingkat', '2');
    }

    public function test_pilih_bawahan_tanpa_sanksi_saran_teguran1(): void
    {
        $h = $this->hierarki();
        $this->loginKaryawan($h['koor']);

        Livewire::test(UsulDisiplin::class)
            ->call('pilihKaryawan', $h['staff']->id)
            ->assertSet('tingkat', '1');
    }
}
