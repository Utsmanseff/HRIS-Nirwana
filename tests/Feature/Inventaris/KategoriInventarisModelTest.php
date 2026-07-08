<?php

namespace Tests\Feature\Inventaris;

use App\Enums\TimTeknis;
use App\Models\KategoriInventaris;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriInventarisModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_buat_kategori_dengan_tim_cast(): void
    {
        $k = KategoriInventaris::factory()->create(['nama' => 'PC', 'tim' => TimTeknis::It]);
        $this->assertInstanceOf(TimTeknis::class, $k->fresh()->tim);
        $this->assertTrue($k->aktif);
    }
}
