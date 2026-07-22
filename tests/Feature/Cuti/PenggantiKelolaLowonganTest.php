<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Pengganti\PenggantiKelola;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PenggantiKelolaLowonganTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_lowongan_muncul_untuk_rekan_satu_unit(): void
    {
        [, $nonaktif, $rekan] = $this->skenario();

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $rekan->id]))
            ->test(PenggantiKelola::class)
            ->assertSee($nonaktif->nama_lengkap)
            ->assertSee('Nonaktif sejak')
            ->assertSee('Ajukan diri');
    }

    public function test_koordinator_melihat_tombol_selesai(): void
    {
        [, , , $koor] = $this->skenario();

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $koor->id]))
            ->test(PenggantiKelola::class)
            ->assertSee('Selesai');
    }

    public function test_selesai_menutup_lowongan(): void
    {
        [, $nonaktif, , $koor] = $this->skenario();

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $koor->id]))
            ->test(PenggantiKelola::class)
            ->call('mulaiSelesai', $nonaktif->id)
            ->call('konfirmasiSelesai');

        $this->assertSame(0, Jadwal::where('karyawan_id', $nonaktif->id)
            ->whereDate('tanggal', '>=', now()->toDateString())->count());
    }

    public function test_bukan_koordinator_tak_bisa_menutup(): void
    {
        [, $nonaktif, $rekan] = $this->skenario();

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $rekan->id]))
            ->test(PenggantiKelola::class)
            ->call('mulaiSelesai', $nonaktif->id)
            ->call('konfirmasiSelesai')
            ->assertHasErrors('selesai');

        $this->assertGreaterThan(0, Jadwal::where('karyawan_id', $nonaktif->id)->count());
    }

    public function test_lowongan_unit_lain_tak_bocor(): void
    {
        [, , $rekan] = $this->skenario();

        $unitLain = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unitLain->id]);
        $luar = Karyawan::factory()->staffUnit($unitLain)->create([
            'status' => 'nonaktif', 'tanggal_nonaktif' => now()->subDay()->toDateString(),
        ]);
        Jadwal::create([
            'karyawan_id' => $luar->id, 'tanggal' => now()->addDay()->toDateString(), 'shift_id' => $shift->id,
        ]);

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $rekan->id]))
            ->test(PenggantiKelola::class)
            ->assertDontSee($luar->nama_lengkap);
    }

    /** @return array{0:OrgUnit,1:Karyawan,2:Karyawan,3:Karyawan} */
    private function skenario(): array
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create([
            'org_unit_id' => $unit->id, 'kode' => 'P', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $koor = Karyawan::factory()->pimpinanUnit($unit)->create();
        $nonaktif = Karyawan::factory()->staffUnit($unit)->create([
            'status' => 'nonaktif', 'tanggal_nonaktif' => now()->subDay()->toDateString(),
        ]);
        $rekan = Karyawan::factory()->staffUnit($unit)->create();

        Jadwal::create([
            'karyawan_id' => $nonaktif->id, 'tanggal' => now()->addDay()->toDateString(), 'shift_id' => $shift->id,
        ]);

        return [$unit, $nonaktif, $rekan, $koor];
    }
}
