<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
    }

    private function buatNotif(User $user, string $pesan, ?string $bacaPada = null): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\CutiDisetujui',
            'data' => ['pesan' => $pesan, 'url' => '/cuti/1'],
            'read_at' => $bacaPada,
        ]);
    }

    public function test_halaman_notifikasi_tampil_daftar(): void
    {
        $user = $this->user();
        $this->buatNotif($user, 'Pengajuan cuti Anda telah disetujui.');

        $this->actingAs($user)->get('/notifikasi')
            ->assertOk()
            ->assertSee('Notifikasi')
            ->assertSee('Pengajuan cuti Anda telah disetujui.')
            ->assertSee('1 belum dibaca');
    }

    public function test_notifikasi_kosong_tampil_empty_state(): void
    {
        $this->actingAs($this->user())->get('/notifikasi')
            ->assertOk()
            ->assertSee('Belum ada notifikasi');
    }

    public function test_tandai_semua_dibaca(): void
    {
        $user = $this->user();
        $this->buatNotif($user, 'Notif A');
        $this->buatNotif($user, 'Notif B');

        $this->assertSame(2, $user->unreadNotifications()->count());

        Livewire::actingAs($user)->test(\App\Livewire\Notifikasi::class)
            ->call('tandaiSemuaDibaca');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_tandai_satu_dibaca(): void
    {
        $user = $this->user();
        $this->buatNotif($user, 'Notif A');
        $id = $user->notifications()->first()->id;

        Livewire::actingAs($user)->test(\App\Livewire\Notifikasi::class)
            ->call('tandaiDibaca', $id);

        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_notif_orang_lain_tak_tampil(): void
    {
        $user = $this->user();
        $lain = $this->user();
        $this->buatNotif($lain, 'Rahasia orang lain');

        $this->actingAs($user)->get('/notifikasi')
            ->assertOk()
            ->assertDontSee('Rahasia orang lain');
    }
}
