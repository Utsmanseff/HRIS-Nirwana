<?php

namespace Tests\Feature\Cuti;

use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratCutiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function cutiDenganSurat(): PengajuanCuti
    {
        Storage::fake('local');
        $pemohon = Karyawan::factory()->create();
        $p = PengajuanCuti::factory()->for($pemohon)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['surat_path' => 'cuti/x/surat.pdf']);
        Storage::disk('local')->put('cuti/x/surat.pdf', '%PDF-1.4 dummy');

        return $p;
    }

    public function test_pemohon_boleh_lihat(): void
    {
        $p = $this->cutiDenganSurat();
        $user = User::factory()->create(['karyawan_id' => $p->karyawan_id]);

        $this->actingAs($user)->get(route('cuti.surat', $p))->assertOk();
    }

    public function test_hrd_boleh_lihat(): void
    {
        $p = $this->cutiDenganSurat();
        $hrd = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $hrd->assignRole(Role::Hrd->value);

        $this->actingAs($hrd)->get(route('cuti.surat', $p))->assertOk();
    }

    public function test_approver_di_rantai_boleh_lihat(): void
    {
        $p = $this->cutiDenganSurat();
        $approver = Karyawan::factory()->create();
        ApprovalCuti::create([
            'pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $approver->id,
            'peran' => PeranApproval::Koordinator, 'status' => StatusApproval::Setuju,
        ]);
        $userApprover = User::factory()->create(['karyawan_id' => $approver->id]);

        $this->actingAs($userApprover)->get(route('cuti.surat', $p))->assertOk();
    }

    public function test_orang_lain_ditolak(): void
    {
        $p = $this->cutiDenganSurat();
        $lain = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($lain)->get(route('cuti.surat', $p))->assertForbidden();
    }

    public function test_surat_path_kosong_ditolak_meski_pemohon(): void
    {
        Storage::fake('local');
        $pemohon = Karyawan::factory()->create();
        $p = PengajuanCuti::factory()->for($pemohon)->status(StatusPengajuanCuti::Diajukan)->create();
        $user = User::factory()->create(['karyawan_id' => $pemohon->id]);

        $this->actingAs($user)->get(route('cuti.surat', $p))->assertForbidden();
    }
}
