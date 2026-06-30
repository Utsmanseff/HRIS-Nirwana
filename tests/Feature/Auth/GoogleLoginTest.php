<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogle(string $email, string $id = 'gid-1'): void
    {
        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn($id);
        $abstractUser->shouldReceive('getEmail')->andReturn($email);
        $abstractUser->shouldReceive('getName')->andReturn('Google User');
        $abstractUser->shouldReceive('getAvatar')->andReturn('http://x/a.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_user_baru_dibuat_lalu_diarahkan_ke_klaim(): void
    {
        $this->mockGoogle('baru@example.com');
        $this->get('/auth/google/callback')->assertRedirect('/klaim');
        $this->assertDatabaseHas('users', ['email' => 'baru@example.com', 'google_id' => 'gid-1', 'karyawan_id' => null]);
        $this->assertAuthenticated();
    }

    public function test_user_lama_login(): void
    {
        $u = User::factory()->create(['email' => 'lama@example.com', 'google_id' => 'gid-9']);
        $this->mockGoogle('lama@example.com', 'gid-9');
        $this->get('/auth/google/callback');
        $this->assertAuthenticatedAs($u);
    }
}
