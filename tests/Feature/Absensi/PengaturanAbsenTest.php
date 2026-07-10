<?php

namespace Tests\Feature\Absensi;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PengaturanAbsenTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::AdminSistem->value);

        return $user;
    }

    public function test_non_admin_dilarang(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Hrd->value);

        $this->actingAs($user)->get('/absensi/pengaturan')->assertForbidden();
    }

    public function test_admin_bisa_simpan_pengaturan(): void
    {
        $user = $this->admin();

        Livewire::actingAs($user)->test(\App\Livewire\Absensi\PengaturanAbsen::class)
            ->set('officeLat', -6.2)
            ->set('officeLong', 106.8)
            ->set('radiusM', 150)
            ->set('maxAkurasiM', 50)
            ->call('simpan')
            ->assertHasNoErrors();

        $p = PengaturanAbsensi::ambil();
        $this->assertEquals(-6.2, (float) $p->office_lat);
        $this->assertEquals(106.8, (float) $p->office_long);
        $this->assertSame(150, $p->radius_m);
        $this->assertSame(50, $p->max_akurasi_m);
    }

    public function test_validasi_koordinat_wajib(): void
    {
        $user = $this->admin();

        Livewire::actingAs($user)->test(\App\Livewire\Absensi\PengaturanAbsen::class)
            ->set('officeLat', null)
            ->set('radiusM', 0)
            ->call('simpan')
            ->assertHasErrors(['officeLat', 'radiusM']);
    }
}
