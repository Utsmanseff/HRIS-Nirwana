<?php

namespace Tests\Feature\Absensi;

use App\Livewire\Absensi\JadwalKelola;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKelolaTest extends TestCase
{
    use RefreshDatabase;

    private function koordinator(): User
    {
        $bidang = OrgUnit::factory()->create(['tipe' => 'bidang', 'parent_id' => null]);
        $unit = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => $bidang->id]);
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 2]);
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id]);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_render_untuk_koordinator_memilih_unit_pimpinan(): void
    {
        $user = $this->koordinator();

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->assertOk()
            ->assertSet('tab', 'shift')
            ->assertSee('Shift Unit');
    }

    public function test_unit_terpilih_default_unit_pertama_dipimpin(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->assertSet('unitId', $unitId);
    }

    public function test_ganti_tab(): void
    {
        Livewire::actingAs($this->koordinator())->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')
            ->assertSet('tab', 'jadwal');
    }
}
