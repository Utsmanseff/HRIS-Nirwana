<?php

namespace App\Livewire;

use App\Enums\JenisKontrak;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Support\PengingatKontrak;
use App\Support\PengingatSip;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $data = [];

        if (auth()->user()->can('kelola-sdm')) {
            $pengingatKontrak = PengingatKontrak::semua()->sortBy('sisaHari')->values();
            $pengingatSip = PengingatSip::semua()->sortBy('sisaHari')->values();

            $data = [
                'jumlahAktif' => Karyawan::where('status', StatusKaryawan::Aktif->value)->count(),
                'jumlahAkanBerakhir' => $pengingatKontrak->where('sisaHari', '>=', 0)->count(),
                'jumlahTerlewat' => $pengingatKontrak->where('sisaHari', '<', 0)->count(),
                'jumlahBelumTetap' => Karyawan::where('status', StatusKaryawan::Aktif->value)
                    ->whereHas('kontrakTerbaru', fn ($q) => $q->where('jenis', '!=', JenisKontrak::Tetap->value))
                    ->count(),
                'pengingatKontrak' => $pengingatKontrak->take(8),
                'pengingatSip' => $pengingatSip->take(5),
                'totalPerhatian' => $pengingatKontrak->count() + $pengingatSip->count(),
            ];
        }

        return view('livewire.dashboard', $data + ['bisaSdm' => auth()->user()->can('kelola-sdm')]);
    }
}
