<?php

namespace Tests\Feature\Sistem;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunNonaktifTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_baru_berstatus_aktif(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->nonaktif_pada);
        $this->assertTrue($user->akunAktif());
    }

    public function test_state_nonaktif_mengisi_nonaktif_pada(): void
    {
        $user = User::factory()->nonaktif()->create();

        $this->assertNotNull($user->nonaktif_pada);
        $this->assertFalse($user->akunAktif());
    }
}
