<?php

namespace App\Livewire\Cuti;

use App\Models\OrgUnit;
use App\Support\KalenderCuti;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class KalenderTim extends Component
{
    #[Url]
    public string $bulan = '';

    #[Url]
    public string $unitId = '';

    #[Url]
    public string $hariAktif = '';

    public function mount(): void
    {
        if ($this->bulan === '') {
            $this->bulan = Carbon::now()->format('Y-m');
        }
    }

    public function bulanSebelumnya(): void
    {
        $this->bulan = $this->anchor()->subMonth()->format('Y-m');
        $this->hariAktif = '';
    }

    public function bulanBerikutnya(): void
    {
        $this->bulan = $this->anchor()->addMonth()->format('Y-m');
        $this->hariAktif = '';
    }

    public function pilihHari(string $ymd): void
    {
        $this->hariAktif = $this->hariAktif === $ymd ? '' : $ymd;
    }

    public function updatedUnitId(): void
    {
        $this->hariAktif = '';
    }

    /** Anchor = awal bulan target (guard parse gagal → bulan ini). */
    private function anchor(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->bulan)->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }

    private function isHrd(): bool
    {
        return auth()->user()->can('kelola-cuti');
    }

    /** @return array<int> */
    private function scopeUnitIds(): array
    {
        $user = auth()->user();

        if ($this->isHrd()) {
            if ($this->unitId !== '' && OrgUnit::whereKey($this->unitId)->exists()) {
                return OrgUnit::denganTurunan((int) $this->unitId);
            }

            return OrgUnit::pluck('id')->all();
        }

        $unitId = $user->karyawan?->org_unit_id;

        return $unitId ? OrgUnit::denganTurunan($unitId) : [];
    }

    public function render()
    {
        $anchor = $this->anchor();
        $data = KalenderCuti::bulan($this->scopeUnitIds(), $anchor);

        // Susun grid: mundur ke Senin pertama, maju ke Minggu terakhir.
        $mulaiGrid = $data['awal']->copy()->startOfWeek(Carbon::MONDAY);
        $akhirGrid = $data['akhir']->copy()->endOfWeek(Carbon::SUNDAY);

        $sel = [];
        for ($d = $mulaiGrid->copy(); $d->lte($akhirGrid); $d->addDay()) {
            $sel[] = [
                'tanggal' => $d->copy(),
                'ymd' => $d->format('Y-m-d'),
                'luar' => $d->month !== $data['awal']->month,
            ];
        }

        return view('livewire.cuti.kalender-tim', [
            'hari' => $data['hari'],
            'minggu' => array_chunk($sel, 7),
            'bulanLabel' => $anchor->locale('id')->translatedFormat('F Y'),
            'isHrd' => $this->isHrd(),
            'daftarUnit' => $this->isHrd() ? OrgUnit::orderBy('nama')->get() : collect(),
        ]);
    }
}
