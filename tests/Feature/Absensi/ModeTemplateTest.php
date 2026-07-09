<?php

namespace Tests\Feature\Absensi;

use App\Enums\ModeTemplate;
use App\Models\OrgUnit;
use App\Models\TemplateJadwal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_mode_rotasi(): void
    {
        $tpl = TemplateJadwal::create([
            'org_unit_id' => OrgUnit::factory()->create()->id,
            'tanggal_jangkar' => '2026-07-01',
        ]);

        $this->assertSame(ModeTemplate::Rotasi, $tpl->fresh()->mode);
    }

    public function test_mode_mingguan_di_cast(): void
    {
        $tpl = TemplateJadwal::create([
            'org_unit_id' => OrgUnit::factory()->create()->id,
            'tanggal_jangkar' => '2026-07-01',
            'mode' => ModeTemplate::Mingguan->value,
        ]);

        $this->assertSame(ModeTemplate::Mingguan, $tpl->fresh()->mode);
        $this->assertSame('Mingguan', $tpl->mode->label());
    }
}
