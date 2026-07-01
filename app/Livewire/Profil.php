<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Profil extends Component
{
    public function render()
    {
        $karyawan = auth()->user()->karyawan()->with(['jabatan', 'orgUnit', 'atasan'])->first();

        return view('livewire.profil', ['karyawan' => $karyawan]);
    }
}
