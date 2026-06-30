<?php

namespace Tests\Unit;

use App\Enums\JabatanLevel;
use App\Enums\JenisKontrak;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_jenis_kontrak_berbatas_waktu(): void
    {
        $this->assertTrue(JenisKontrak::Pkwt->berbatasWaktu());
        $this->assertFalse(JenisKontrak::Tetap->berbatasWaktu());
        $this->assertTrue(JenisKontrak::PercobaanUnpaid->berbatasWaktu());
    }

    public function test_threshold_pengingat_hari(): void
    {
        $this->assertSame(3, JenisKontrak::PercobaanUnpaid->thresholdHari());
        $this->assertSame(30, JenisKontrak::Percobaan->thresholdHari());
        $this->assertSame(30, JenisKontrak::Pkwt->thresholdHari());
        $this->assertNull(JenisKontrak::Tetap->thresholdHari());
    }

    public function test_jabatan_level_label(): void
    {
        $this->assertSame('Koordinator', JabatanLevel::Koordinator->label());
        $this->assertSame(2, JabatanLevel::Koordinator->value);
    }
}
