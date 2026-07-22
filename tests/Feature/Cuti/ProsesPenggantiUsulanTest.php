<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Enums\StatusPengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\UsulanPenggantiMasuk;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesPenggantiUsulanTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $koor;

    protected Karyawan $pemohon;

    protected Karyawan $b;

    protected Karyawan $c;

    protected User $userKoor;

    protected User $userC;

    protected PengajuanCuti $cuti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();

        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->koor = Karyawan::factory()->pimpinanUnit($this->unit)->create();
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->b = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->c = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->userKoor = User::factory()->create(['karyawan_id' => $this->koor->id]);
        $this->userC = User::factory()->create(['karyawan_id' => $this->c->id]);

        $pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $this->cuti = PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-04', 4)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['karyawan_id' => $this->pemohon->id]);
        foreach (['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04'] as $t) {
            Jadwal::factory()->create([
                'karyawan_id' => $this->pemohon->id, 'tanggal' => $t, 'shift_id' => $pagi->id,
            ]);
        }
        ProsesPengganti::tetapkan($this->cuti, $this->b, $this->userKoor);
    }

    public function test_ajukan_diri_membuat_baris_usulan(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->cuti->fresh(), $this->c, Carbon::parse('2026-08-03'), $this->userC);

        $this->assertSame(StatusPengganti::Usulan, $usulan->status);
        $this->assertSame('2026-08-03', $usulan->tanggal_mulai->toDateString());
        $this->assertSame('2026-08-04', $usulan->tanggal_selesai->toDateString());
        // Usulan belum mengubah jadwal siapa pun.
        $this->assertSame(0, Jadwal::where('karyawan_id', $this->c->id)->count());
        $this->assertSame(4, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
    }

    public function test_ajukan_diri_menotifikasi_koordinator(): void
    {
        ProsesPengganti::ajukanDiri($this->cuti->fresh(), $this->c, Carbon::parse('2026-08-03'), $this->userC);

        Notification::assertSentTo($this->userKoor, UsulanPenggantiMasuk::class);
    }

    public function test_tolak_pengaju_beda_unit(): void
    {
        $lain = OrgUnit::factory()->create();
        $luar = Karyawan::factory()->staffUnit($lain)->create();
        $userLuar = User::factory()->create(['karyawan_id' => $luar->id]);

        $this->expectException(ProsesPenggantiException::class);
        $this->expectExceptionMessageMatches('/satu unit/');

        ProsesPengganti::ajukanDiri($this->cuti->fresh(), $luar, Carbon::parse('2026-08-03'), $userLuar);
    }

    public function test_acc_usulan_menjalankan_estafet(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->cuti->fresh(), $this->c, Carbon::parse('2026-08-03'), $this->userC);

        ProsesPengganti::accUsulan($usulan->fresh(), $this->userKoor);

        $this->assertSame(0, PenggantiCuti::usulan()->count());
        $this->assertSame(2, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
        $this->assertSame(2, Jadwal::where('karyawan_id', $this->c->id)->salinanPengganti()->count());
    }

    public function test_acc_oleh_bukan_koordinator_ditolak(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->cuti->fresh(), $this->c, Carbon::parse('2026-08-03'), $this->userC);
        $orangLain = User::factory()->create(['karyawan_id' => $this->b->id]);

        $this->expectException(ProsesPenggantiException::class);

        ProsesPengganti::accUsulan($usulan->fresh(), $orangLain);
    }

    public function test_tolak_usulan_menghapus_baris(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->cuti->fresh(), $this->c, Carbon::parse('2026-08-03'), $this->userC);

        ProsesPengganti::tolakUsulan($usulan->fresh(), $this->userKoor);

        $this->assertSame(0, PenggantiCuti::usulan()->count());
        $this->assertSame(4, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
    }

    public function test_pemohon_tak_bisa_ajukan_diri(): void
    {
        $userPemohon = User::factory()->create(['karyawan_id' => $this->pemohon->id]);

        $this->expectException(ProsesPenggantiException::class);

        ProsesPengganti::ajukanDiri($this->cuti->fresh(), $this->pemohon, Carbon::parse('2026-08-02'), $userPemohon);
    }
}
