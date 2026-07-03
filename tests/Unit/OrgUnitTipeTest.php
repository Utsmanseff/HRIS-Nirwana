<?php

namespace Tests\Unit;

use App\Enums\JabatanLevel;
use App\Enums\OrgUnitTipe;
use PHPUnit\Framework\TestCase;

class OrgUnitTipeTest extends TestCase
{
    public function test_empat_tipe(): void
    {
        $this->assertSame('direktur', OrgUnitTipe::Direktur->value);
        $this->assertSame('bidang', OrgUnitTipe::Bidang->value);
        $this->assertSame('bagian', OrgUnitTipe::Bagian->value);
        $this->assertSame('unit', OrgUnitTipe::Unit->value);
    }

    public function test_level_pimpinan_per_tipe(): void
    {
        $this->assertSame(JabatanLevel::Direktur, OrgUnitTipe::Direktur->levelPimpinan());
        $this->assertSame(JabatanLevel::Kabid, OrgUnitTipe::Bidang->levelPimpinan());
        $this->assertSame(JabatanLevel::Kabid, OrgUnitTipe::Bagian->levelPimpinan());
        $this->assertSame(JabatanLevel::Koordinator, OrgUnitTipe::Unit->levelPimpinan());
    }

    public function test_label(): void
    {
        $this->assertSame('Bidang', OrgUnitTipe::Bidang->label());
        $this->assertSame('Bagian', OrgUnitTipe::Bagian->label());
    }
}
