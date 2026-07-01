<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    private function userTerhubung(array $karyawan = []): User
    {
        $kar = Karyawan::factory()->create($karyawan);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_user_terhubung_lihat_data_sendiri(): void
    {
        $user = $this->userTerhubung(['nama_lengkap' => 'Siti Rahmawati', 'nip' => '2024.03.0117']);

        $res = $this->actingAs($user)->get('/profil');
        $res->assertOk();
        $res->assertSee('Siti Rahmawati');
        $res->assertSee('2024.03.0117');
    }

    public function test_user_belum_klaim_diarahkan_ke_klaim(): void
    {
        $user = User::factory()->create(['karyawan_id' => null]);

        $this->actingAs($user)->get('/profil')->assertRedirect('/klaim');
    }
}
