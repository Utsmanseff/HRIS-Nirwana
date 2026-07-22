<?php

namespace Tests\Feature\Absensi;

use App\Livewire\Absensi\JadwalSaya;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalSayaLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_titipan_lowongan_diberi_label(): void
    {
        [$saya, $shift, $siti] = $this->skenario();

        $rencana = PenugasanPengganti::factory()->lowongan()->create([
            'karyawan_digantikan_id' => $siti->id, 'karyawan_id' => $saya->id,
        ]);
        Jadwal::create([
            'karyawan_id' => $saya->id, 'tanggal' => now()->toDateString(),
            'shift_id' => $shift->id, 'pengganti_id' => $rencana->id,
        ]);

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $saya->id]))
            ->test(JadwalSaya::class)
            ->assertSee('Mengisi jadwal kosong — Siti');
    }

    public function test_shift_titipan_cuti_diberi_label(): void
    {
        [$saya, $shift, $siti] = $this->skenario();

        $rencana = PenugasanPengganti::factory()->create([
            'karyawan_digantikan_id' => $siti->id, 'karyawan_id' => $saya->id,
        ]);
        Jadwal::create([
            'karyawan_id' => $saya->id, 'tanggal' => now()->toDateString(),
            'shift_id' => $shift->id, 'pengganti_id' => $rencana->id,
        ]);

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $saya->id]))
            ->test(JadwalSaya::class)
            ->assertSee('Pengganti cuti — Siti');
    }

    public function test_dinas_biasa_tanpa_label(): void
    {
        [$saya, $shift] = $this->skenario();

        Jadwal::create([
            'karyawan_id' => $saya->id, 'tanggal' => now()->toDateString(), 'shift_id' => $shift->id,
        ]);

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $saya->id]))
            ->test(JadwalSaya::class)
            ->assertDontSee('Mengisi jadwal kosong')
            ->assertDontSee('Pengganti cuti');
    }

    /** @return array{0:Karyawan,1:Shift,2:Karyawan} */
    private function skenario(): array
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);
        $siti = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Siti']);
        $saya = Karyawan::factory()->staffUnit($unit)->create();

        return [$saya, $shift, $siti];
    }
}
