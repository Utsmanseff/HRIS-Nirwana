<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\Karyawan;
use App\Models\KategoriInventaris;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BerandaInventarisTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_muncul_untuk_it_dengan_jumlah(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $u->assignRole(Role::It->value);

        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
        JadwalPemeliharaan::factory()->for($aset)->create([
            'interval_bulan' => 1,
            'terakhir_dilakukan' => Carbon::today()->subMonths(1)->subDays(2),
        ]);

        $this->actingAs($u)->get('/beranda')
            ->assertOk()
            ->assertSee('Aset Perlu Pemeliharaan');
    }

    public function test_kartu_tak_muncul_untuk_karyawan_biasa(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $u->assignRole(Role::Karyawan->value);

        $this->actingAs($u)->get('/beranda')
            ->assertOk()
            ->assertDontSee('Aset Perlu Pemeliharaan');
    }
}
