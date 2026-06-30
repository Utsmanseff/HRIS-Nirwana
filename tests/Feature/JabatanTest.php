<?php

namespace Tests\Feature;

use App\Enums\JabatanLevel;
use App\Models\Jabatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JabatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_cast_level_enum(): void
    {
        $j = Jabatan::factory()->create(['nama' => 'Koordinator IT', 'level' => JabatanLevel::Koordinator]);
        $this->assertSame(JabatanLevel::Koordinator, $j->fresh()->level);
        $this->assertSame('Koordinator', $j->level->label());
    }
}
