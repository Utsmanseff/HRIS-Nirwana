<?php

namespace Tests\Unit;

use App\Enums\PeranApproval;
use PHPUnit\Framework\TestCase;

class PeranApprovalTest extends TestCase
{
    public function test_label_tiap_peran(): void
    {
        $this->assertSame('Koordinator', PeranApproval::Koordinator->label());
        $this->assertSame('Kabid', PeranApproval::Kabid->label());
        $this->assertSame('HRD', PeranApproval::Hrd->label());
        $this->assertSame('Direktur', PeranApproval::Direktur->label());
    }
}
