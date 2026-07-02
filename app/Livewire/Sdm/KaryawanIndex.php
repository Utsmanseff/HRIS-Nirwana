<?php

namespace App\Livewire\Sdm;

use App\Enums\JenisKontrak;
use App\Enums\SeverityPengingat;
use App\Models\Karyawan;
use App\Support\PengingatKontrak;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class KaryawanIndex extends Component
{
    use WithPagination;

    /** Teks + kelas badge untuk kolom Kontrak. */
    public function badgeKontrak(Karyawan $k): array
    {
        $terbaru = $k->kontrakTerbaru;
        if (! $terbaru) {
            return ['Belum ada kontrak', 'badge-neutral'];
        }

        $pengingat = PengingatKontrak::untuk($k);
        if ($pengingat) {
            return $pengingat->severity === SeverityPengingat::Terlewat
                ? [$terbaru->jenis->label().' terlewat', 'badge-danger']
                : [$terbaru->jenis->label().' · H-'.$pengingat->sisaHari, 'badge-warning'];
        }

        return [
            $terbaru->jenis->label(),
            $terbaru->jenis === JenisKontrak::Tetap ? 'badge-brand' : 'badge-info',
        ];
    }

    public function render()
    {
        $karyawan = Karyawan::query()
            ->with(['orgUnit.parent', 'jabatan', 'atasan.jabatan', 'kontrakTerbaru', 'kontrak'])
            ->orderBy('nama_lengkap')
            ->paginate(15);

        return view('livewire.sdm.karyawan-index', ['karyawan' => $karyawan]);
    }
}
