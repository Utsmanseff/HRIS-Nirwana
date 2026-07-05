<?php

namespace App\Livewire\Cuti;

use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Support\SaldoCuti;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CutiIndex extends Component
{
    private function karyawan(): Karyawan
    {
        return auth()->user()->karyawan()->firstOrFail();
    }

    public function render()
    {
        $kar = $this->karyawan();
        $saldo = SaldoCuti::untuk($kar);

        return view('livewire.cuti.cuti-index', [
            'karyawan' => $kar,
            'saldo' => $saldo,
            'pengajuan' => $kar->pengajuanCuti()->with(['jenisCuti', 'approval'])->get(),
            'hariLibur' => HariLibur::dalamRentang(Carbon::today(), Carbon::today()->addMonths(3))->get(),
        ]);
    }
}
