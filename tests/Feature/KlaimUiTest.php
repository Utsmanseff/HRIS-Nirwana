<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KlaimUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_klaim_pakai_layout_auth(): void
    {
        // User login TAPI belum klaim (karyawan_id null) → diarahkan ke /klaim oleh middleware.
        $user = User::factory()->create(['karyawan_id' => null]);

        $res = $this->actingAs($user)->get('/klaim');
        $res->assertOk();
        $res->assertSee('auth-brand', false);
        $res->assertSee('Hubungkan Data Karyawan');
    }
}
