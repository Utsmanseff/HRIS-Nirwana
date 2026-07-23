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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_nama_file_unduhan_pakai_tanggal_approval_terakhir(): void
    {
        $p = $this->cutiDenganSurat();
        ApprovalCuti::create([
            'pengajuan_cuti_id' => $p->id, 'urutan' => 1,
            'approver_id' => Karyawan::factory()->create()->id,
            'peran' => PeranApproval::Hrd, 'status' => StatusApproval::Setuju,
            'acted_at' => Carbon::create(2026, 7, 17, 10, 0),
        ]);
        $user = User::factory()->create(['karyawan_id' => $p->karyawan_id]);

        $res = $this->actingAs($user)->get(route('cuti.surat', $p));

        $res->assertOk();
        $this->assertStringContainsString(
            'surat-keterangan-cuti_'.Str::slug($p->karyawan->nama_lengkap).'_20260717.pdf',
            $res->headers->get('content-disposition'),
        );
    }

    /** Tanpa baris approval (data lama) → jatuh ke tanggal hari ini, bukan error. */
    public function test_nama_file_tanpa_approval_pakai_hari_ini(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 20, 8, 0));
        $p = $this->cutiDenganSurat();
        $user = User::factory()->create(['karyawan_id' => $p->karyawan_id]);

        $res = $this->actingAs($user)->get(route('cuti.surat', $p));

        $res->assertOk();
        $this->assertStringContainsString('_20260720.pdf', $res->headers->get('content-disposition'));
        Carbon::setTestNow();
    }
}
