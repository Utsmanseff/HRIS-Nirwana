<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_login_tidak_mendarat_di_halaman_tamu(): void
    {
        $user = User::factory()->create();

        // '/' -> '/login' -> middleware guest memantul user yang sudah login.
        $this->actingAs($user)->get('/login')->assertRedirect();
    }
}
