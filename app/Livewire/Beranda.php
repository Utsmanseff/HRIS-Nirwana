<?php

namespace App\Livewire;

use App\Enums\JenisKontrak;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Support\NavMenu;
use App\Support\PengingatKontrak;
use App\Support\PengingatSip;
use App\Support\SaldoCuti;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Beranda extends Component
{
    public function render()
    {
        $user = auth()->user();
        $bisaSdm = $user->can('kelola-sdm');
        $data = ['bisaSdm' => $bisaSdm, 'menu' => NavMenu::untuk($user)];

        if ($bisaSdm) {
            $pengingatKontrak = PengingatKontrak::semua()->sortBy('sisaHari')->values();
            $pengingatSip = PengingatSip::semua()->sortBy('sisaHari')->values();
            $data += [
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

        // Kartu jatah cuti untuk siapa pun yang punya data karyawan.
        $kar = $user->karyawan()->first();
        $data['saldo'] = $kar ? SaldoCuti::untuk($kar) : null;

        return view('livewire.beranda', $data);
    }
}
