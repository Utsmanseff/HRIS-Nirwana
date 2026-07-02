<?php

namespace App\Livewire\Sdm;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JabatanKelola extends Component
{
    public function render()
    {
        return view('livewire.sdm.jabatan-kelola');
    }
}
