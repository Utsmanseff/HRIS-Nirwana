<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\PengaturanAbsensi;
use App\Support\KompresGambar;
use App\Support\LokasiAbsen;
use App\Support\ProsesAbsen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class AbsenSwipe extends Component
{
    use WithFileUploads;

    // Data capture dari client (diverifikasi ulang di server — jangan dipercaya).
    public $foto = null;
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

    /** Jadwal + shift hari ini (info; ProsesAbsen tetap snapshot ulang saat masuk). */
    #[Computed]
    public function shiftHariIni()
    {
        return Jadwal::where('karyawan_id', auth()->user()->karyawan_id)
            ->whereDate('tanggal', now()->toDateString())
            ->with('shift')
            ->first()?->shift;
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

    public function simpan(): void
    {
        $this->validate([
            'foto' => ['required', 'image', 'max:5120'],
            'lat' => ['required', 'numeric'],
            'long' => ['required', 'numeric'],
            'akurasi' => ['required', 'numeric', 'min:0'],
        ], [], ['foto' => 'foto', 'lat' => 'lokasi', 'akurasi' => 'akurasi']);

        $p = PengaturanAbsensi::ambil();

        // OTORITAS SERVER: hitung ulang Haversine + akurasi. Client cuma gerbang UX.
        if (! LokasiAbsen::dalamRadius((float) $this->lat, (float) $this->long, $p)) {
            $this->addError('lat', 'Di luar radius kantor — absen ditolak.');

            return;
        }
        if (! LokasiAbsen::akurasiDiterima((float) $this->akurasi, $p)) {
            $this->addError('akurasi', 'Akurasi lokasi terlalu buruk — coba lagi di tempat terbuka.');

            return;
        }

        $kar = auth()->user()->karyawan;

        // Simpan foto → WebP (disk local privat).
        $webp = KompresGambar::keWebp($this->foto->get(), 80, 720);
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

        ProsesAbsen::sesiAktif($kar)
            ? ProsesAbsen::pulang($kar, $data)
            : ProsesAbsen::masuk($kar, $data);

        // Bersihkan capture + segarkan computed (sesi/aksi/riwayat).
        $this->reset('foto', 'lat', 'long', 'akurasi', 'wajahAda');
        unset($this->sesi, $this->aksi, $this->riwayat);
        $this->dispatch('absen-tersimpan');
        session()->flash('absen_ok', 'Absensi tercatat.');
    }

    public function render()
    {
        return view('livewire.absensi.absen-swipe', [
            'pengaturan' => PengaturanAbsensi::ambil(),
        ]);
    }
}
