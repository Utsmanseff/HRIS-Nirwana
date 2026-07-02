<?php

namespace App\Livewire\Sdm;

use App\Models\Jabatan;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JabatanKelola extends Component
{
    public function render()
    {
        $jabatan = Jabatan::query()
            ->withCount('karyawan')
            ->orderBy('level')->orderBy('nama')->get();

        return view('livewire.sdm.jabatan-kelola', ['jabatan' => $jabatan]);
    }
}
