<?php

namespace Tests\Feature\Inventaris;

use App\Models\Aset;
use App\Models\LampiranAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LampiranAsetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_relasi_dan_cast(): void
    {
        $aset = Aset::factory()->create();
        $l = LampiranAset::create([
            'aset_id' => $aset->id,
            'tipe' => 'sertifikat',
            'path' => 'aset/1/x.webp',
            'mime' => 'image/webp',
            'tanggal' => '2026-01-01',
            'berlaku_sampai' => '2027-01-01',
        ]);
        $this->assertSame($aset->id, $l->aset->id);
        $this->assertSame('2027-01-01', $l->berlaku_sampai->format('Y-m-d'));
    }
}
