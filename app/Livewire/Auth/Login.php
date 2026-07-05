<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    public string $nip = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate(['nip' => ['required', 'string'], 'password' => ['required', 'string']]);

        $user = User::whereHas('karyawan', fn ($q) => $q->where('nip', $this->nip))->first();

        if (! $user || ! $user->password || ! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages(['nip' => 'NIP atau kata sandi salah.']);
        }

        if (! $user->akunAktif()) {
            throw ValidationException::withMessages(['nip' => 'Akun dinonaktifkan. Hubungi admin sistem.']);
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        return $this->redirect('/beranda', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
