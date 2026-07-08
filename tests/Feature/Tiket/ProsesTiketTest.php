<?php

namespace Tests\Feature\Tiket;

use App\Enums\JenisTiket;
use App\Enums\StatusAset;
use App\Enums\StatusTiket;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\Tiket;
use App\Models\User;
use App\Support\ProsesTiket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProsesTiketTest extends TestCase
{
    use RefreshDatabase;

    public function test_mulai_set_respon_sekali(): void
    {
        $t = Tiket::factory()->status(StatusTiket::Baru)->create(['waktu_respon' => null]);
        $u = User::factory()->create();

        ProsesTiket::mulai($t, $u);
        $t->refresh();
        $this->assertSame(StatusTiket::Diproses, $t->status);
        $this->assertNotNull($t->waktu_respon);

        // Panggil lagi tak menggeser waktu_respon.
        $respon = $t->waktu_respon;
        Carbon::setTestNow(now()->addHour());
        // Status sudah Diproses → mulai() menolak; pastikan waktu_respon tetap.
        try {
            ProsesTiket::mulai($t->fresh(), $u);
        } catch (\RuntimeException $e) {
            // diharapkan
        }
        Carbon::setTestNow();
        $this->assertEquals($respon->format('Y-m-d H:i:s'), $t->fresh()->waktu_respon->format('Y-m-d H:i:s'));
    }

    public function test_perbaikan_aset_jadi_dalam_perbaikan_lalu_baik(): void
    {
        $aset = Aset::factory()->create(['status' => StatusAset::Baik->value]);
        $t = Tiket::factory()->jenis(JenisTiket::Perbaikan)->status(StatusTiket::Baru)
            ->create(['inventaris_id' => $aset->id, 'waktu_respon' => null]);
        $u = User::factory()->create();

        ProsesTiket::mulai($t, $u);
        $this->assertSame(StatusAset::DalamPerbaikan, $aset->fresh()->status);

        ProsesTiket::selesai($t->fresh(), $u, 'Sudah diganti kabel.');
        $this->assertSame(StatusAset::Baik, $aset->fresh()->status);
    }

    public function test_selesai_isi_semua_field(): void
    {
        $t = Tiket::factory()->status(StatusTiket::Baru)->create(['waktu_respon' => null]);
        $u = User::factory()->create();

        ProsesTiket::selesai($t, $u, 'Beres.');
        $t->refresh();
        $this->assertSame(StatusTiket::Selesai, $t->status);
        $this->assertNotNull($t->waktu_respon); // diisi karena masih null
        $this->assertNotNull($t->waktu_selesai);
        $this->assertSame($u->id, $t->penyelesai_id);
        $this->assertSame('Beres.', $t->catatan_penyelesaian);
    }

    public function test_pemeliharaan_selesai_update_jadwal(): void
    {
        $aset = Aset::factory()->create();
        $j = JadwalPemeliharaan::factory()->for($aset)->create(['terakhir_dilakukan' => Carbon::parse('2026-01-01')]);
        $t = Tiket::factory()->jenis(JenisTiket::Pemeliharaan)->status(StatusTiket::Baru)
            ->create(['inventaris_id' => $aset->id, 'jadwal_pemeliharaan_id' => $j->id]);
        $u = User::factory()->create();

        Carbon::setTestNow('2026-06-15');
        ProsesTiket::selesai($t, $u, 'Kalibrasi selesai.');
        Carbon::setTestNow();
        $this->assertSame('2026-06-15', $j->fresh()->terakhir_dilakukan->format('Y-m-d'));
    }

    public function test_batal(): void
    {
        $t = Tiket::factory()->status(StatusTiket::Baru)->create();
        ProsesTiket::batal($t);
        $this->assertSame(StatusTiket::Batal, $t->fresh()->status);
    }

    public function test_no_reopen_dari_selesai(): void
    {
        $t = Tiket::factory()->status(StatusTiket::Selesai)->create();
        $u = User::factory()->create();
        $this->expectException(\RuntimeException::class);
        ProsesTiket::mulai($t, $u);
    }
}
