<?php

namespace Tests\Feature\Inventaris;

use App\Models\Aset;
use App\Models\MutasiAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MutasiAsetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_relasi_aset(): void
    {
        $aset = Aset::factory()->create();
        $m = MutasiAset::create([
            'aset_id' => $aset->id,
            'dari_unit_id' => null,
            'ke_unit_id' => null,
            'tanggal' => now(),
            'oleh' => null,
            'catatan' => 'pindah gudang',
        ]);
        $this->assertSame($aset->id, $m->aset->id);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $m->tanggal);
    }
}
