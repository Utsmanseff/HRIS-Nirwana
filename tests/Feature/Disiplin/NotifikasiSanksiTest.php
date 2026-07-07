<?php

namespace Tests\Feature\Disiplin;

use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Notifications\SanksiDiterbitkan;
use App\Notifications\SanksiDitolak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifikasiSanksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notif_diterbitkan_ke_database_dengan_payload(): void
    {
        Notification::fake();
        $kena = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kena->id]);
        $sanksi = SanksiDisiplin::factory()->diterbitkan()->create(['karyawan_id' => $kena->id]);

        $user->notify(new SanksiDiterbitkan($sanksi));

        Notification::assertSentTo($user, SanksiDiterbitkan::class, function ($notif) use ($user, $sanksi) {
            $data = $notif->toArray($user);

            return $data['jenis'] === 'sanksi'
                && $data['sanksi_id'] === $sanksi->id
                && str_contains($data['url'], '/beranda');
        });
    }

    public function test_notif_ditolak_bawa_alasan(): void
    {
        Notification::fake();
        $pengusul = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $pengusul->id]);
        $sanksi = SanksiDisiplin::factory()->create(['pengusul_id' => $pengusul->id]);

        $user->notify(new SanksiDitolak($sanksi, 'Bukti kurang'));

        Notification::assertSentTo($user, SanksiDitolak::class, fn ($n) => str_contains($n->toArray($user)['pesan'], 'Bukti kurang'));
    }
}
