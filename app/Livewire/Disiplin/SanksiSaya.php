<?php

namespace App\Livewire\Disiplin;

use App\Models\Karyawan;
use App\Support\EskalasiSanksi;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SanksiSaya extends Component
{
    public Karyawan $karyawan;

    public function mount(): void
    {
        $kar = auth()->user()->karyawan()->first();
        abort_unless($kar, 403);
        $this->karyawan = $kar;
    }

    public function render()
    {
        $semua = $this->karyawan->sanksiDisiplin()->with('pengusul')->get();
        $aktif = EskalasiSanksi::sanksiAktif($this->karyawan);
        $aktifIds = $aktif->pluck('id')->all();

        return view('livewire.disiplin.sanksi-saya', [
            'aktif' => $aktif,
            'riwayat' => $semua->reject(fn ($s) => in_array($s->id, $aktifIds, true))->values(),
        ]);
    }
}
