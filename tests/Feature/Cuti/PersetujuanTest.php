<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\Persetujuan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PersetujuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    public function test_route_persetujuan_butuh_gate_approve_cuti(): void
    {
        // Staff biasa (tanpa bawahan, bukan HRD) → 403.
        $unit = OrgUnit::factory()->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $user = User::factory()->create(['karyawan_id' => $staff->id]);
        $user->assignRole('Karyawan');

        $this->actingAs($user)->get('/cuti/persetujuan')->assertForbidden();
    }

    public function test_hrd_bisa_buka_persetujuan(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole('HRD');

        $this->actingAs($user)->get('/cuti/persetujuan')->assertOk();

        Livewire::actingAs($user)->test(Persetujuan::class)
            ->assertOk()
            ->assertSet('tab', 'perlu-aksi');
    }

    public function test_perlu_aksi_hanya_pengajuan_di_tahap_saya(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $unit = \App\Models\OrgUnit::factory()->create();
        $koor = \App\Models\Karyawan::factory()->pimpinanUnit($unit)->create();
        $userKoor = \App\Models\User::factory()->create(['karyawan_id' => $koor->id]);
        $userKoor->assignRole('Karyawan');
        $hrd = \App\Models\Karyawan::factory()->create();
        $userHrd = \App\Models\User::factory()->create(['karyawan_id' => $hrd->id]);

        // Pengajuan menunggu di tahap koordinator (urutan 1) lalu HRD (urutan 2).
        $pemohon = \App\Models\Karyawan::factory()->staffUnit($unit)->create();
        $p = \App\Models\PengajuanCuti::factory()->for($pemohon)->status(\App\Enums\StatusPengajuanCuti::Diajukan)->create();
        \App\Models\ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $koor->id, 'peran' => 'koordinator', 'status' => \App\Enums\StatusApproval::Menunggu]);
        \App\Models\ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 2, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => \App\Enums\StatusApproval::Menunggu]);

        // Koordinator lihat (tahap 1 aktif = dia).
        \Livewire\Livewire::actingAs($userKoor)->test(\App\Livewire\Cuti\Persetujuan::class)
            ->assertSee($pemohon->nama_lengkap);

        // Setujui tahap 1 → kini tahap HRD; koordinator tak lagi lihat.
        \App\Support\ProsesApproval::setujui($p->tahapAktif(), $userKoor);
        \Livewire\Livewire::actingAs($userKoor)->test(\App\Livewire\Cuti\Persetujuan::class)
            ->assertDontSee($pemohon->nama_lengkap);
    }

    public function test_approver_setujui_dari_komponen(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $unit = \App\Models\OrgUnit::factory()->create();
        $hrd = \App\Models\Karyawan::factory()->create();
        $userHrd = \App\Models\User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');
        $pemohon = \App\Models\Karyawan::factory()->staffUnit($unit)->create();

        $p = \App\Models\PengajuanCuti::factory()->for($pemohon)->jenis(\App\Enums\KodeJenisCuti::CutiSakit)
            ->status(\App\Enums\StatusPengajuanCuti::Diproses)->create(['jumlah_hari' => 1]);
        \App\Models\ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => \App\Enums\StatusApproval::Menunggu]);

        \Livewire\Livewire::actingAs($userHrd)->test(\App\Livewire\Cuti\Persetujuan::class)
            ->call('tinjau', $p->id)
            ->set('catatan', 'ok')
            ->call('setujui')
            ->assertHasNoErrors();

        $this->assertSame(\App\Enums\StatusPengajuanCuti::Disetujui, $p->refresh()->status);
    }

    public function test_approver_tolak_wajib_catatan(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $hrd = \App\Models\Karyawan::factory()->create();
        $userHrd = \App\Models\User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');
        $pemohon = \App\Models\Karyawan::factory()->staffUnit($unit)->create();
        $p = \App\Models\PengajuanCuti::factory()->for($pemohon)->status(\App\Enums\StatusPengajuanCuti::Diproses)->create();
        \App\Models\ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => \App\Enums\StatusApproval::Menunggu]);

        \Livewire\Livewire::actingAs($userHrd)->test(\App\Livewire\Cuti\Persetujuan::class)
            ->call('tinjau', $p->id)
            ->set('catatan', '')
            ->call('tolak')
            ->assertHasErrors('catatan');

        $this->assertSame(\App\Enums\StatusPengajuanCuti::Diproses, $p->refresh()->status);
    }
}
