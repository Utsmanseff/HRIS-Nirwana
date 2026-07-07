<?php

namespace Tests\Feature\Disiplin;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\SanksiDisiplin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanksiDisiplinFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_diajukan_dan_state(): void
    {
        $s = SanksiDisiplin::factory()->create();
        $this->assertSame(StatusSanksi::Diajukan, $s->status);
        $this->assertNotNull($s->karyawan_id);
        $this->assertNotNull($s->pengusul_id);

        $terbit = SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Sp1)->create();
        $this->assertSame(StatusSanksi::Diterbitkan, $terbit->status);
        $this->assertSame(TingkatSanksi::Sp1, $terbit->tingkat);
        $this->assertNotNull($terbit->tanggal_terbit);
        $this->assertNotNull($terbit->berlaku_sampai);
    }
}
