<?php

namespace Tests\Feature\Tiket;

use App\Enums\Role;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Livewire\Beranda;
use App\Models\Karyawan;
use App\Models\Tiket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BerandaTiketTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_antrian_tim(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create();

        Livewire::actingAs($u)->test(Beranda::class)->assertSee('Antrian IT');
    }

    public function test_kartu_tiket_saya(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $kar = Karyawan::factory()->create();
        $u->update(['karyawan_id' => $kar->id]);
        $u->assignRole(Role::Karyawan->value);
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create(['pelapor_id' => $kar->id]);

        Livewire::actingAs($u)->test(Beranda::class)->assertSee('Tiket Saya');
    }
}
