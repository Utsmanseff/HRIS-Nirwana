<?php

namespace Tests\Unit;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use PHPUnit\Framework\TestCase;

class EnumTiketTest extends TestCase
{
    public function test_jenis_label(): void
    {
        $this->assertSame('Perbaikan', JenisTiket::Perbaikan->label());
        $this->assertSame('Pemeliharaan', JenisTiket::Pemeliharaan->label());
    }

    public function test_prioritas_urutan_desc(): void
    {
        $this->assertSame(4, PrioritasTiket::Urgent->urutan());
        $this->assertSame(1, PrioritasTiket::Rendah->urutan());
        $this->assertSame('Tinggi', PrioritasTiket::Tinggi->label());
    }

    public function test_status_aktif(): void
    {
        $this->assertSame(
            [StatusTiket::Baru, StatusTiket::Diproses],
            StatusTiket::aktif(),
        );
        $this->assertSame('Selesai', StatusTiket::Selesai->label());
    }
}
