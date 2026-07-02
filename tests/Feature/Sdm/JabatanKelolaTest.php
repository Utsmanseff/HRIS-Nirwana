<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\JabatanKelola;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class JabatanKelolaTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_daftar_menampilkan_jabatan_dengan_jumlah_karyawan(): void
    {
        $jab = Jabatan::factory()->create(['nama' => 'Apoteker', 'level' => 1]);
        Karyawan::factory()->count(2)->create(['jabatan_id' => $jab->id]);

        Livewire::actingAs($this->userSdm())->test(JabatanKelola::class)
            ->assertSee('Apoteker')
            ->assertSee('2'); // jumlah karyawan
    }
}
