<?php

namespace App\Livewire\Absensi;

use App\Models\Jadwal;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Jadwal Saya')]
class JadwalSaya extends Component
{
    /** Bulan aktif, format Y-m. */
    #[Url]
    public string $bulan = '';

    public function mount(): void
    {
        if ($this->bulan === '' || ! preg_match('/^\d{4}-\d{2}$/', $this->bulan)) {
            $this->bulan = now()->format('Y-m');
        }
    }

    public function geser(int $arah): void
    {
        $this->bulan = $this->awalBulan()->addMonths($arah)->format('Y-m');
    }

    private function awalBulan(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->bulan.'-01')->startOfMonth();
    }

    public function render()
    {
        $kar = auth()->user()->karyawan()->first();
        abort_unless($kar, 403);

        $awal = $this->awalBulan();
        $akhir = (clone $awal)->endOfMonth();

        $jadwal = Jadwal::where('karyawan_id', $kar->id)
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->with('shift')
            ->orderBy('tanggal')
            ->get();

        return view('livewire.absensi.jadwal-saya', [
            'jadwal' => $jadwal,
            'labelBulan' => $awal->locale('id')->translatedFormat('F Y'),
            'bisaMundur' => true,
        ]);
    }
}
