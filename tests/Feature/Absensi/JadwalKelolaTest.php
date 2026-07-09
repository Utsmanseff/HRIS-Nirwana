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

    public function test_tambah_shift_untuk_unit(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('sNama', 'Pagi')->set('sKode', 'P')->set('sWarna', '#16A34A')
            ->set('sMulai', '07:00')->set('sSelesai', '14:00')->set('sToleransi', 15)
            ->call('simpanShift')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shift', ['org_unit_id' => $unitId, 'kode' => 'P', 'nama' => 'Pagi']);
    }

    public function test_edit_shift(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unitId, 'kode' => 'P', 'toleransi_telat' => 10]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('editShift', $shift->id)
            ->assertSet('sToleransi', 10)
            ->set('sToleransi', 20)
            ->call('simpanShift');

        $this->assertDatabaseHas('shift', ['id' => $shift->id, 'toleransi_telat' => 20]);
    }

    public function test_kode_shift_unik_per_unit(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;
        \App\Models\Shift::factory()->create(['org_unit_id' => $unitId, 'kode' => 'P']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('sNama', 'Pagi 2')->set('sKode', 'P')->set('sWarna', '#111111')
            ->set('sMulai', '08:00')->set('sSelesai', '15:00')->set('sToleransi', 10)
            ->call('simpanShift')
            ->assertHasErrors('sKode');
    }

    private function staffDi(OrgUnit $unit): Karyawan
    {
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);

        return Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id]);
    }

    public function test_simpan_template_pola_rotasi_dari_grid_kode(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'rotasi')
            ->set('tplJangkar', '2026-07-01')
            ->set('tplPanjang', 2)
            ->set("polaGrid.{$staff->id}.0", 'P')
            ->set("polaGrid.{$staff->id}.1", 'L')
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_jadwal', ['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01 00:00:00', 'mode' => 'rotasi']);
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 1, 'shift_id' => null]);
    }

    public function test_kode_tak_dikenal_ditolak(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'rotasi')
            ->set('tplJangkar', '2026-07-01')->set('tplPanjang', 1)
            ->set("polaGrid.{$staff->id}.0", 'XX')
            ->call('simpanTemplate')
            ->assertHasErrors('polaGrid');
    }

    public function test_simpan_template_mingguan_kunci_7_slot(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'mingguan')
            ->set("polaGrid.{$staff->id}.0", 'P')   // Senin
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_jadwal', ['org_unit_id' => $unit->id, 'mode' => 'mingguan']);
        $this->assertSame(7, \App\Models\PolaJadwal::where('karyawan_id', $staff->id)->count());
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 6, 'shift_id' => null]);
    }
}
