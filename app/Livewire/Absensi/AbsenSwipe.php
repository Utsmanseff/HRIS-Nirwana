<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\PengaturanAbsensi;
use App\Support\JadwalHarian;
use App\Support\KompresGambar;
use App\Support\LokasiAbsen;
use App\Support\FotoDataUrl;
use App\Support\ProsesAbsen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.app')]
class AbsenSwipe extends Component
{
    /** Batas ukuran foto setelah didekode (5 MB, sama dengan aturan `max:5120` dulu). */
    private const MAKS_FOTO = 5 * 1024 * 1024;

    // Data capture dari client (diverifikasi ulang di server — jangan dipercaya).
    public ?float $lat = null;
    public ?float $long = null;
    public ?float $akurasi = null;
    public bool $wajahAda = true;

    /** Sesi terbuka milik karyawan (null bila tak ada). */
    #[Computed]
    public function sesi(): ?Absensi
    {
        return ProsesAbsen::sesiAktif(auth()->user()->karyawan);
    }

    /** 'masuk' bila tak ada sesi aktif, else 'pulang'. */
    #[Computed]
    public function aksi(): string
    {
        return $this->sesi ? 'pulang' : 'masuk';
    }

    /** Semua jadwal hari ini (info; ProsesAbsen tetap snapshot ulang saat masuk). */
    #[Computed]
    public function jadwalHariIni()
    {
        return JadwalHarian::untuk(auth()->user()->karyawan, now());
    }

    /** Jadwal yang akan dipakai bila absen masuk sekarang (null = mode catat). */
    #[Computed]
    public function jadwalTerpilih(): ?Jadwal
    {
        return JadwalHarian::pilihUntukAbsen(auth()->user()->karyawan, now());
    }

    /**
     * shift_id yang sudah punya sesi absensi hari ini. Beda dengan "tak terpilih":
     * shift yang belum tiba gilirannya bukan berarti sudah dijalani.
     */
    #[Computed]
    public function shiftTerpakai(): array
    {
        return Absensi::where('karyawan_id', auth()->user()->karyawan_id)
            ->whereDate('tanggal_kerja', now()->toDateString())
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->all();
    }

    /** Riwayat 7 sesi terakhir milik sendiri. */
    #[Computed]
    public function riwayat()
    {
        return Absensi::where('karyawan_id', auth()->user()->karyawan_id)
            ->latest('tanggal_kerja')
            ->latest('jam_masuk')
            ->take(7)
            ->get();
    }

    /**
     * Satu-satunya permintaan jaringan untuk sekali absen.
     *
     * Foto datang sebagai data URL di argumen, bukan lewat `$wire.upload()`. Satu
     * upload() Livewire = tiga permintaan berurutan (`_startUpload` → POST berkas →
     * `_finishUpload`), masing-masing boot Laravel penuh, dan simpan() jadi yang
     * keempat. Lihat App\Support\FotoDataUrl untuk alasan lengkapnya.
     */
    public function simpan(?string $foto = null): void
    {
        $this->validate([
            'lat' => ['required', 'numeric'],
            'long' => ['required', 'numeric'],
            'akurasi' => ['required', 'numeric', 'min:0'],
        ], [], ['lat' => 'lokasi', 'akurasi' => 'akurasi']);

        $biner = FotoDataUrl::dekode($foto, self::MAKS_FOTO);
        if ($biner === null) {
            $this->gagal('foto', 'Foto gagal diambil — coba lagi.');

            return;
        }

        $p = PengaturanAbsensi::ambil();

        // OTORITAS SERVER: hitung ulang Haversine + akurasi. Client cuma gerbang UX.
        if (! LokasiAbsen::dalamRadius((float) $this->lat, (float) $this->long, $p)) {
            $this->gagal('lat', 'Di luar radius kantor — absen ditolak.');

            return;
        }
        if (! LokasiAbsen::akurasiDiterima((float) $this->akurasi, $p)) {
            $this->gagal('akurasi', 'Akurasi lokasi terlalu buruk — coba lagi di tempat terbuka.');

            return;
        }

        $kar = auth()->user()->karyawan;

        // Simpan foto → WebP (disk local privat). Klien sudah mengecilkan & meng-encode,
        // tapi server tetap encode ulang: klien tak boleh jadi otoritas format/ukuran.
        try {
            $webp = KompresGambar::keWebp($biner, 80, 720);
        } catch (RuntimeException) {
            $this->gagal('foto', 'Foto tidak terbaca — coba lagi.');

            return;
        }
        $path = "absensi/{$kar->id}/".Str::ulid().'.webp';
        Storage::disk('local')->put($path, $webp);

        $data = [
            'jam' => now(),
            'foto_path' => $path,
            'lat' => (float) $this->lat,
            'long' => (float) $this->long,
            'akurasi' => (float) $this->akurasi,
            'wajah_verif' => $this->wajahAda,
            'flag_lokasi' => LokasiAbsen::heuristik((float) $this->akurasi),
        ];

        // Dua submit beruntun bisa balapan. ProsesAbsen::catat() memutuskan
        // masuk/pulang di dalam kunci per-karyawan; di sini tinggal urus jalur
        // gagalnya — jangan 500, tampilkan pesannya & buang foto yatim.
        try {
            $absensi = ProsesAbsen::catat($kar, $data);
        } catch (RuntimeException $e) {
            Storage::disk('local')->delete($path);
            unset($this->sesi, $this->aksi, $this->jadwalHariIni, $this->jadwalTerpilih, $this->shiftTerpakai);
            $this->gagal('sesi', $e->getMessage());

            return;
        }

        // Label & jam untuk alert dibaca dari BARIS YANG TERSIMPAN, bukan dari
        // computed $this->aksi sebelum submit. Computed itu query sendiri, terpisah
        // dari keputusan masuk/pulang di dalam kunci — pada kasus balapan ia bisa
        // bilang "masuk" padahal yang tercatat pulang. Jamnya pun harus jam DB,
        // supaya modal tak menyebut menit yang beda dari Riwayat tepat di bawahnya.
        $aksiTercatat = $absensi->jam_pulang ? 'pulang' : 'masuk';
        $jamTercatat = ($absensi->jam_pulang ?? $absensi->jam_masuk)->format('H:i');

        // Bersihkan capture + segarkan computed (sesi/aksi/riwayat).
        $this->reset('lat', 'long', 'akurasi', 'wajahAda');
        unset($this->sesi, $this->aksi, $this->riwayat, $this->jadwalHariIni, $this->jadwalTerpilih, $this->shiftTerpakai);
        $this->dispatch('absen-tersimpan', aksi: $aksiTercatat, jam: $jamTercatat);
    }

    /**
     * Kegagalan absen naik ke modal, bukan cuma teks 11px di bawah tombol.
     * Justru jalur gagal yang bikin orang menekan tombol lagi; kalau pesannya
     * tak terlihat di layar HP, tap kedua itulah yang jadi absen dobel.
     * addError tetap dipasang sebagai jaring pengaman bila Alpine/store gagal muat.
     */
    private function gagal(string $field, string $pesan): void
    {
        $this->addError($field, $pesan);
        $this->dispatch('absen-gagal', pesan: $pesan);
    }

    public function render()
    {
        return view('livewire.absensi.absen-swipe', [
            'pengaturan' => PengaturanAbsensi::ambil(),
        ]);
    }
}
