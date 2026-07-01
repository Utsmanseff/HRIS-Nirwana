<?php

namespace Tests\Feature;

use App\Livewire\Profil;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilKontakTest extends TestCase
{
    use RefreshDatabase;

    private function userTerhubung(): User
    {
        $kar = Karyawan::factory()->create(['no_hp' => '0810', 'email' => 'a@a.test']);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_simpan_kontak_memperbarui_karyawan(): void
    {
        $user = $this->userTerhubung();

        Livewire::actingAs($user)->test(Profil::class)
            ->set('no_hp', '081234567890')
            ->set('email', 'baru@mail.test')
            ->set('alamat', 'Jl. Baru 1')
            ->call('simpanKontak')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('karyawan', [
            'id' => $user->karyawan_id,
            'no_hp' => '081234567890',
            'email' => 'baru@mail.test',
            'alamat' => 'Jl. Baru 1',
        ]);
    }

    public function test_email_kontak_wajib_format_valid(): void
    {
        $user = $this->userTerhubung();

        Livewire::actingAs($user)->test(Profil::class)
            ->set('email', 'bukan-email')
            ->call('simpanKontak')
            ->assertHasErrors(['email']);
    }
}
