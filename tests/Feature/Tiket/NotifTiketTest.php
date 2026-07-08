<?php

namespace Tests\Feature\Tiket;

use App\Enums\Role;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Livewire\Tiket\TiketForm;
use App\Models\Karyawan;
use App\Models\Tiket;
use App\Models\User;
use App\Notifications\TiketBaru;
use App\Notifications\TiketSelesai;
use App\Support\ProsesTiket;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotifTiketTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiket_baru_notif_ke_tim(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $anggotaIt = User::factory()->create();
        $anggotaIt->assignRole(Role::It->value);
        $pembuat = User::factory()->create();
        $pembuat->assignRole(Role::It->value);

        Livewire::actingAs($pembuat)->test(TiketForm::class)
            ->set('tim', TimTeknis::It->value)
            ->set('judul', 'AC mati')
            ->set('deskripsi', 'Tak dingin.')
            ->set('statusLanjut', 'baru')
            ->call('simpan');

        Notification::assertSentTo($anggotaIt, TiketBaru::class);
    }

    public function test_langsung_selesai_internal_tak_notif_tim(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);
        $pembuat = User::factory()->create();
        $pembuat->assignRole(Role::It->value);

        Livewire::actingAs($pembuat)->test(TiketForm::class)
            ->set('tim', TimTeknis::It->value)
            ->set('judul', 'Rekam internal')
            ->set('deskripsi', 'Sudah beres.')
            ->set('statusLanjut', 'selesai')
            ->call('simpan');

        Notification::assertNothingSentTo($pembuat);
    }

    public function test_selesai_notif_pelapor(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $kar = Karyawan::factory()->create();
        $pelaporUser = User::factory()->create();
        $pelaporUser->update(['karyawan_id' => $kar->id]);

        $petugas = User::factory()->create();
        $petugas->assignRole(Role::It->value);

        $t = Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create(['pelapor_id' => $kar->id]);
        ProsesTiket::selesai($t, $petugas, 'Beres.');

        Notification::assertSentTo($pelaporUser, TiketSelesai::class);
    }
}
