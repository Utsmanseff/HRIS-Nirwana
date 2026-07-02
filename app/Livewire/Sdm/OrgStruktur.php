<?php

namespace App\Livewire\Sdm;

use App\Models\OrgUnit;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrgStruktur extends Component
{
    public function render()
    {
        // Muat pohon: akar + anak berjenjang (kedalaman wajar 3-4 level).
        $akar = OrgUnit::query()->akar()
            ->withCount('karyawan')
            ->with(['children' => fn ($q) => $q->withCount('karyawan')
                ->with(['children' => fn ($q2) => $q2->withCount('karyawan')
                    ->with('children')])])
            ->orderBy('nama')->get();

        return view('livewire.sdm.org-struktur', ['akar' => $akar]);
    }
}
