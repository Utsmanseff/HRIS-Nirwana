<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\KelolaCuti;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    private function userHrd(): User
    {
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('HRD');

        return $u;
    }

    public function test_non_hrd_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('Karyawan');

        $this->actingAs($u)->get('/cuti/kelola')->assertForbidden();
    }

    public function test_hrd_bisa_buka(): void
    {
        $this->actingAs($this->userHrd())->get('/cuti/kelola')->assertOk();

        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->assertOk()
            ->assertSet('tab', 'hari-libur');
    }

    public function test_hari_libur_tambah_dan_hapus(): void
    {
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->set('hlTanggal', '2026-08-17')
            ->set('hlNama', 'HUT RI')
            ->call('simpanHariLibur')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_libur', ['tanggal' => '2026-08-17 00:00:00', 'nama' => 'HUT RI']);

        $id = \App\Models\HariLibur::first()->id;
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->call('hapusHariLibur', $id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('hari_libur', ['id' => $id]);
    }

    public function test_hari_libur_validasi(): void
    {
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->set('hlTanggal', '')
            ->set('hlNama', '')
            ->call('simpanHariLibur')
            ->assertHasErrors(['hlTanggal', 'hlNama']);
    }
}
