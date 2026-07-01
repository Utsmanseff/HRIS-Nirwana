<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_bisa_daftar_langganan_push(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://push.test/xyz',
            'keys' => ['p256dh' => 'kunci-publik', 'auth' => 'token-auth'],
        ])->assertSuccessful();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => 'https://push.test/xyz',
        ]);
    }

    public function test_tamu_ditolak(): void
    {
        // App hanya me-render JSON untuk rute api/* (lihat bootstrap/app.php shouldRenderJsonWhen),
        // jadi tamu di rute web ini di-redirect ke login, bukan 401.
        $this->post('/push/subscribe', ['endpoint' => 'https://push.test/1'])
            ->assertRedirect('/login');
    }
}
