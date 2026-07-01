<?php

namespace Tests\Feature;

use App\Livewire\Profil;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function user(?string $password): User
    {
        $kar = Karyawan::factory()->create();

        return User::factory()->create([
            'karyawan_id' => $kar->id,
            'password' => $password ? Hash::make($password) : null,
        ]);
    }

    public function test_set_password_saat_akun_google_belum_punya(): void
    {
        $user = $this->user(null);

        Livewire::actingAs($user)->test(Profil::class)
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('simpanPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->password));
    }

    public function test_ubah_password_wajib_sandi_lama_benar(): void
    {
        $user = $this->user('lamaBenar1');

        Livewire::actingAs($user)->test(Profil::class)
            ->set('password_lama', 'salah')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('simpanPassword')
            ->assertHasErrors(['password_lama']);

        $this->assertTrue(Hash::check('lamaBenar1', $user->fresh()->password)); // tak berubah
    }

    public function test_ubah_password_berhasil_dgn_sandi_lama_benar(): void
    {
        $user = $this->user('lamaBenar1');

        Livewire::actingAs($user)->test(Profil::class)
            ->set('password_lama', 'lamaBenar1')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('simpanPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }
}
