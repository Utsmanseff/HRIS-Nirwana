<?php

namespace Tests\Feature\Disiplin;

use App\Enums\TingkatSanksi;
use App\Livewire\Disiplin\SanksiSaya;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SanksiSayaTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_sanksi_sendiri(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $milikku = SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Sp1)->create(['karyawan_id' => $kar->id]);
        $oranglain = SanksiDisiplin::factory()->diterbitkan()->create();

        Livewire::actingAs($user)->test(SanksiSaya::class)
            ->assertOk()
            ->assertSee($milikku->nomor_surat)
            ->assertDontSee($oranglain->nomor_surat);
    }

    public function test_tanpa_karyawan_ditolak(): void
    {
        $user = User::factory()->create(['karyawan_id' => null]);

        Livewire::actingAs($user)->test(SanksiSaya::class)->assertForbidden();
    }
}
