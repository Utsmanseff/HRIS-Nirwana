<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use App\Notifications\UjiCoba;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifUjiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mengirim_notifikasi_ke_karyawan_sasaran(): void
    {
        Notification::fake();

        $kar = Karyawan::factory()->create(['nip' => 'UJI-0001']);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->artisan('notif:uji', ['nip' => 'UJI-0001'])->assertSuccessful();

        Notification::assertSentTo($user, UjiCoba::class);
    }

    public function test_gagal_bila_nip_tak_ada(): void
    {
        Notification::fake();

        $this->artisan('notif:uji', ['nip' => 'TIDAK-ADA'])
            ->expectsOutputToContain('tidak ada')
            ->assertFailed();

        Notification::assertNothingSent();
    }

    public function test_gagal_bila_karyawan_belum_punya_akun(): void
    {
        Notification::fake();

        Karyawan::factory()->create(['nip' => 'UJI-0002']);

        $this->artisan('notif:uji', ['nip' => 'UJI-0002'])
            ->expectsOutputToContain('belum tertaut akun')
            ->assertFailed();

        Notification::assertNothingSent();
    }
}
