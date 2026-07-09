<?php

namespace App\Livewire\Absensi;

use App\Models\OrgUnit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JadwalKelola extends Component
{
    #[Url]
    public string $tab = 'shift';   // shift | template | jadwal

    #[Url]
    public ?int $unitId = null;

    public function mount(): void
    {
        $unit = $this->unitDipimpin()->first();
        $this->unitId ??= $unit?->id;
    }

    /** Unit-unit yang dipimpin user (untuk selektor). */
    public function unitDipimpin()
    {
        return auth()->user()->karyawan?->unitDipimpin() ?? collect();
    }

    public function gantiTab(string $tab): void
    {
        $this->tab = in_array($tab, ['shift', 'template', 'jadwal'], true) ? $tab : 'shift';
    }

    public function gantiUnit(int $unitId): void
    {
        if ($this->unitDipimpin()->contains('id', $unitId)) {
            $this->unitId = $unitId;
        }
    }

    protected function unitTerpilih(): ?OrgUnit
    {
        return $this->unitId ? OrgUnit::find($this->unitId) : null;
    }

    public function render()
    {
        return view('livewire.absensi.jadwal-kelola', [
            'unitList' => $this->unitDipimpin(),
            'unit' => $this->unitTerpilih(),
        ]);
    }
}
