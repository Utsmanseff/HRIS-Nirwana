<?php

namespace Tests\Feature\Absensi;

use App\Enums\Role;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private function hrd(): User
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Hrd->value);

        return $user;
    }

    public function test_karyawan_biasa_dilarang(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/absensi/laporan')->assertForbidden();
    }

    public function test_hrd_lihat_rekap(): void
    {
        $user = $this->hrd();
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Budi Santoso']);
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => now()->toDateString()]);

        Livewire::actingAs($user)->test(\App\Livewire\Absensi\LaporanAbsensi::class)
            ->assertOk()
            ->assertSee('Budi Santoso');
    }
}
