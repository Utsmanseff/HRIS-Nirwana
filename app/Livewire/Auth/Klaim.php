<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Klaim extends Component
{
    public string $q = '';

    public function hasil()
    {
        if (mb_strlen(trim($this->q)) < 3) {
            return collect();
        }

        $term = '%'.trim($this->q).'%';

        return Karyawan::query()
            ->where('status', StatusKaryawan::Aktif->value)
            ->whereDoesntHave('user')
            ->where(fn ($w) => $w->where('nama_lengkap', 'like', $term)
                ->orWhere('nip', 'like', $term)->orWhere('nik', 'like', $term))
            ->limit(10)->get();
    }

    public function klaim(int $karyawanId)
    {
        $kar = Karyawan::query()
            ->where('id', $karyawanId)
            ->where('status', StatusKaryawan::Aktif->value)
            ->whereDoesntHave('user')->first();

        if (! $kar) {
            throw ValidationException::withMessages(['q' => 'Data ini sudah terhubung ke akun lain. Hubungi admin.']);
        }

        /** @var User $user */
        $user = auth()->user();
        $user->update(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Karyawan->value);

        return $this->redirect('/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.klaim', ['hasil' => $this->hasil()]);
    }
}
