<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotifInfraTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_notifikasi_dan_langganan_push_ada(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasTable('push_subscriptions'));
    }

    public function test_user_bisa_simpan_langganan_push(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription('https://endpoint.test/abc', 'pubkey', 'authtoken');
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => 'https://endpoint.test/abc',
        ]);
    }
}
