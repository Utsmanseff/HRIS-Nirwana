<?php

namespace Tests\Unit;

use App\Enums\StatusSanksi;
use PHPUnit\Framework\TestCase;

class StatusSanksiTest extends TestCase
{
    public function test_nilai_dan_label(): void
    {
        $this->assertSame('diajukan', StatusSanksi::Diajukan->value);
        $this->assertSame('diterbitkan', StatusSanksi::Diterbitkan->value);
        $this->assertSame('Dicabut', StatusSanksi::Dicabut->label());
    }

    public function test_pending_hanya_diajukan_diproses(): void
    {
        $this->assertTrue(StatusSanksi::Diajukan->pending());
        $this->assertTrue(StatusSanksi::Diproses->pending());
        $this->assertFalse(StatusSanksi::Diterbitkan->pending());
        $this->assertFalse(StatusSanksi::Ditolak->pending());
    }
}
