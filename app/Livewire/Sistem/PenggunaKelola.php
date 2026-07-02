<?php

namespace App\Livewire\Sistem;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PenggunaKelola extends Component
{
    #[Url]
    public string $tab = 'pengguna';

    public function render()
    {
        return view('livewire.sistem.pengguna-kelola');
    }
}
