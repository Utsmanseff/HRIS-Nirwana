<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JabatanOrgUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_jabatan_milik_org_unit(): void
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id]);
        $this->assertEquals($unit->id, $jab->orgUnit->id);
    }

    public function test_scope_pimpinan_dan_staff(): void
    {
        $unit = OrgUnit::factory()->create();
        Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);
        Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 2]);
        $this->assertSame(1, Jabatan::pimpinan()->count());
        $this->assertSame(1, Jabatan::staff()->count());
    }
}
