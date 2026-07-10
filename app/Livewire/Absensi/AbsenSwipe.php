<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\PengaturanAbsensi;
use App\Support\ProsesAbsen;
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

    public function render()
    {
        return view('livewire.absensi.absen-swipe', [
            'pengaturan' => PengaturanAbsensi::ambil(),
        ]);
    }
}
