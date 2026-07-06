<?php

namespace Tests\Feature\Cuti;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanCutiEksporTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    private function userHrd(): User
    {
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('HRD');

        return $u;
    }

    private function pengajuanContoh(): void
    {
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Budi Santoso']);
        $izin = JenisCuti::where('kode', 'izin_biasa')->first();
        PengajuanCuti::factory()->for($kar)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => '2026-06-10', 'tanggal_selesai' => '2026-06-10',
            'jumlah_hari' => 1, 'status' => 'disetujui',
        ]);
    }

    public function test_pengajuan_xlsx_terunduh(): void
    {
        $this->pengajuanContoh();
        $res = $this->actingAs($this->userHrd())
            ->get('/cuti/laporan/pengajuan?format=xlsx&dari=2026-06-01&sampai=2026-06-30');

        $res->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $res->headers->get('content-type').$res->headers->get('content-disposition'),
        );
    }

    public function test_pengajuan_pdf_terunduh(): void
    {
        $this->pengajuanContoh();
        $res = $this->actingAs($this->userHrd())
            ->get('/cuti/laporan/pengajuan?format=pdf&dari=2026-06-01&sampai=2026-06-30');

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    public function test_non_hrd_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('Karyawan');

        $this->actingAs($u)->get('/cuti/laporan/pengajuan?format=pdf')->assertForbidden();
    }
}
