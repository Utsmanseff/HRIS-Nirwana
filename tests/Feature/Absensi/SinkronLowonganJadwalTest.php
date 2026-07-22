<?php

namespace Tests\Feature\Absensi;

use App\Livewire\Absensi\JadwalKelola;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use App\Models\User;
use App\Support\ProsesPengganti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SinkronLowonganJadwalTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_bulanan_menyalin_jadwal_baru_ke_pengganti(): void
    {
        [$unit, $shift, $nonaktif, $pengganti] = $this->skenario();

        $pola = TemplateJadwal::factory()->create([
            'org_unit_id' => $unit->id, 'mode' => 'mingguan', 'tanggal_jangkar' => now()->startOfMonth()->toDateString(),
        ]);
        for ($i = 0; $i < 7; $i++) {
            PolaJadwal::create([
                'template_id' => $pola->id, 'karyawan_id' => $nonaktif->id, 'posisi' => $i, 'shift_id' => $shift->id,
            ]);
        }

        $this->artisan('absensi:bentuk-jadwal')->assertSuccessful();

        // Cakupan lowongan mulai dari tanggal nonaktif — jadwal sebelum itu
        // memang bukan urusan si pengganti.
        $sejak = $nonaktif->tanggal_nonaktif->toDateString();
        $ditutup = Jadwal::where('karyawan_id', $nonaktif->id)->whereDate('tanggal', '>=', $sejak)->count();
        $salinan = Jadwal::where('karyawan_id', $pengganti->id)->whereNotNull('pengganti_id')->count();

        $this->assertGreaterThan(0, $ditutup);
        $this->assertSame($ditutup, $salinan);
    }

    public function test_simpan_pola_tanpa_si_nonaktif_menutup_lowongan(): void
    {
        [$unit, $shift, $nonaktif, $pengganti, $koor] = $this->skenario();

        $pola = TemplateJadwal::factory()->create(['org_unit_id' => $unit->id]);
        PolaJadwal::create([
            'template_id' => $pola->id, 'karyawan_id' => $nonaktif->id, 'posisi' => 0, 'shift_id' => $shift->id,
        ]);

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $koor->id]))
            ->test(JadwalKelola::class, ['unitId' => $unit->id])
            ->set('tab', 'template')
            ->set('polaId', $pola->id)
            ->set('polaGrid', [])            // si nonaktif dibuang dari grid
            ->set('urutanAnggota', [])
            ->set('tplJangkar', now()->startOfMonth()->toDateString())
            ->call('simpanTemplate');

        $this->assertSame(0, PolaJadwal::where('karyawan_id', $nonaktif->id)->count());
        $this->assertSame(0, Jadwal::where('karyawan_id', $nonaktif->id)
            ->whereDate('tanggal', '>=', now()->toDateString())->count());
    }

    public function test_terapkan_pola_manual_ikut_menyinkronkan_salinan(): void
    {
        [$unit, $shift, $nonaktif, $pengganti, $koor] = $this->skenario();

        $pola = TemplateJadwal::factory()->create([
            'org_unit_id' => $unit->id, 'mode' => 'mingguan', 'tanggal_jangkar' => now()->startOfMonth()->toDateString(),
        ]);
        for ($i = 0; $i < 7; $i++) {
            PolaJadwal::create([
                'template_id' => $pola->id, 'karyawan_id' => $nonaktif->id, 'posisi' => $i, 'shift_id' => $shift->id,
            ]);
        }

        Livewire::actingAs(User::factory()->create(['karyawan_id' => $koor->id]))
            ->test(JadwalKelola::class, ['unitId' => $unit->id])
            ->call('terapkanPola', $pola->id);

        $sejak = $nonaktif->tanggal_nonaktif->toDateString();
        $ditutup = Jadwal::where('karyawan_id', $nonaktif->id)->whereDate('tanggal', '>=', $sejak)->count();

        $this->assertGreaterThan(0, $ditutup);
        $this->assertSame($ditutup, Jadwal::where('karyawan_id', $pengganti->id)
            ->whereNotNull('pengganti_id')->count());
    }

    /** @return array{0:OrgUnit,1:Shift,2:Karyawan,3:Karyawan,4:Karyawan} */
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
        $pengganti = Karyawan::factory()->staffUnit($unit)->create();

        Jadwal::create([
            'karyawan_id' => $nonaktif->id, 'tanggal' => now()->addDay()->toDateString(), 'shift_id' => $shift->id,
        ]);
        ProsesPengganti::tetapkan($nonaktif, $pengganti, User::factory()->create());

        return [$unit, $shift, $nonaktif, $pengganti, $koor];
    }
}
