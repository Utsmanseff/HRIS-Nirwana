<?php

namespace Tests\Unit;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNamaTampilanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pakai_nama_karyawan_saat_tertaut(): void
    {
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Rahmi Wulandari']);
        $user = User::factory()->create(['name' => 'gaming akun', 'karyawan_id' => $kar->id]);

        $this->assertSame('Rahmi Wulandari', $user->namaTampilan());
    }

    public function test_fallback_ke_users_name_saat_tak_tertaut(): void
    {
        $user = User::factory()->create(['name' => 'Budi Google', 'karyawan_id' => null]);

        $this->assertSame('Budi Google', $user->namaTampilan());
    }

}
