<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\LaporanCuti;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanCutiTest extends TestCase
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

        $this->actingAs($u)->get('/cuti/laporan')->assertForbidden();
    }

    public function test_hrd_buka_dan_strip_status(): void
    {
        $hrd = $this->userHrd();
        $kar = Karyawan::factory()->create();
        $izin = JenisCuti::where('kode', 'izin_biasa')->first();
        PengajuanCuti::factory()->for($kar)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => now()->startOfYear()->addMonth()->toDateString(),
            'tanggal_selesai' => now()->startOfYear()->addMonth()->toDateString(),
            'jumlah_hari' => 1, 'status' => 'diajukan',
        ]);

        $this->actingAs($hrd)->get('/cuti/laporan')->assertOk();

        Livewire::actingAs($hrd)->test(LaporanCuti::class)
            ->assertOk()
            ->assertSet('status', '')
            ->assertSee('Pending')
            ->assertSeeHtml('data-strip="pending"');
    }
}
