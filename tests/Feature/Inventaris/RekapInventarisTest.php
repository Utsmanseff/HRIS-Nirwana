<?php

namespace Tests\Feature\Inventaris;

use App\Enums\StatusAset;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\KategoriInventaris;
use App\Support\RekapInventaris;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RekapInventarisTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_filter_tim_dan_status(): void
    {
        $katIt = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kategori_inventaris_id' => $katIt->id, 'status' => StatusAset::Baik->value]);
        Aset::factory()->create(['kategori_inventaris_id' => $katIt->id, 'status' => StatusAset::Rusak->value]);
        $katAtem = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        Aset::factory()->create(['kategori_inventaris_id' => $katAtem->id]);

        $f = ['tim' => [TimTeknis::It->value], 'status' => StatusAset::Rusak->value];
        $this->assertSame(1, RekapInventaris::query($f)->count());
    }

    public function test_hitung_status_semua_hadir(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->count(2)->create(['kategori_inventaris_id' => $kat->id, 'status' => StatusAset::Baik->value]);
        Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'status' => StatusAset::Afkir->value]);

        $hitung = RekapInventaris::hitungStatus(['tim' => [TimTeknis::It->value]]);
        $this->assertSame(2, $hitung['baik']);
        $this->assertSame(1, $hitung['afkir']);
        $this->assertSame(0, $hitung['rusak']);
    }

    public function test_jumlah_jatuh_tempo(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
        JadwalPemeliharaan::factory()->for($aset)->create([
            'interval_bulan' => 1,
            'terakhir_dilakukan' => Carbon::today()->subMonths(1)->subDays(2),
        ]);

        $this->assertSame(1, RekapInventaris::jumlahJatuhTempo([TimTeknis::It->value]));
        $this->assertSame(0, RekapInventaris::jumlahJatuhTempo([TimTeknis::Atem->value]));
    }
}
