<?php

namespace Tests\Feature;

use App\Enums\OrgUnitTipe;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_pohon_parent_dan_children(): void
    {
        $bidang = OrgUnit::factory()->create(['nama' => 'Penunjang Medik', 'tipe' => OrgUnitTipe::Bidang]);
        $unit = OrgUnit::factory()->create(['tipe' => OrgUnitTipe::Unit, 'parent_id' => $bidang->id]);
        $this->assertTrue($bidang->children->contains($unit));
        $this->assertEquals($bidang->id, $unit->parent->id);
        $this->assertSame(OrgUnitTipe::Unit, $unit->tipe);
    }

    public function test_scope_akar(): void
    {
        $root = OrgUnit::factory()->create(['parent_id' => null]);
        OrgUnit::factory()->create(['parent_id' => $root->id]);
        $this->assertCount(1, OrgUnit::akar()->get());
    }
}
