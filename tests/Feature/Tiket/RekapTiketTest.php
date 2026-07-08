<?php

namespace Tests\Feature\Tiket;

use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Models\Tiket;
use App\Support\RekapTiket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RekapTiketTest extends TestCase
{
    use RefreshDatabase;

    public function test_antrian_hitung_aktif_per_tim(): void
    {
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create();
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Diproses)->create();
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Selesai)->create();
        Tiket::factory()->tim(TimTeknis::Atem)->status(StatusTiket::Baru)->create();

        $this->assertSame(2, RekapTiket::jumlahAntrian([TimTeknis::It->value]));
    }

    public function test_metrik_rata_per_tim(): void
    {
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Selesai)->create([
            'waktu_lapor' => Carbon::parse('2026-06-01 08:00'),
            'waktu_respon' => Carbon::parse('2026-06-01 08:20'),   // 20 mnt
            'waktu_selesai' => Carbon::parse('2026-06-01 09:00'),  // 60 mnt
        ]);
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Selesai)->create([
            'waktu_lapor' => Carbon::parse('2026-06-02 08:00'),
            'waktu_respon' => Carbon::parse('2026-06-02 08:40'),   // 40 mnt
            'waktu_selesai' => Carbon::parse('2026-06-02 10:00'),  // 120 mnt
        ]);

        $metrik = RekapTiket::metrikPerTim(['tim' => [TimTeknis::It->value]]);
        $it = collect($metrik)->firstWhere('tim', 'it');
        $this->assertSame(30.0, round($it['rata_respon'], 1));       // (20+40)/2
        $this->assertSame(90.0, round($it['rata_penyelesaian'], 1)); // (60+120)/2
    }
}
