<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProsesPenggantiLowonganEstafetTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $nonaktif;

    private Karyawan $b;

    private Karyawan $c;

    private Karyawan $koor;

    private User $userKoor;

    private User $userC;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = OrgUnit::factory()->create();
        $this->shift = Shift::factory()->create([
            'org_unit_id' => $unit->id, 'kode' => 'P', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);

        // Kepala unit derived = karyawan level tertinggi (≥2) di unit.
        $this->koor = Karyawan::factory()->pimpinanUnit($unit)->create();
        $this->userKoor = User::factory()->create(['karyawan_id' => $this->koor->id]);

        $this->nonaktif = Karyawan::factory()->staffUnit($unit)->create([
            'status' => 'nonaktif', 'tanggal_nonaktif' => '2026-08-01',
        ]);
        $this->b = Karyawan::factory()->staffUnit($unit)->create();
        $this->c = Karyawan::factory()->staffUnit($unit)->create();
        $this->userC = User::factory()->create(['karyawan_id' => $this->c->id]);

        foreach (['2026-08-03', '2026-08-05', '2026-08-07'] as $tgl) {
            Jadwal::create(['karyawan_id' => $this->nonaktif->id, 'tanggal' => $tgl, 'shift_id' => $this->shift->id]);
        }

        ProsesPengganti::tetapkan($this->nonaktif, $this->b, $this->userKoor);
    }

    public function test_alihkan_memotong_rentang_lowongan_terbuka(): void
    {
        ProsesPengganti::alihkan($this->nonaktif, Carbon::parse('2026-08-05'), $this->c, $this->userKoor);

        $lama = PenugasanPengganti::where('karyawan_id', $this->b->id)->first();
        $baru = PenugasanPengganti::where('karyawan_id', $this->c->id)->first();

        $this->assertSame('2026-08-04', $lama->tanggal_selesai->toDateString());
        $this->assertNull($baru->tanggal_selesai);          // ujung tetap terbuka
        $this->assertSame(1, Jadwal::where('karyawan_id', $this->b->id)->whereNotNull('pengganti_id')->count());
        $this->assertSame(2, Jadwal::where('karyawan_id', $this->c->id)->whereNotNull('pengganti_id')->count());
    }

    public function test_rekan_satu_unit_bisa_ajukan_diri_atas_lowongan(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->nonaktif, $this->c, Carbon::parse('2026-08-05'), $this->userC);

        $this->assertSame(StatusPengganti::Usulan, $usulan->status);
        $this->assertSame($this->nonaktif->id, $usulan->karyawan_digantikan_id);
        $this->assertNull($usulan->pengajuan_cuti_id);
    }

    public function test_orang_luar_unit_ditolak(): void
    {
        $luar = Karyawan::factory()->create();
        $userLuar = User::factory()->create(['karyawan_id' => $luar->id]);

        $this->expectException(ProsesPenggantiException::class);
        ProsesPengganti::ajukanDiri($this->nonaktif, $luar, Carbon::parse('2026-08-05'), $userLuar);
    }

    public function test_acc_usulan_lowongan_menjadikan_pengaju_pengganti_aktif(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->nonaktif, $this->c, Carbon::parse('2026-08-05'), $this->userC);

        ProsesPengganti::accUsulan($usulan->fresh(), $this->userKoor);

        $this->assertDatabaseMissing('penugasan_pengganti', ['id' => $usulan->id]);
        $this->assertSame(2, Jadwal::where('karyawan_id', $this->c->id)->whereNotNull('pengganti_id')->count());
    }

    public function test_tolak_usulan_lowongan_tak_mengubah_cakupan(): void
    {
        $usulan = ProsesPengganti::ajukanDiri($this->nonaktif, $this->c, Carbon::parse('2026-08-05'), $this->userC);

        ProsesPengganti::tolakUsulan($usulan->fresh(), $this->userKoor);

        $this->assertDatabaseMissing('penugasan_pengganti', ['id' => $usulan->id]);
        $this->assertSame(3, Jadwal::where('karyawan_id', $this->b->id)->whereNotNull('pengganti_id')->count());
    }
}
