<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuanCuti;
use App\Enums\StatusTiket;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SanksiDisiplin;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiwayatTest extends TestCase
{
    use RefreshDatabase;

    private function userKaryawan(): array
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        return [$user, $kar];
    }

    public function test_feed_gabung_semua_jenis(): void
    {
        $this->seed(\Database\Seeders\JenisCutiSeeder::class);
        [$user, $kar] = $this->userKaryawan();

        PengajuanCuti::factory()->for($kar)->status(StatusPengajuanCuti::Disetujui)->create();
        Tiket::factory()->create(['pelapor_id' => $kar->id, 'judul' => 'Printer rusak']);
        SanksiDisiplin::factory()->diterbitkan()->create(['karyawan_id' => $kar->id, 'uraian' => 'Terlambat berulang']);
        Absensi::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/riwayat')
            ->assertOk()
            ->assertSee('Cuti disetujui')
            ->assertSee('Printer rusak')
            ->assertSee('Sanksi diterbitkan')
            ->assertSee('Absensi');
    }

    public function test_filter_jenis_menyaring(): void
    {
        [$user, $kar] = $this->userKaryawan();
        Tiket::factory()->create(['pelapor_id' => $kar->id, 'judul' => 'Tiket AC', 'status' => StatusTiket::Baru]);
        Absensi::factory()->create(['karyawan_id' => $kar->id]);

        Livewire::actingAs($user)->test(\App\Livewire\Riwayat::class)
            ->assertSee('Tiket AC')
            ->assertSee('Absensi ·')
            ->call('pilihJenis', 'tiket')
            ->assertSee('Tiket AC')
            ->assertDontSee('Absensi ·');
    }

    public function test_aktivitas_orang_lain_tak_tampil(): void
    {
        [$user, $kar] = $this->userKaryawan();
        $lain = Karyawan::factory()->create();
        Tiket::factory()->create(['pelapor_id' => $lain->id, 'judul' => 'Rahasia orang lain']);

        $this->actingAs($user)->get('/riwayat')
            ->assertOk()
            ->assertDontSee('Rahasia orang lain');
    }

    public function test_urut_terbaru_dulu(): void
    {
        [$user, $kar] = $this->userKaryawan();
        Tiket::factory()->create(['pelapor_id' => $kar->id, 'judul' => 'Tiket Lama', 'waktu_lapor' => now()->subDays(5)]);
        Tiket::factory()->create(['pelapor_id' => $kar->id, 'judul' => 'Tiket Baru', 'waktu_lapor' => now()]);

        $this->actingAs($user)->get('/riwayat')
            ->assertOk()
            ->assertSeeInOrder(['Tiket Baru', 'Tiket Lama']);
    }

    public function test_user_tanpa_karyawan_dialihkan_klaim(): void
    {
        // Middleware 'claimed' menahan lebih dulu (redirect klaim); abort 403 di mount = pertahanan lapis dua.
        $user = User::factory()->create(['karyawan_id' => null]);

        $this->actingAs($user)->get('/riwayat')->assertRedirect(route('klaim'));
    }
}
