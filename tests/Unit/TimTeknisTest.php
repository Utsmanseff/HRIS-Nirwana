<?php

namespace Tests\Unit;

use App\Enums\TimTeknis;
use PHPUnit\Framework\TestCase;

class TimTeknisTest extends TestCase
{
    public function test_permission_map(): void
    {
        $this->assertSame('kerjakan-tiket-it', TimTeknis::It->permission());
        $this->assertSame('kerjakan-tiket-sarana', TimTeknis::Sarana->permission());
        $this->assertSame('kerjakan-tiket-alkes', TimTeknis::Atem->permission());
    }

    public function test_dari_permission(): void
    {
        $this->assertSame(TimTeknis::Atem, TimTeknis::dariPermission('kerjakan-tiket-alkes'));
        $this->assertNull(TimTeknis::dariPermission('kelola-sdm'));
    }

    public function test_label(): void
    {
        $this->assertSame('IT', TimTeknis::It->label());
        $this->assertSame('Sarana', TimTeknis::Sarana->label());
        $this->assertSame('ATEM', TimTeknis::Atem->label());
    }
}
