<?php

namespace Tests\Unit;

use App\Enums\TingkatSanksi;
use PHPUnit\Framework\TestCase;

class TingkatSanksiTest extends TestCase
{
    public function test_label_dan_jenis(): void
    {
        $this->assertSame('Teguran 1', TingkatSanksi::Teguran1->label());
        $this->assertSame('SP 2', TingkatSanksi::Sp2->label());
        $this->assertSame('teguran', TingkatSanksi::Teguran3->jenis());
        $this->assertSame('sp', TingkatSanksi::Sp1->jenis());
    }

    public function test_berikutnya(): void
    {
        $this->assertSame(TingkatSanksi::Teguran2, TingkatSanksi::Teguran1->berikutnya());
        $this->assertSame(TingkatSanksi::Sp1, TingkatSanksi::Teguran3->berikutnya());
        $this->assertNull(TingkatSanksi::Sp3->berikutnya());
    }
}
