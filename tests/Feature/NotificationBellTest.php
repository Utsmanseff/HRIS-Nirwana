<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Models\User;
use App\Notifications\SipAkanBerakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    private function buatNotif(User $user, array $overrides = []): DatabaseNotification
    {
        return $user->notifications()->create(array_merge([
            'id' => (string) Str::uuid(),
            'type' => SipAkanBerakhir::class,
            'data' => ['pesan' => 'SIP Budi berakhir 10 hari', 'url' => '/x'],
            'read_at' => null,
        ], $overrides));
    }

    public function test_menampilkan_jumlah_belum_dibaca(): void
    {
        $user = User::factory()->create();
        $this->buatNotif($user);

        Livewire::actingAs($user)->test(NotificationBell::class)
            ->assertSee('1')
            ->assertSee('SIP Budi berakhir 10 hari');
    }

    public function test_tandai_dibaca_mengurangi_unread(): void
    {
        $user = User::factory()->create();
        $n = $this->buatNotif($user);

        Livewire::actingAs($user)->test(NotificationBell::class)
            ->call('tandaiDibaca', $n->id);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_tandai_semua_dibaca(): void
    {
        $user = User::factory()->create();
        $this->buatNotif($user, ['id' => (string) Str::uuid()]);
        $this->buatNotif($user, ['id' => (string) Str::uuid()]);

        Livewire::actingAs($user)->test(NotificationBell::class)
            ->call('tandaiSemuaDibaca');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
