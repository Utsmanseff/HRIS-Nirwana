<?php

namespace Tests\Feature\Sdm;

use App\Livewire\Sdm\OrgStruktur;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrgPenggantiToggleTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole('Staff HR');

        return $user;
    }

    public function test_toggle_menyalakan_dan_mematikan_flag(): void
    {
        $user = $this->userSdm();
        $unit = OrgUnit::factory()->create(['pakai_pengganti' => false]);

        Livewire::actingAs($user)->test(OrgStruktur::class)
            ->call('togglePengganti', $unit->id);
        $this->assertTrue($unit->fresh()->pakai_pengganti);

        Livewire::actingAs($user)->test(OrgStruktur::class)
            ->call('togglePengganti', $unit->id);
        $this->assertFalse($unit->fresh()->pakai_pengganti);
    }

    public function test_node_menampilkan_penanda_unit_berflag(): void
    {
        $user = $this->userSdm();
        OrgUnit::factory()->create(['nama' => 'Unit Farmasi', 'pakai_pengganti' => true]);

        Livewire::actingAs($user)->test(OrgStruktur::class)
            ->assertSee('Pengganti: on');
    }
}
