<?php

namespace App\Livewire\Absensi;

use App\Enums\Role;
use App\Models\OrgUnit;
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
        $f = [
            'dari' => $this->dari ?: null,
            'sampai' => $this->sampai ?: null,
            'unit' => $this->unit ?: null,
            'status' => $this->status ?: null,
            'cari' => $this->cari ?: null,
        ];

        // Koordinator (bukan HRD) dibatasi subtree unit yang dia pimpin.
        $user = auth()->user();
        if (! $user->hasRole(Role::Hrd->value) && ! $this->unit) {
            $unitDipimpin = $user->karyawan?->unitDipimpin();
            if ($unitDipimpin && $unitDipimpin->isNotEmpty()) {
                $f['unit'] = $unitDipimpin->first()->id;
            }
        }

        return $f;
    }

    public function render()
    {
        $f = $this->filter();

        return view('livewire.absensi.laporan-absensi', [
            'baris' => RekapAbsensi::ambil($f),
            'stat' => RekapAbsensi::statistik($f),
            'unitOpsi' => OrgUnit::orderBy('nama')->get(),
            'query' => array_filter([
                'dari' => $this->dari, 'sampai' => $this->sampai, 'unit' => $this->unit,
                'status' => $this->status, 'cari' => $this->cari,
            ]),
        ]);
    }
}
