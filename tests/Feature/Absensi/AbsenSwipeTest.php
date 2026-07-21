<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbsenSwipeTest extends TestCase
{
    use RefreshDatabase;

    private function userKaryawan(): User
    {
        $kar = Karyawan::factory()->create();

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_halaman_absensi_tertutup_untuk_user_belum_klaim(): void
    {
        // User tanpa karyawan (belum klaim) → middleware 'claimed' redirect ke /klaim.
        $user = User::factory()->create(['karyawan_id' => null]);
        $this->actingAs($user)->get('/absensi')->assertRedirect(route('klaim'));
    }

    public function test_halaman_absensi_terbuka_untuk_karyawan(): void
    {
        $user = $this->userKaryawan();
        $this->actingAs($user)->get('/absensi')->assertOk();
    }

    public function test_render_menampilkan_aksi_masuk_saat_tak_ada_sesi(): void
    {
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->assertSet('aksi', 'masuk')
            ->assertOk();
    }

    public function test_render_menampilkan_aksi_pulang_saat_sesi_aktif(): void
    {
        $user = $this->userKaryawan();
        Absensi::factory()->create([
            'karyawan_id' => $user->karyawan_id,
            'jam_pulang' => null,
        ]);

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->assertSet('aksi', 'pulang');
    }

    private function setPengaturan(): PengaturanAbsensi
    {
        // Kantor di (-6.9, 107.6), radius 100 m, akurasi maks 30 m.
        return PengaturanAbsensi::create([
            'id' => 1, 'office_lat' => -6.9, 'office_long' => 107.6,
            'radius_m' => 100, 'max_akurasi_m' => 30,
        ]);
    }

    public function test_masuk_dalam_radius_membuat_absensi_dan_foto_webp(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('foto', UploadedFile::fake()->image('selfie.jpg', 200, 200))
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->set('wajahAda', true)
            ->call('simpan')
            ->assertHasNoErrors();

        $a = Absensi::where('karyawan_id', $user->karyawan_id)->firstOrFail();
        $this->assertNull($a->jam_pulang);
        $this->assertTrue((bool) $a->wajah_verif_masuk);
        $this->assertNotNull($a->foto_masuk_path);
        Storage::disk('local')->assertExists($a->foto_masuk_path);
        $this->assertStringEndsWith('.webp', $a->foto_masuk_path);
    }

    public function test_di_luar_radius_ditolak_tanpa_membuat_absensi(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('foto', UploadedFile::fake()->image('selfie.jpg', 200, 200))
            ->set('lat', -6.95)->set('long', 107.65)->set('akurasi', 8) // jauh
            ->call('simpan')
            ->assertHasErrors('lat');

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    public function test_akurasi_buruk_ditolak(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('foto', UploadedFile::fake()->image('s.jpg', 200, 200))
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 500)
            ->call('simpan')
            ->assertHasErrors('akurasi');

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    public function test_fallback_wajah_tidak_ada_tetap_absen_tapi_wajah_verif_false(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('foto', UploadedFile::fake()->image('s.jpg', 200, 200))
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->set('wajahAda', false)
            ->call('simpan')
            ->assertHasNoErrors();

        $a = Absensi::where('karyawan_id', $user->karyawan_id)->firstOrFail();
        $this->assertFalse((bool) $a->wajah_verif_masuk);
    }

    public function test_pulang_menutup_sesi_aktif(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();
        Absensi::factory()->create([
            'karyawan_id' => $user->karyawan_id,
            'jam_pulang' => null,
        ]);

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('foto', UploadedFile::fake()->image('s.jpg', 200, 200))
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan')
            ->assertHasNoErrors();

        $a = Absensi::where('karyawan_id', $user->karyawan_id)->firstOrFail();
        $this->assertNotNull($a->jam_pulang);
        $this->assertNotNull($a->foto_pulang_path);
    }

    public function test_beranda_menampilkan_kartu_absensi_untuk_karyawan(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Beranda::class)
            ->assertSee('Absensi Hari Ini');
    }

    /** @return array{0: User, 1: \App\Models\Shift, 2: \App\Models\Shift} */
    private function karyawanDinasGanda(): array
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $malam = \App\Models\Shift::factory()->for($unit, 'orgUnit')->create([
            'kode' => 'M', 'nama' => 'Malam', 'jam_mulai' => '00:00:00', 'jam_selesai' => '08:00:00',
        ]);
        $sore = \App\Models\Shift::factory()->for($unit, 'orgUnit')->create([
            'kode' => 'S', 'nama' => 'Sore', 'jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00',
        ]);
        \App\Models\Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => today()->toDateString(), 'shift_id' => $malam->id]);
        \App\Models\Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => today()->toDateString(), 'shift_id' => $sore->id]);

        return [$user, $malam, $sore];
    }

    public function test_absen_menampilkan_semua_shift_hari_ini(): void
    {
        [$user] = $this->karyawanDinasGanda();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->assertOk()
            ->assertSee('Malam')
            ->assertSee('Sore')
            // Belum ada sesi sama sekali → tak boleh ada shift berlabel "selesai".
            ->assertDontSee('selesai');
    }

    public function test_absen_menandai_selesai_hanya_untuk_shift_yang_sudah_dipakai(): void
    {
        [$user, $malam] = $this->karyawanDinasGanda();
        Absensi::create([
            'karyawan_id' => $user->karyawan_id,
            'tanggal_kerja' => today()->toDateString(),
            'shift_id' => $malam->id,
            'shift_nama' => $malam->nama,
            'shift_mulai' => $malam->jam_mulai,
            'shift_selesai' => $malam->jam_selesai,
            'shift_toleransi' => $malam->toleransi_telat,
            'jam_masuk' => today()->setTime(0, 5),
            'jam_pulang' => today()->setTime(8, 0),
            'lat_masuk' => -3.31, 'long_masuk' => 114.59, 'akurasi_masuk' => 10,
            'wajah_verif_masuk' => true,
        ]);

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->assertOk()
            ->assertSeeInOrder(['Malam', 'selesai']);
    }

    public function test_beranda_menampilkan_chip_untuk_tiap_shift_hari_ini(): void
    {
        [$user] = $this->karyawanDinasGanda();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Beranda::class)
            ->assertSee('Malam')
            ->assertSee('Sore');
    }
}
