<?php

namespace Tests\Feature\Absensi;

use App\Enums\Role;
use App\Exports\AbsensiExport;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use App\Support\NamaFileLaporan;
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
            NamaFileLaporan::buat('laporan-absensi', ['2026-07-10'], 'xlsx'),
            fn (AbsensiExport $export) => true,
        );

        Carbon::setTestNow();
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
