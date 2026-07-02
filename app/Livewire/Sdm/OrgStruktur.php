<?php

namespace App\Livewire\Sdm;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrgStruktur extends Component
{
    public function render()
    {
        return view('livewire.sdm.org-struktur');
    }
}
