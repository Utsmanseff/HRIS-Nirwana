<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Exports\KaryawanExport;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\OrgUnit;
use App\Models\User;
use App\Support\NamaFileLaporan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_config_instansi_baris_kop_resmi(): void
    {
        $this->assertSame('RUMAH SAKIT UMUM NIRWANA', config('instansi.nama_resmi'));
        $this->assertSame('Jl. Panglima Batur Timur No. 42 Banjarbaru Kalimantan Selatan', config('instansi.alamat'));
        $this->assertSame('Telp. 0511-674 9722 / 0821 5084 1882', config('instansi.telp'));
        $this->assertSame('Email: official@rsunirwana.id | Website: https://rsunirwana.id', config('instansi.email_web'));
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
        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32));
        Karyawan::factory()->create();

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/karyawan?format=xlsx')
            ->assertOk();

        Excel::assertDownloaded(NamaFileLaporan::buat('daftar-karyawan', [], 'xlsx'));
        Carbon::setTestNow();
    }

    public function test_nama_file_karyawan_mengandung_filter_dan_tanggal(): void
    {
        Excel::fake();
        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32));
        $unit = OrgUnit::factory()->create(['nama' => 'Unit Farmasi']);

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/karyawan?format=xlsx&status=aktif&unit_id='.$unit->id.'&level=1')
            ->assertOk();

        Excel::assertDownloaded(
            NamaFileLaporan::buat('daftar-karyawan', ['aktif', 'Unit Farmasi', 'L1'], 'xlsx'),
        );
        Carbon::setTestNow();
    }

    public function test_nama_file_pdf_mengandung_tanggal(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32));
        Karyawan::factory()->create();

        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/karyawan?format=pdf')
            ->assertDownload(NamaFileLaporan::buat('daftar-karyawan', [], 'pdf'));
        Carbon::setTestNow();
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

        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32));
        $this->actingAs($this->userSdm())->get('/sdm/laporan/karyawan?format=xlsx&cari=F-1')->assertOk();

        Excel::assertDownloaded(NamaFileLaporan::buat('daftar-karyawan', ['F-1'], 'xlsx'), function (KaryawanExport $export) {
            $nips = $export->query()->pluck('nip')->all();

            return $nips === ['F-1'];
        });
        Carbon::setTestNow();
    }

    public function test_excel_karyawan_punya_kop_instansi(): void
    {
        $headings = (new KaryawanExport([], 'Status: Aktif'))->headings();

        $this->assertSame([config('instansi.nama_resmi')], $headings[0]);
        $this->assertSame([config('instansi.alamat')], $headings[1]);
        $this->assertContains('Status: Aktif', $headings[4]);
        $this->assertContains('NIP', end($headings));
    }

    public function test_excel_karyawan_registrasi_events_styling(): void
    {
        $this->assertNotEmpty((new KaryawanExport([], ''))->registerEvents());
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

        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32));
        $this->actingAs($this->userSdm())
            ->get('/sdm/laporan/pengingat-kontrak?format=xlsx')
            ->assertOk();

        Excel::assertDownloaded(NamaFileLaporan::buat('pengingat-kontrak', [], 'xlsx'));
        Carbon::setTestNow();
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

    public function test_daftar_karyawan_punya_link_ekspor_dengan_filter_aktif(): void
    {
        $this->actingAs($this->userSdm())
            ->get('/sdm/karyawan')
            ->assertSee('/sdm/laporan/karyawan?format=xlsx', false)
            ->assertSee('/sdm/laporan/karyawan?format=pdf', false);
    }

    public function test_dashboard_punya_link_ekspor_pengingat(): void
    {
        $this->actingAs($this->userSdm())
            ->get('/dashboard')
            ->assertSee('/sdm/laporan/pengingat-kontrak?format=xlsx', false);
    }
}
