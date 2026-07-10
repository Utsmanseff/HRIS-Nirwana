<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenSwipeTest extends TestCase
{
    use RefreshDatabase;

    private function userKaryawan(): User
    {
        $kar = Karyawan::factory()->create();

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_halaman_absensi_tertutup_untuk_user_belum_klaim(): void
    {
        // User tanpa karyawan (belum klaim) → middleware 'claimed' redirect ke /klaim.
        $user = User::factory()->create(['karyawan_id' => null]);
        $this->actingAs($user)->get('/absensi')->assertRedirect(route('klaim'));
    }

    public function test_halaman_absensi_terbuka_untuk_karyawan(): void
    {
        $user = $this->userKaryawan();
        $this->actingAs($user)->get('/absensi')->assertOk();
    }

    public function test_render_menampilkan_aksi_masuk_saat_tak_ada_sesi(): void
    {
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->assertSet('aksi', 'masuk')
            ->assertOk();
    }

    public function test_render_menampilkan_aksi_pulang_saat_sesi_aktif(): void
    {
        $user = $this->userKaryawan();
        Absensi::factory()->create([
            'karyawan_id' => $user->karyawan_id,
            'jam_pulang' => null,
        ]);

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->assertSet('aksi', 'pulang');
    }
}
