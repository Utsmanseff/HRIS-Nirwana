<?php

namespace App\Livewire\Cuti;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KelolaCuti extends Component
{
    #[Url]
    public string $tab = 'hari-libur';

    public function render()
    {
        return view('livewire.cuti.kelola-cuti');
    }
}
