<?php

namespace App\Livewire\Absensi;

use App\Support\LabelPengganti;
use App\Support\LingkupAbsensi;
use App\Support\RekapAbsensi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LaporanAbsensi extends Component
{
    #[Url]
    public string $dari = '';
    #[Url]
    public string $sampai = '';
    #[Url]
    public ?int $unit = null;
    #[Url]
    public string $status = '';
    #[Url]
    public string $cari = '';

    public function mount(): void
    {
        // Default periode = hari ini (rekap harian).
        if ($this->dari === '') {
            $this->dari = now()->toDateString();
        }
        if ($this->sampai === '') {
            $this->sampai = now()->toDateString();
        }
    }

    /** @return array<string,mixed> */
    private function filter(): array
    {
        return [
            'dari' => $this->dari ?: null,
            'sampai' => $this->sampai ?: null,
            'unit' => LingkupAbsensi::unitEfektif(auth()->user(), $this->unit ?: null),
            'status' => $this->status ?: null,
            'cari' => $this->cari ?: null,
        ];
    }

    public function render()
    {
        $f = $this->filter();
        $baris = RekapAbsensi::ambil($f);

        return view('livewire.absensi.laporan-absensi', [
            'baris' => $baris,
            'keterangan' => LabelPengganti::petaAbsensi($baris),
            'stat' => RekapAbsensi::statistik($f),
            'unitOpsi' => LingkupAbsensi::opsiUnit(auth()->user()),
            'query' => array_filter([
                'dari' => $this->dari, 'sampai' => $this->sampai, 'unit' => $this->unit,
                'status' => $this->status, 'cari' => $this->cari,
            ]),
        ]);
    }
}
