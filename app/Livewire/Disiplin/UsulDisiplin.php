<?php

namespace App\Livewire\Disiplin;

use App\Models\Karyawan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class UsulDisiplin extends Component
{
    public function mount(): void
    {
        abort_unless(Gate::allows('usul-disiplin'), 403);
    }

    protected function pengusul(): Karyawan
    {
        return auth()->user()->karyawan()->firstOrFail();
    }

    public function render()
    {
        $pengusul = $this->pengusul();

        return view('livewire.disiplin.usul-disiplin', [
            'pengusul' => $pengusul,
            'usulan' => $pengusul->usulanSanksi()->with(['karyawan', 'approval'])->get(),
        ]);
    }
}
