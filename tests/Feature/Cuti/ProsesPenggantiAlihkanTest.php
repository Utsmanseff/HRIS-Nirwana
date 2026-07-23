<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesPenggantiAlihkanTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $pemohon;

    protected Karyawan $b;

    protected Karyawan $c;

    protected Shift $pagi;

    protected User $aktor;

    protected PengajuanCuti $cuti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();
        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->b = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->c = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $this->aktor = User::factory()->create(['karyawan_id' => $this->pemohon->id]);

        $this->cuti = PengajuanCuti::factory()->rentang('2026-08-01', '2026-08-05', 5)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['karyawan_id' => $this->pemohon->id]);

        foreach (['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04', '2026-08-05'] as $t) {
            Jadwal::factory()->create([
                'karyawan_id' => $this->pemohon->id, 'tanggal' => $t, 'shift_id' => $this->pagi->id,
            ]);
        }
        ProsesPengganti::tetapkan($this->cuti, $this->b, $this->aktor);
    }

    private function tanggalSalinan(Karyawan $kar): array
    {
        return Jadwal::where('karyawan_id', $kar->id)->salinanPengganti()
            ->orderBy('tanggal')->pluck('tanggal')
            ->map(fn ($t) => Carbon::parse($t)->toDateString())->all();
    }

    public function test_potong_rentang_lama_dan_buat_rentang_baru(): void
    {
        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-03'), $this->c, $this->aktor);

        $baris = $this->cuti->fresh()->pengganti()->aktif()->get();
        $this->assertCount(2, $baris);
        $this->assertSame($this->b->id, $baris[0]->karyawan_id);
        $this->assertSame('2026-08-02', $baris[0]->tanggal_selesai->toDateString());
        $this->assertSame($this->c->id, $baris[1]->karyawan_id);
        $this->assertSame('2026-08-03', $baris[1]->tanggal_mulai->toDateString());
        $this->assertSame('2026-08-05', $baris[1]->tanggal_selesai->toDateString());
    }

    public function test_regen_jadwal_pindah_ke_pengganti_baru(): void
    {
        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-03'), $this->c, $this->aktor);

        $this->assertSame(['2026-08-01', '2026-08-02'], $this->tanggalSalinan($this->b));
        $this->assertSame(['2026-08-03', '2026-08-04', '2026-08-05'], $this->tanggalSalinan($this->c));
    }

    public function test_berantai_c_ke_d(): void
    {
        $d = Karyawan::factory()->staffUnit($this->unit)->create();

        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-03'), $this->c, $this->aktor);
        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-05'), $d, $this->aktor);

        $this->assertSame(['2026-08-01', '2026-08-02'], $this->tanggalSalinan($this->b));
        $this->assertSame(['2026-08-03', '2026-08-04'], $this->tanggalSalinan($this->c));
        $this->assertSame(['2026-08-05'], $this->tanggalSalinan($d));
        $this->assertCount(3, $this->cuti->fresh()->pengganti()->aktif()->get());
    }

    public function test_alih_dari_hari_pertama_membuang_baris_lama(): void
    {
        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-01'), $this->c, $this->aktor);

        $baris = $this->cuti->fresh()->pengganti()->aktif()->get();
        $this->assertCount(1, $baris);
        $this->assertSame($this->c->id, $baris[0]->karyawan_id);
        $this->assertSame([], $this->tanggalSalinan($this->b));
        $this->assertCount(5, $this->tanggalSalinan($this->c));
    }

    public function test_tolak_bila_cuti_belum_disetujui(): void
    {
        $cuti = PengajuanCuti::factory()->rentang('2026-09-01', '2026-09-03', 3)
            ->create(['karyawan_id' => $this->pemohon->id]);

        $this->expectException(ProsesPenggantiException::class);

        ProsesPengganti::alihkan($cuti, Carbon::parse('2026-09-02'), $this->c, $this->aktor);
    }

    public function test_tolak_bila_tanggal_di_luar_masa_cuti(): void
    {
        $this->expectException(ProsesPenggantiException::class);

        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-09'), $this->c, $this->aktor);
    }

    public function test_tolak_bila_pengganti_baru_bentrok(): void
    {
        Jadwal::factory()->create([
            'karyawan_id' => $this->c->id, 'tanggal' => '2026-08-04', 'shift_id' => $this->pagi->id,
        ]);

        $this->expectException(ProsesPenggantiException::class);
        $this->expectExceptionMessageMatches('/2026-08-04/');

        ProsesPengganti::alihkan($this->cuti->fresh(), Carbon::parse('2026-08-03'), $this->c, $this->aktor);
    }
}
