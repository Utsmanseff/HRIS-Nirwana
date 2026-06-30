<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function userNip(string $nip, string $pass = 'rahasia123'): User
    {
        $kar = Karyawan::factory()->create(['nip' => $nip]);

        return User::factory()->create(['karyawan_id' => $kar->id, 'password' => Hash::make($pass)]);
    }

    public function test_login_nip_password_benar(): void
    {
        $this->userNip('1990.04.21.001');

        Livewire::test(Login::class)->set('nip', '1990.04.21.001')->set('password', 'rahasia123')
            ->call('login')->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_login_password_salah(): void
    {
        $this->userNip('1990.04.21.001');

        Livewire::test(Login::class)->set('nip', '1990.04.21.001')->set('password', 'salah')
            ->call('login')->assertHasErrors('nip');

        $this->assertGuest();
    }

    public function test_login_nip_tidak_ada(): void
    {
        Livewire::test(Login::class)->set('nip', '0000.00.00.000')->set('password', 'x')
            ->call('login')->assertHasErrors('nip');

        $this->assertGuest();
    }
}
