<?php

namespace Tests\Feature\Sistem;

use App\Enums\Role;
use App\Livewire\Sistem\PenggunaKelola;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RbacMatriksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $admin->assignRole(Role::AdminSistem->value);
        $this->actingAs($admin);
    }

    public function test_tab_role_menampilkan_8_kartu_role_dengan_jumlah_user(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->set('tab', 'role')
            ->assertSee('Staff HR')
            ->assertSee('Admin Sistem')
            ->assertSee('1 user'); // admin yang sedang login
    }

    public function test_matriks_dibaca_dari_database(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->set('tab', 'role')
            ->assertSee('Matriks Hak Akses')
            ->assertSee('Kelola data SDM')
            ->assertSee('Acc cuti final');
    }

    public function test_tab_role_menjelaskan_kemampuan_derived(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->set('tab', 'role')
            ->assertSee('bukan role'); // catatan Atasan/Koordinator = derived
    }
}
