<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** Foto absen sekarang dikirim sebagai data URL di argumen simpan(), bukan lewat
     *  $wire.upload() — lihat App\Support\FotoDataUrl. */
    private function fotoData(string $tipe = 'png'): string
    {
        $img = imagecreatetruecolor(64, 64);
        imagefilledrectangle($img, 0, 0, 63, 63, imagecolorallocate($img, 120, 90, 70));
        ob_start();
        $tipe === 'webp' ? imagewebp($img, null, 80) : imagepng($img);
        $biner = ob_get_clean();
        imagedestroy($img);

        return "data:image/$tipe;base64,".base64_encode($biner);
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
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->set('wajahAda', true)
            ->call('simpan', $this->fotoData())
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
            ->set('lat', -6.95)->set('long', 107.65)->set('akurasi', 8) // jauh
            ->call('simpan', $this->fotoData())
            ->assertHasErrors('lat');

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    public function test_akurasi_buruk_ditolak(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 500)
            ->call('simpan', $this->fotoData())
            ->assertHasErrors('akurasi');

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    public function test_fallback_wajah_tidak_ada_tetap_absen_tapi_wajah_verif_false(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->set('wajahAda', false)
            ->call('simpan', $this->fotoData())
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
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', $this->fotoData())
            ->assertHasNoErrors();

        $a = Absensi::where('karyawan_id', $user->karyawan_id)->firstOrFail();
        $this->assertNotNull($a->jam_pulang);
        $this->assertNotNull($a->foto_pulang_path);
    }

    /**
     * Label & jam pada modal konfirmasi harus berasal dari baris yang tersimpan.
     * Kalau diambil dari computed $aksi sebelum submit, kasus balapan bisa
     * menampilkan "Absen Masuk Berhasil" untuk baris yang sebenarnya pulang.
     */
    public function test_masuk_mengirim_payload_aksi_dan_jam_dari_baris_tersimpan(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-08-26 08:07:30'));

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', $this->fotoData())
            ->assertHasNoErrors()
            ->assertDispatched('absen-tersimpan', aksi: 'masuk', jam: '08:07');
    }

    public function test_pulang_mengirim_payload_aksi_pulang_dan_jam_pulang(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();
        Absensi::factory()->create([
            'karyawan_id' => $user->karyawan_id,
            'jam_pulang' => null,
        ]);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-08-26 16:45:10'));

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', $this->fotoData())
            ->assertHasNoErrors()
            ->assertDispatched('absen-tersimpan', aksi: 'pulang', jam: '16:45');
    }

    /**
     * Jalur gagal juga harus naik ke modal — teks 11px di bawah tombol gampang
     * kelewat di HP, dan itulah yang bikin orang menekan tombol lagi.
     */
    public function test_penolakan_radius_mengirim_event_gagal_untuk_modal(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.95)->set('long', 107.65)->set('akurasi', 8)
            ->call('simpan', $this->fotoData())
            ->assertHasErrors('lat')
            ->assertDispatched('absen-gagal', pesan: 'Di luar radius kantor — absen ditolak.');
    }

    public function test_sukses_tidak_mengirim_event_gagal(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', $this->fotoData())
            ->assertNotDispatched('absen-gagal');
    }

    /**
     * MediaPipe memuat berkasnya berurutan (glue JS → wasm → tflite). Preload di
     * <head> yang bikin ketiganya berangkat barengan; kalau @stack('head') di layout
     * hilang, halaman tetap jalan tapi diam-diam melambat lagi. Jadi dijaga test.
     */
    public function test_halaman_absensi_mempreload_berkas_detektor_wajah(): void
    {
        $user = $this->userKaryawan();
        $html = $this->actingAs($user)->get('/absensi')->assertOk()->getContent();

        // crossorigin wajib walau se-origin: tanpa itu preload tak cocok dengan
        // permintaan MediaPipe dan berkasnya diunduh DUA KALI.
        $this->assertMatchesRegularExpression(
            '~<link rel="preload" href="/mediapipe/blaze_face_short_range\.tflite"[^>]*crossorigin~',
            $html,
            'preload model tflite hilang dari <head> atau tanpa crossorigin',
        );

        // Varian wasm dipilih di klien lewat probe SIMD — KEDUA namanya harus ada di
        // halaman, kalau salah satu hilang berarti variannya di-hardcode lagi.
        $this->assertStringContainsString('vision_wasm', $html);
        $this->assertStringContainsString('_nosimd', $html);
        $this->assertStringContainsString('WebAssembly.validate', $html);
    }

    public function test_foto_webp_dari_klien_diterima(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', $this->fotoData('webp'))
            ->assertHasNoErrors();

        $this->assertSame(1, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    /**
     * WithFileUploads sudah dicabut; foto lewat argumen. Kalau ada yang mengembalikan
     * $wire.upload(), sekali absen jadi empat permintaan lagi tanpa ada yang sadar.
     */
    public function test_komponen_tidak_lagi_memakai_upload_livewire(): void
    {
        $this->assertNotContains(
            \Livewire\WithFileUploads::class,
            class_uses_recursive(\App\Livewire\Absensi\AbsenSwipe::class),
            'AbsenSwipe kembali memakai WithFileUploads — sekali absen jadi 4 permintaan HTTP.',
        );
    }

    public function test_foto_kosong_atau_bukan_gambar_ditolak(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        foreach ([null, '', 'bukan-data-url', 'data:text/html;base64,'.base64_encode('<b>')] as $buruk) {
            \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
                ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
                ->call('simpan', $buruk)
                ->assertHasErrors('foto')
                ->assertDispatched('absen-gagal');
        }

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    /** Data URL bertipe gambar tapi isinya sampah: lolos decoder, harus dijegal GD. */
    public function test_data_url_gambar_palsu_ditolak_tanpa_menulis_berkas(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', 'data:image/webp;base64,'.base64_encode('bukan gambar sama sekali'))
            ->assertHasErrors('foto');

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_foto_kelewat_besar_ditolak(): void
    {
        Storage::fake('local');
        $this->setPengaturan();
        $user = $this->userKaryawan();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Absensi\AbsenSwipe::class)
            ->set('lat', -6.9)->set('long', 107.6)->set('akurasi', 8)
            ->call('simpan', 'data:image/webp;base64,'.str_repeat('A', 8 * 1024 * 1024))
            ->assertHasErrors('foto');

        $this->assertSame(0, Absensi::where('karyawan_id', $user->karyawan_id)->count());
    }

    /**
     * Runtime detektor kandidat (TensorFlow.js + BlazeFace) di-host sendiri, bukan CDN.
     * Kalau salah satu berkasnya hilang saat deploy, halaman absen tetap terbuka dan
     * gagalnya baru ketahuan di HP staf — jadi keberadaannya dijaga di sini.
     */
    public function test_berkas_runtime_detektor_tfjs_tersedia(): void
    {
        foreach ([
            'blazeface-front.json',
            'blazeface-front.bin',
            'tfjs-backend-wasm-simd.wasm',
            'tfjs-backend-wasm.wasm',
        ] as $berkas) {
            $this->assertFileExists(public_path("wajah/$berkas"));
        }
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
