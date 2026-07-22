<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\DitunjukJadiPengganti;
use App\Support\ProsesPengganti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesPenggantiGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $pemohon;

    protected Karyawan $b;

    protected Shift $pagi;

    protected User $aktor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();
        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->b = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $this->aktor = User::factory()->create(['karyawan_id' => $this->pemohon->id]);
    }

    private function cutiDisetujui(): PengajuanCuti
    {
        return PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-03', 3)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['karyawan_id' => $this->pemohon->id]);
    }

    private function jadwalPemohon(array $tanggal): void
    {
        foreach ($tanggal as $t) {
            Jadwal::factory()->create([
                'karyawan_id' => $this->pemohon->id, 'tanggal' => $t, 'shift_id' => $this->pagi->id,
            ]);
        }
    }

    /**
     * Rencana dibuat LANGSUNG lewat factory (bukan `tetapkan`) supaya test ini
     * murni menguji generate — `tetapkan` sendiri memanggil generate.
     */
    private function rencana(PengajuanCuti $cuti, string $mulai, string $selesai): PenggantiCuti
    {
        return PenggantiCuti::factory()->create([
            'pengajuan_cuti_id' => $cuti->id,
            'karyawan_id' => $this->b->id,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
        ]);
    }

    public function test_salin_shift_pemohon_ke_pengganti_bertanda(): void
    {
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01', '2026-08-02', '2026-08-03']);
        $rencana = $this->rencana($cuti, '2026-08-01', '2026-08-03');

        $dibuat = ProsesPengganti::generateSaatDisetujui($cuti->fresh());

        $this->assertSame(3, $dibuat);
        $salinan = Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->get();
        $this->assertCount(3, $salinan);
        $this->assertTrue($salinan->every(fn (Jadwal $j) => $j->pengganti_cuti_id === $rencana->id));
        $this->assertTrue($salinan->every(fn (Jadwal $j) => $j->shift_id === $this->pagi->id));
    }

    public function test_hari_pemohon_libur_dilewati(): void
    {
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01', '2026-08-03']); // 08-02 libur
        $this->rencana($cuti, '2026-08-01', '2026-08-03');

        $dibuat = ProsesPengganti::generateSaatDisetujui($cuti->fresh());

        $this->assertSame(2, $dibuat);
        $this->assertFalse(Jadwal::where('karyawan_id', $this->b->id)->whereDate('tanggal', '2026-08-02')->exists());
    }

    public function test_dinas_ganda_pengganti_tetap_punya_shift_sendiri(): void
    {
        $sore = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'S', 'jam_mulai' => '14:00:00', 'jam_selesai' => '21:00:00',
        ]);
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01']);
        Jadwal::factory()->create(['karyawan_id' => $this->b->id, 'tanggal' => '2026-08-01', 'shift_id' => $sore->id]);
        $this->rencana($cuti, '2026-08-01', '2026-08-01');

        ProsesPengganti::generateSaatDisetujui($cuti->fresh());

        $this->assertSame(2, Jadwal::where('karyawan_id', $this->b->id)->whereDate('tanggal', '2026-08-01')->count());
    }

    public function test_idempoten_dipanggil_dua_kali(): void
    {
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01', '2026-08-02']);
        $this->rencana($cuti, '2026-08-01', '2026-08-02');

        $pertama = ProsesPengganti::generateSaatDisetujui($cuti->fresh());
        $kedua = ProsesPengganti::generateSaatDisetujui($cuti->fresh());

        $this->assertSame(2, $pertama);
        $this->assertSame(0, $kedua);
        $this->assertSame(2, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
    }

    public function test_cuti_belum_disetujui_tidak_generate(): void
    {
        $cuti = PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-03', 3)
            ->create(['karyawan_id' => $this->pemohon->id]); // status diajukan
        $this->jadwalPemohon(['2026-08-01']);
        $this->rencana($cuti, '2026-08-01', '2026-08-03');

        $this->assertSame(0, ProsesPengganti::generateSaatDisetujui($cuti->fresh()));
        $this->assertSame(0, Jadwal::salinanPengganti()->count());
    }

    public function test_notif_ke_pengganti_saat_salinan_terbentuk(): void
    {
        $userB = User::factory()->create(['karyawan_id' => $this->b->id]);
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01']);
        $this->rencana($cuti, '2026-08-01', '2026-08-01');

        ProsesPengganti::generateSaatDisetujui($cuti->fresh());

        Notification::assertSentTo($userB, DitunjukJadiPengganti::class);
    }

    public function test_rencana_usulan_tidak_ikut_digenerate(): void
    {
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01']);
        PenggantiCuti::factory()->usulan()->create([
            'pengajuan_cuti_id' => $cuti->id, 'karyawan_id' => $this->b->id,
            'tanggal_mulai' => '2026-08-01', 'tanggal_selesai' => '2026-08-03',
        ]);

        $this->assertSame(0, ProsesPengganti::generateSaatDisetujui($cuti->fresh()));
    }

    public function test_tetapkan_pada_cuti_disetujui_langsung_materialisasi(): void
    {
        $cuti = $this->cutiDisetujui();
        $this->jadwalPemohon(['2026-08-01', '2026-08-02']);

        ProsesPengganti::tetapkan($cuti, $this->b, $this->aktor);

        $this->assertSame(2, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
    }
}
