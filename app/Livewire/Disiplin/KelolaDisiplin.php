<?php

namespace App\Livewire\Disiplin;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\SanksiDisiplin;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KelolaDisiplin extends Component
{
    #[Url]
    public string $cari = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterTingkat = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('buat-sanksi'), 403);
    }

    protected function daftar()
    {
        return SanksiDisiplin::query()
            ->when($this->cari !== '', fn ($q) => $q->whereHas('karyawan', fn ($k) => $k
                ->where('nama_lengkap', 'like', "%{$this->cari}%")
                ->orWhere('nip', 'like', "%{$this->cari}%")))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterTingkat !== '', fn ($q) => $q->where('tingkat', $this->filterTingkat))
            ->with(['karyawan.jabatan', 'pengusul'])
            ->latest()
            ->limit(100)
            ->get();
    }

    public function render()
    {
        return view('livewire.disiplin.kelola-disiplin', [
            'daftar' => $this->daftar(),
            'statusOpsi' => StatusSanksi::cases(),
            'tingkatOpsi' => TingkatSanksi::cases(),
            'bisaCabut' => auth()->user()->can('kelola-disiplin'),
        ]);
    }
}
