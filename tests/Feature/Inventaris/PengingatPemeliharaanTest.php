<?php

namespace Tests\Feature\Inventaris;

use App\Enums\SeverityPengingat;
use App\Enums\StatusAset;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\KategoriInventaris;
use App\Support\PengingatPemeliharaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatPemeliharaanTest extends TestCase
{
    use RefreshDatabase;

    private function jadwal(array $override = [], TimTeknis $tim = TimTeknis::It): JadwalPemeliharaan
    {
        $kat = KategoriInventaris::factory()->create(['tim' => $tim]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);

        return JadwalPemeliharaan::factory()->for($aset)->create($override);
    }

    public function test_akan_jatuh_tempo_dalam_ambang(): void
    {
        $hari = Carbon::parse('2026-06-01');
        // berikutnya = 2026-06-10 (H-9) → AkanBerakhir
        $this->jadwal(['interval_bulan' => 1, 'terakhir_dilakukan' => Carbon::parse('2026-05-10')]);
        $semua = PengingatPemeliharaan::semua(null, $hari);
        $this->assertCount(1, $semua);
        $this->assertSame(SeverityPengingat::AkanBerakhir, $semua->first()->severity);
    }

    public function test_lewat_jadi_terlewat(): void
    {
        $hari = Carbon::parse('2026-06-01');
        $this->jadwal(['interval_bulan' => 1, 'terakhir_dilakukan' => Carbon::parse('2026-04-01')]); // berikutnya 2026-05-01
        $p = PengingatPemeliharaan::semua(null, $hari)->first();
        $this->assertSame(SeverityPengingat::Terlewat, $p->severity);
        $this->assertLessThan(0, $p->sisaHari);
    }

    public function test_jauh_tak_muncul(): void
    {
        $hari = Carbon::parse('2026-06-01');
        $this->jadwal(['interval_bulan' => 12, 'terakhir_dilakukan' => Carbon::parse('2026-05-01')]); // berikutnya 2027-05-01
        $this->assertCount(0, PengingatPemeliharaan::semua(null, $hari));
    }

    public function test_filter_tim(): void
    {
        $hari = Carbon::parse('2026-06-01');
        $this->jadwal(['interval_bulan' => 1, 'terakhir_dilakukan' => Carbon::parse('2026-05-10')], TimTeknis::It);
        $this->jadwal(['interval_bulan' => 1, 'terakhir_dilakukan' => Carbon::parse('2026-05-10')], TimTeknis::Atem);
        $this->assertCount(1, PengingatPemeliharaan::semua([TimTeknis::It->value], $hari));
        $this->assertCount(2, PengingatPemeliharaan::semua(null, $hari));
    }

    public function test_aset_afkir_dilewati(): void
    {
        $hari = Carbon::parse('2026-06-01');
        $j = $this->jadwal(['interval_bulan' => 1, 'terakhir_dilakukan' => Carbon::parse('2026-05-20')]);
        $j->aset->update(['status' => StatusAset::Afkir->value]);
        $this->assertCount(0, PengingatPemeliharaan::semua(null, $hari));
    }
}
