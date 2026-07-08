<?php

namespace Tests\Feature\Inventaris;

use App\Enums\StatusAset;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tim_derived_dari_kategori(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
        $this->assertSame(TimTeknis::Atem, $aset->tim);
    }

    public function test_status_cast_default_baik(): void
    {
        $aset = Aset::factory()->create();
        $this->assertSame(StatusAset::Baik, $aset->fresh()->status);
    }

    public function test_kode_unik(): void
    {
        Aset::factory()->create(['kode' => 'IT-0001']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Aset::factory()->create(['kode' => 'IT-0001']);
    }
}
