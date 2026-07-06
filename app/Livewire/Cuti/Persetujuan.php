<?php

namespace App\Livewire\Cuti;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Persetujuan extends Component
{
    #[Url]
    public string $tab = 'perlu-aksi';

    public function render()
    {
        return view('livewire.cuti.persetujuan');
    }
}
