<?php

namespace App\Livewire\Sistem;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;
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

    public ?int $kelolaId = null;

    /** @var list<string> */
    public array $rolePilihan = [];

    public ?string $sandiSementara = null;

    public function bukaKelola(int $id): void
    {
        $user = User::findOrFail($id);
        $this->kelolaId = $user->id;
        $this->rolePilihan = $user->roles->pluck('name')->all();
        $this->sandiSementara = null;
        $this->resetErrorBag();
    }

    public function tutupKelola(): void
    {
        $this->reset(['kelolaId', 'rolePilihan', 'sandiSementara']);
        $this->resetErrorBag();
    }

    /** Ambil user target aksi; tolak bila akun sendiri (anti terkunci sendiri). */
    private function targetKelola(): ?User
    {
        $user = User::findOrFail($this->kelolaId);

        if ($user->id === auth()->id()) {
            $this->addError('kelola', 'Tidak bisa mengubah akun sendiri.');

            return null;
        }

        return $user;
    }

    public function simpanRole(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $valid = array_values(array_intersect(
            $this->rolePilihan,
            array_column(Role::cases(), 'value'),
        ));

        $user->syncRoles($valid);
        $this->rolePilihan = $valid;
        session()->flash('pesan', 'Role tersimpan.');
    }

    public function resetSandi(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $sandi = Str::password(12, symbols: false);
        $user->update(['password' => $sandi]); // cast 'hashed' otomatis meng-hash

        $this->sandiSementara = $sandi;
    }

    public function toggleAktif(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $user->update(['nonaktif_pada' => $user->akunAktif() ? now() : null]);
    }

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
