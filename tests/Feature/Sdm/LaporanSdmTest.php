<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Exports\KaryawanExport;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class LaporanSdmTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_instansi_tersedia_untuk_kop(): void
    {
        $this->assertSame('RSU Nirwana', config('instansi.nama'));
        $this->assertNotEmpty(config('instansi.alamat'));
        $this->assertSame('img/RSU22Nirwana.png', config('instansi.logo'));
    }

    private function userSdm(): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_ekspor_karyawan_xlsx_terunduh(): void
    {
        Excel::fake();
        Karyawan::factory()->create();

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/karyawan?format=xlsx')
            ->assertOk();

        Excel::assertDownloaded('daftar-karyawan.xlsx');
    }

    public function test_ekspor_karyawan_pdf_content_type(): void
    {
        Karyawan::factory()->create(['nama_lengkap' => 'Orang Pdf']);

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/karyawan?format=pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_ekspor_mengikuti_filter(): void
    {
        Excel::fake();
        Karyawan::factory()->create(['nama_lengkap' => 'Cocok Filter', 'nip' => 'F-1']);
        Karyawan::factory()->create(['nama_lengkap' => 'Tidak Cocok', 'nip' => 'F-2']);

        $this->actingAs($this->userSdm())->get('/sdm/laporan/karyawan?format=xlsx&cari=F-1')->assertOk();

        Excel::assertDownloaded('daftar-karyawan.xlsx', function (KaryawanExport $export) {
            $nips = $export->query()->pluck('nip')->all();

            return $nips === ['F-1'];
        });
    }

    public function test_tanpa_kelola_sdm_ditolak(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($user)->get('/sdm/laporan/karyawan?format=pdf')->assertForbidden();
    }

    public function test_ekspor_pengingat_kontrak_xlsx(): void
    {
        Excel::fake();
        $k = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $k->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(11), 'tanggal_akhir' => now()->addDays(10)]);

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/pengingat-kontrak?format=xlsx')
            ->assertOk();

        Excel::assertDownloaded('pengingat-kontrak.xlsx');
    }

    public function test_ekspor_pengingat_kontrak_pdf(): void
    {
        $k = Karyawan::factory()->create(['nama_lengkap' => 'Hampir Habis']);
        Kontrak::factory()->create(['karyawan_id' => $k->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(11), 'tanggal_akhir' => now()->addDays(10)]);

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/pengingat-kontrak?format=pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
