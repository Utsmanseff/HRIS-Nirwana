<?php

namespace Tests\Feature\Tiket;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Models\Tiket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TiketModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cast_enum(): void
    {
        $t = Tiket::factory()->create([
            'jenis' => JenisTiket::Pemeliharaan,
            'tim' => TimTeknis::Atem,
            'prioritas' => PrioritasTiket::Tinggi,
            'status' => StatusTiket::Baru,
        ]);
        $t = $t->fresh();
        $this->assertInstanceOf(JenisTiket::class, $t->jenis);
        $this->assertInstanceOf(TimTeknis::class, $t->tim);
        $this->assertInstanceOf(PrioritasTiket::class, $t->prioritas);
        $this->assertInstanceOf(StatusTiket::class, $t->status);
    }

    public function test_nomor_auto_urut_per_tahun(): void
    {
        Carbon::setTestNow('2026-03-01');
        $a = Tiket::create(Tiket::factory()->raw(['nomor' => Tiket::buatNomor()]));
        $b = Tiket::create(Tiket::factory()->raw(['nomor' => Tiket::buatNomor()]));
        $this->assertSame('TKT-2026-0001', $a->nomor);
        $this->assertSame('TKT-2026-0002', $b->nomor);
        Carbon::setTestNow();
    }

    public function test_metrik_menit_derived(): void
    {
        $t = Tiket::factory()->create([
            'waktu_lapor' => Carbon::parse('2026-06-01 08:00'),
            'waktu_respon' => Carbon::parse('2026-06-01 08:30'),
            'waktu_selesai' => Carbon::parse('2026-06-01 10:00'),
        ]);
        $this->assertSame(30, $t->menitRespon());
        $this->assertSame(120, $t->menitPenyelesaian());
    }

    public function test_metrik_null_bila_belum(): void
    {
        $t = Tiket::factory()->create(['waktu_respon' => null, 'waktu_selesai' => null]);
        $this->assertNull($t->menitRespon());
        $this->assertNull($t->menitPenyelesaian());
    }
}
