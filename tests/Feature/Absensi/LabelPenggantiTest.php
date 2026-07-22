<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Support\LabelPengganti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelPenggantiTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_untuk_salinan_cuti_dan_lowongan_serta_kosong_untuk_dinas_biasa(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);

        $budi = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Budi']);
        $siti = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Siti']);
        $pengganti = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        $rCuti = PenugasanPengganti::factory()->create([
            'karyawan_digantikan_id' => $budi->id, 'karyawan_id' => $pengganti->id,
        ]);
        $rLow = PenugasanPengganti::factory()->lowongan()->create([
            'karyawan_digantikan_id' => $siti->id, 'karyawan_id' => $pengganti->id,
        ]);

        Jadwal::create(['karyawan_id' => $pengganti->id, 'tanggal' => '2026-08-03', 'shift_id' => $shift->id, 'pengganti_id' => $rCuti->id]);
        Jadwal::create(['karyawan_id' => $pengganti->id, 'tanggal' => '2026-08-04', 'shift_id' => $shift->id, 'pengganti_id' => $rLow->id]);
        Jadwal::create(['karyawan_id' => $pengganti->id, 'tanggal' => '2026-08-05', 'shift_id' => $shift->id]);

        $a1 = Absensi::factory()->create(['karyawan_id' => $pengganti->id, 'tanggal_kerja' => '2026-08-03', 'shift_id' => $shift->id]);
        $a2 = Absensi::factory()->create(['karyawan_id' => $pengganti->id, 'tanggal_kerja' => '2026-08-04', 'shift_id' => $shift->id]);
        $a3 = Absensi::factory()->create(['karyawan_id' => $pengganti->id, 'tanggal_kerja' => '2026-08-05', 'shift_id' => $shift->id]);

        $peta = LabelPengganti::petaAbsensi(collect([$a1, $a2, $a3]));

        $this->assertSame('Pengganti cuti — Budi', $peta[$a1->id]);
        $this->assertSame('Mengisi jadwal kosong — Siti', $peta[$a2->id]);
        $this->assertArrayNotHasKey($a3->id, $peta);
    }

    public function test_absensi_karyawan_lain_di_tanggal_sama_tak_ikut_terlabeli(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);

        $siti = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Siti']);
        $pengganti = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $orangLain = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        $rencana = PenugasanPengganti::factory()->lowongan()->create([
            'karyawan_digantikan_id' => $siti->id, 'karyawan_id' => $pengganti->id,
        ]);
        Jadwal::create(['karyawan_id' => $pengganti->id, 'tanggal' => '2026-08-04', 'shift_id' => $shift->id, 'pengganti_id' => $rencana->id]);

        $lain = Absensi::factory()->create(['karyawan_id' => $orangLain->id, 'tanggal_kerja' => '2026-08-04', 'shift_id' => $shift->id]);

        $this->assertArrayNotHasKey($lain->id, LabelPengganti::petaAbsensi(collect([$lain])));
    }

    public function test_layar_laporan_menampilkan_kolom_keterangan(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);
        $siti = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Siti']);
        $pengganti = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        $rencana = PenugasanPengganti::factory()->lowongan()->create([
            'karyawan_digantikan_id' => $siti->id, 'karyawan_id' => $pengganti->id,
        ]);
        Jadwal::create([
            'karyawan_id' => $pengganti->id, 'tanggal' => now()->toDateString(),
            'shift_id' => $shift->id, 'pengganti_id' => $rencana->id,
        ]);
        Absensi::factory()->create([
            'karyawan_id' => $pengganti->id, 'tanggal_kerja' => now()->toDateString(), 'shift_id' => $shift->id,
        ]);

        $hrd = \App\Models\User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $hrd->assignRole(\App\Enums\Role::Hrd->value);

        \Livewire\Livewire::actingAs($hrd)->test(\App\Livewire\Absensi\LaporanAbsensi::class)
            ->assertSee('Keterangan')
            ->assertSee('Mengisi jadwal kosong — Siti');
    }

    public function test_koleksi_kosong_tak_menembak_query(): void
    {
        $this->assertSame([], LabelPengganti::petaAbsensi(collect()));
    }
}
