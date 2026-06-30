<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserKaryawanLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_terhubung_ke_karyawan(): void
    {
        $kar = Karyawan::factory()->create(['nip' => '2000.01.01.001']);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $this->assertEquals($kar->id, $user->karyawan->id);
        $this->assertEquals('2000.01.01.001', $user->karyawan->nip);
        $this->assertEquals($user->id, $kar->user->id);
    }

    public function test_user_google_tanpa_password_boleh(): void
    {
        $u = User::create(['name' => 'G', 'email' => 'g@x.test', 'google_id' => 'gid-1']);
        $this->assertNull($u->password);
        $this->assertNull($u->karyawan_id);
    }
}
