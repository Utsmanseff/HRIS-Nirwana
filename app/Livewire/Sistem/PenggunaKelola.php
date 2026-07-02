<?php

namespace App\Livewire\Sistem;

use App\Enums\Role;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class PenggunaKelola extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'pengguna';

    #[Url]
    public string $q = '';

    #[Url]
    public string $filterRole = '';

    #[Url]
    public string $filterStatus = '';

    public function updating($name, $value): void
    {
        if (in_array($name, ['q', 'filterRole', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $users = User::query()
            ->with(['roles', 'karyawan'])
            ->when($this->q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->q}%")
                ->orWhere('email', 'like', "%{$this->q}%")
                ->orWhereHas('karyawan', fn ($k) => $k
                    ->where('nip', 'like', "%{$this->q}%")
                    ->orWhere('nama_lengkap', 'like', "%{$this->q}%"))))
            ->when($this->filterRole !== '', fn ($query) => $query->role($this->filterRole))
            ->when($this->filterStatus === 'aktif', fn ($query) => $query->whereNull('nonaktif_pada'))
            ->when($this->filterStatus === 'nonaktif', fn ($query) => $query->whereNotNull('nonaktif_pada'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.sistem.pengguna-kelola', [
            'users' => $users,
            'semuaRole' => Role::cases(),
        ]);
    }
}
