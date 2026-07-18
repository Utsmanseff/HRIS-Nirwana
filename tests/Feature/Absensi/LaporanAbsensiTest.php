<?php

namespace Tests\Feature\Absensi;

use App\Enums\Role;
use App\Exports\AbsensiExport;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use App\Support\NamaFile;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LaporanAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private function hrd(): User
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Hrd->value);

        return $user;
    }

    public function test_karyawan_biasa_dilarang(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/absensi/laporan')->assertForbidden();
    }

    public function test_hrd_lihat_rekap(): void
    {
        $user = $this->hrd();
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Budi Santoso']);
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => now()->toDateString()]);

        Livewire::actingAs($user)->test(\App\Livewire\Absensi\LaporanAbsensi::class)
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_admin_lihat_semua_unit_walau_koordinator(): void
    {
        $this->seed(RoleSeeder::class);
        $unitIt = \App\Models\OrgUnit::factory()->create(['nama' => 'IT']);
        $unitFar = \App\Models\OrgUnit::factory()->create(['nama' => 'Farmasi']);
        // Admin = koordinator IT + role Admin Sistem.
        $jab = \App\Models\Jabatan::factory()->create(['org_unit_id' => $unitIt->id, 'level' => 2]);
        $adminKar = Karyawan::factory()->create(['org_unit_id' => $unitIt->id, 'jabatan_id' => $jab->id, 'nama_lengkap' => 'Admin IT']);
        $admin = User::factory()->create(['karyawan_id' => $adminKar->id]);
        $admin->assignRole(Role::AdminSistem->value);

        $karFar = Karyawan::factory()->create(['org_unit_id' => $unitFar->id, 'nama_lengkap' => 'Orang Farmasi']);
        Absensi::factory()->create(['karyawan_id' => $karFar->id, 'tanggal_kerja' => now()->toDateString()]);

        // "Semua Unit" (unit null) → admin harus lihat data Farmasi juga.
        Livewire::actingAs($admin)->test(\App\Livewire\Absensi\LaporanAbsensi::class)
            ->set('unit', null)
            ->assertSee('Orang Farmasi');
    }

    public function test_koordinator_dibatasi_subtree(): void
    {
        $this->seed(RoleSeeder::class);
        $unitIt = \App\Models\OrgUnit::factory()->create(['nama' => 'IT']);
        $unitFar = \App\Models\OrgUnit::factory()->create(['nama' => 'Farmasi']);
        $jab = \App\Models\Jabatan::factory()->create(['org_unit_id' => $unitIt->id, 'level' => 2]);
        $koorKar = Karyawan::factory()->create(['org_unit_id' => $unitIt->id, 'jabatan_id' => $jab->id, 'nama_lengkap' => 'Koor IT']);
        $koor = User::factory()->create(['karyawan_id' => $koorKar->id]); // tanpa role → koordinator murni

        $karFar = Karyawan::factory()->create(['org_unit_id' => $unitFar->id, 'nama_lengkap' => 'Orang Farmasi']);
        Absensi::factory()->create(['karyawan_id' => $karFar->id, 'tanggal_kerja' => now()->toDateString()]);

        // Koordinator IT tak boleh lihat data Farmasi walau pilih "Semua".
        Livewire::actingAs($koor)->test(\App\Livewire\Absensi\LaporanAbsensi::class)
            ->set('unit', null)
            ->assertDontSee('Orang Farmasi');
    }

    public function test_ekspor_xlsx_terunduh(): void
    {
        Excel::fake();
        Carbon::setTestNow('2026-07-10 10:00:00');
        $user = $this->hrd();
        $kar = Karyawan::factory()->create();
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => '2026-07-10']);

        $this->actingAs($user)
            ->get(route('absensi.laporan.unduh', ['format' => 'xlsx', 'dari' => '2026-07-10', 'sampai' => '2026-07-10']))
            ->assertOk();

        Excel::assertDownloaded(
            NamaFile::laporan('laporan-absensi', ['2026-07-10'], 'xlsx'),
            fn (AbsensiExport $export) => true,
        );

        Carbon::setTestNow();
    }

    public function test_ekspor_per_unit_xlsx_multi_sheet(): void
    {
        Excel::fake();
        Carbon::setTestNow('2026-07-10 10:00:00');
        $user = $this->hrd();
        $unitA = \App\Models\OrgUnit::factory()->create(['nama' => 'Alfa']);
        $unitB = \App\Models\OrgUnit::factory()->create(['nama' => 'Beta']);
        foreach ([$unitA, $unitB] as $u) {
            $k = Karyawan::factory()->create(['org_unit_id' => $u->id]);
            Absensi::factory()->create(['karyawan_id' => $k->id, 'tanggal_kerja' => '2026-07-10']);
        }

        $this->actingAs($user)
            ->get(route('absensi.laporan.unduh', ['mode' => 'per-unit', 'format' => 'xlsx', 'dari' => '2026-07-10', 'sampai' => '2026-07-10']))
            ->assertOk();

        Excel::assertDownloaded(
            NamaFile::laporan('laporan-absensi', ['2026-07-10', 'per-unit'], 'xlsx'),
            fn (\App\Exports\AbsensiPerUnitExport $export) => count($export->sheets()) === 2,
        );

        Carbon::setTestNow();
    }

    public function test_ekspor_per_unit_pdf_terunduh(): void
    {
        $user = $this->hrd();
        $u = \App\Models\OrgUnit::factory()->create(['nama' => 'Alfa']);
        $k = Karyawan::factory()->create(['org_unit_id' => $u->id]);
        Absensi::factory()->create(['karyawan_id' => $k->id, 'tanggal_kerja' => now()->toDateString()]);

        $res = $this->actingAs($user)
            ->get(route('absensi.laporan.unduh', ['mode' => 'per-unit', 'format' => 'pdf', 'dari' => now()->toDateString(), 'sampai' => now()->toDateString()]));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    public function test_ekspor_pdf_terunduh(): void
    {
        $user = $this->hrd();
        $kar = Karyawan::factory()->create();
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => now()->toDateString()]);

        $res = $this->actingAs($user)
            ->get(route('absensi.laporan.unduh', ['format' => 'pdf', 'dari' => now()->toDateString(), 'sampai' => now()->toDateString()]));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }
}
