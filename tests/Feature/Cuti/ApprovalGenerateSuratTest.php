<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Support\ProsesApproval;
use App\Support\RantaiApproval;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalGenerateSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, JenisCutiSeeder::class]);
        Storage::fake('local');
        Notification::fake();
    }

    public function test_approve_tahap_terakhir_mengisi_surat_path_dan_file_kebentuk(): void
    {
        $hrdKar = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrdKar->id]);
        $userHrd->assignRole(Role::Hrd->value);

        $pemohon = Karyawan::factory()->create();
        $p = PengajuanCuti::factory()->for($pemohon)->jenis(KodeJenisCuti::CutiSakit)->create();
        RantaiApproval::bangunUntuk($p);

        $this->assertNull($p->surat_path);

        ProsesApproval::setujui($p->tahapAktif(), $userHrd);
        $p->refresh();

        $this->assertNotNull($p->surat_path);
        Storage::disk('local')->assertExists($p->surat_path);
    }

    public function test_belum_tahap_terakhir_surat_path_masih_kosong(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit)->create();
        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);
        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);

        $pemohon = Karyawan::factory()->staffUnit($unit)->create();
        $p = PengajuanCuti::factory()->for($pemohon)->jenis(KodeJenisCuti::CutiSakit)->create();
        RantaiApproval::bangunUntuk($p);

        ProsesApproval::setujui($p->tahapAktif(), $userKoor);
        $p->refresh();

        $this->assertNull($p->surat_path);
    }
}
