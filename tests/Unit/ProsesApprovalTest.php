<?php

namespace Tests\Unit;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Enums\Role;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\CutiPerluPersetujuan;
use App\Support\ProsesApproval;
use App\Enums\KodeJenisCuti;
use App\Models\JenisCuti;
use App\Notifications\CutiDisetujui;
use App\Support\ProsesApprovalException;
use App\Support\SaldoCuti;
use Illuminate\Support\Carbon;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    /** Rakit pengajuan + rantai 2 tahap manual (koordinator lalu HRD). */
    private function pengajuanRantai(): array
    {
        $pemohon = Karyawan::factory()->create();
        $koor = Karyawan::factory()->create();
        $hrd = Karyawan::factory()->create();
        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);

        $p = PengajuanCuti::factory()->for($pemohon)->status(StatusPengajuanCuti::Diajukan)->create();
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $koor->id, 'peran' => 'koordinator', 'status' => StatusApproval::Menunggu]);
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 2, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => StatusApproval::Menunggu]);

        return compact('p', 'koor', 'hrd', 'userKoor', 'userHrd');
    }

    public function test_setujui_tahap_pertama_maju_ke_diproses_dan_notif_berikutnya(): void
    {
        Notification::fake();
        ['p' => $p, 'userKoor' => $userKoor, 'userHrd' => $userHrd] = $this->pengajuanRantai();

        ProsesApproval::setujui($p->tahapAktif(), $userKoor, 'ok');

        $this->assertSame(StatusPengajuanCuti::Diproses, $p->refresh()->status);
        $step1 = $p->approval()->where('urutan', 1)->first();
        $this->assertSame(StatusApproval::Setuju, $step1->status);
        $this->assertSame('ok', $step1->catatan);
        $this->assertNotNull($step1->acted_at);
        $this->assertSame(2, $p->tahapAktif()->urutan); // tahap aktif kini HRD
        Notification::assertSentTo($userHrd, CutiPerluPersetujuan::class);
    }

    public function test_setujui_tahap_terakhir_jadi_disetujui_dan_notif_pemohon(): void
    {
        Notification::fake();
        $pemohon = Karyawan::factory()->create();
        $userPemohon = User::factory()->create(['karyawan_id' => $pemohon->id]);
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);

        $p = PengajuanCuti::factory()->for($pemohon)->jenis(KodeJenisCuti::CutiSakit)
            ->status(StatusPengajuanCuti::Diproses)->create(['jumlah_hari' => 2]);
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => StatusApproval::Menunggu]);

        ProsesApproval::setujui($p->tahapAktif(), $userHrd);

        $this->assertSame(StatusPengajuanCuti::Disetujui, $p->refresh()->status);
        Notification::assertSentTo($userPemohon, CutiDisetujui::class);
    }

    public function test_guard_jatah_kurang_cuti_tahunan_saat_final(): void
    {
        Notification::fake();
        Carbon::setTestNow('2027-06-01');
        // Eligibility & jatah butuh KONTRAK nyata (Pkwt/Tetap), bukan tanggal_masuk.
        $pemohon = Karyawan::factory()->create();
        \App\Models\Kontrak::factory()->for($pemohon)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);
        // Periode aktif mulai 2027-03-01. 11 hari cuti-tahunan sudah disetujui di periode itu.
        PengajuanCuti::factory()->for($pemohon)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['tanggal_mulai' => '2027-06-02', 'tanggal_selesai' => '2027-06-02', 'jumlah_hari' => 11]);

        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $p = PengajuanCuti::factory()->for($pemohon)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Diproses)
            ->create(['tanggal_mulai' => '2027-06-10', 'tanggal_selesai' => '2027-06-12', 'jumlah_hari' => 3]);
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $hrd->id, 'peran' => 'hrd', 'status' => StatusApproval::Menunggu]);

        // jatah 12 − terpakai 11 = sisa 1; diminta 3 → ditolak.
        try {
            $this->expectException(ProsesApprovalException::class);
            ProsesApproval::setujui($p->tahapAktif(), $userHrd);
        } finally {
            $this->assertSame(StatusPengajuanCuti::Diproses, $p->refresh()->status); // tak berubah
            Carbon::setTestNow();
        }
    }

    public function test_tolak_membuat_pengajuan_ditolak_dan_notif_pemohon(): void
    {
        Notification::fake();
        $pemohon = Karyawan::factory()->create();
        $userPemohon = User::factory()->create(['karyawan_id' => $pemohon->id]);
        $koor = Karyawan::factory()->create();
        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);

        $p = PengajuanCuti::factory()->for($pemohon)->status(StatusPengajuanCuti::Diajukan)->create();
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $koor->id, 'peran' => 'koordinator', 'status' => StatusApproval::Menunggu]);

        \App\Support\ProsesApproval::tolak($p->tahapAktif(), $userKoor, 'dokumen kurang');

        $this->assertSame(StatusPengajuanCuti::Ditolak, $p->refresh()->status);
        $this->assertSame(StatusApproval::Tolak, $p->approval()->where('urutan', 1)->first()->status);
        Notification::assertSentTo($userPemohon, \App\Notifications\CutiDitolak::class);
    }

    public function test_hrd_self_approve_tahap_final_sendiri(): void
    {
        Notification::fake();
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');
        $direktur = Karyawan::factory()->create();

        // Rantai pemohon HRD = Direktur final (approver bukan HRD).
        $p = PengajuanCuti::factory()->for($hrd)->jenis(KodeJenisCuti::CutiSakit)
            ->status(StatusPengajuanCuti::Diajukan)->create(['jumlah_hari' => 1]);
        ApprovalCuti::create(['pengajuan_cuti_id' => $p->id, 'urutan' => 1, 'approver_id' => $direktur->id, 'peran' => 'direktur', 'status' => StatusApproval::Menunggu]);

        \App\Support\ProsesApproval::setujui($p->tahapAktif(), $userHrd);

        $this->assertSame(StatusPengajuanCuti::Disetujui, $p->refresh()->status);
    }

    public function test_hrd_batalkan_cuti_disetujui_jatah_balik(): void
    {
        Notification::fake();
        $pemohon = Karyawan::factory()->create();
        $userPemohon = User::factory()->create(['karyawan_id' => $pemohon->id]);
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');

        $p = PengajuanCuti::factory()->for($pemohon)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Disetujui)->create(['jumlah_hari' => 3]);

        \App\Support\ProsesApproval::batalkanOlehHrd($p, $userHrd, 'salah input');

        $p->refresh();
        $this->assertSame(StatusPengajuanCuti::Dibatalkan, $p->status);
        $this->assertSame($userHrd->id, $p->dibatalkan_oleh);
        $this->assertSame('salah input', $p->alasan_batal);
        Notification::assertSentTo($userPemohon, \App\Notifications\CutiDibatalkan::class);
    }

    public function test_batal_hrd_tolak_bila_status_bukan_disetujui(): void
    {
        $hrd = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrd->id]);
        $userHrd->assignRole('HRD');
        $p = PengajuanCuti::factory()->status(StatusPengajuanCuti::Diajukan)->create();

        $this->expectException(\App\Support\ProsesApprovalException::class);
        \App\Support\ProsesApproval::batalkanOlehHrd($p, $userHrd, 'x');
    }
}