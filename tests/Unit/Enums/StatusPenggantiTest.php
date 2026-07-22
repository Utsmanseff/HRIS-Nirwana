<?php

namespace Tests\Unit\Enums;

use App\Enums\StatusPengganti;
use PHPUnit\Framework\TestCase;

class StatusPenggantiTest extends TestCase
{
    public function test_nilai_enum(): void
    {
        $this->assertSame('aktif', StatusPengganti::Aktif->value);
        $this->assertSame('usulan', StatusPengganti::Usulan->value);
        $this->assertCount(2, StatusPengganti::cases());
    }

    public function test_label(): void
    {
        $this->assertSame('Aktif', StatusPengganti::Aktif->label());
        $this->assertSame('Menunggu Acc', StatusPengganti::Usulan->label());
    }
}
