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

class PenggunaDaftarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $this->admin->assignRole(Role::AdminSistem->value);
        $this->actingAs($this->admin);
    }

    public function test_daftar_menampilkan_user_beserta_role_dan_karyawan_tertaut(): void
    {
        $karyawan = Karyawan::factory()->create(['nip' => 'NIP-777', 'nama_lengkap' => 'Budi Santoso']);
        $user = User::factory()->create(['name' => 'Budi', 'karyawan_id' => $karyawan->id]);
        $user->assignRole(Role::Karyawan->value);

        Livewire::test(PenggunaKelola::class)
            ->assertSee('Budi')
            ->assertSee('NIP-777')
            ->assertSee('Karyawan');
    }

    public function test_user_tanpa_karyawan_ditandai_belum_tertaut(): void
    {
        User::factory()->create(['name' => 'Akun Nganggur', 'karyawan_id' => null]);

        Livewire::test(PenggunaKelola::class)
            ->assertSee('Akun Nganggur')
            ->assertSee('Belum tertaut');
    }

    public function test_search_menyaring_by_nama_email_nip(): void
    {
        $k = Karyawan::factory()->create(['nip' => 'NIP-CARI']);
        User::factory()->create(['name' => 'Target Orang', 'karyawan_id' => $k->id]);
        User::factory()->create(['name' => 'Orang Lain', 'karyawan_id' => Karyawan::factory()->create()->id]);

        Livewire::test(PenggunaKelola::class)
            ->set('q', 'NIP-CARI')
            ->assertSee('Target Orang')
            ->assertDontSee('Orang Lain');
    }

    public function test_filter_role_dan_status(): void
    {
        $hrd = User::factory()->create(['name' => 'Bu HRD', 'karyawan_id' => Karyawan::factory()->create()->id]);
        $hrd->assignRole(Role::Hrd->value);
        User::factory()->nonaktif()->create(['name' => 'Si Nonaktif', 'karyawan_id' => Karyawan::factory()->create()->id]);

        Livewire::test(PenggunaKelola::class)
            ->set('filterRole', Role::Hrd->value)
            ->assertSee('Bu HRD')
            ->assertDontSee('Si Nonaktif');

        Livewire::test(PenggunaKelola::class)
            ->set('filterStatus', 'nonaktif')
            ->assertSee('Si Nonaktif')
            ->assertDontSee('Bu HRD');
    }
}
