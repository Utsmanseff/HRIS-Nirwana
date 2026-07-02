<?php

namespace Tests\Feature\Sistem;

use App\Livewire\Auth\Login;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_login_nip_akun_nonaktif_ditolak(): void
    {
        $karyawan = Karyawan::factory()->create(['nip' => 'TEST-001']);
        User::factory()->nonaktif()->create([
            'karyawan_id' => $karyawan->id,
            'password' => 'rahasia123',
        ]);

        Livewire::test(Login::class)
            ->set('nip', 'TEST-001')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasErrors('nip');

        $this->assertGuest();
    }

    public function test_sesi_berjalan_akun_nonaktif_dipaksa_logout(): void
    {
        $karyawan = Karyawan::factory()->create();
        $user = User::factory()->nonaktif()->create(['karyawan_id' => $karyawan->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_akun_aktif_tetap_bisa_akses(): void
    {
        $karyawan = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $karyawan->id]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
