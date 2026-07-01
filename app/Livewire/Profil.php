<?php

namespace App\Livewire;

use App\Models\Karyawan;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Profil extends Component
{
    public string $no_hp = '';

    public string $email = '';

    public string $alamat = '';

    public string $password_lama = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $kar = $this->karyawan();
        $this->no_hp = $kar->no_hp ?? '';
        $this->email = $kar->email ?? '';
        $this->alamat = $kar->alamat ?? '';
    }

    private function karyawan(): Karyawan
    {
        return auth()->user()->karyawan()->with(['jabatan', 'orgUnit', 'atasan'])->firstOrFail();
    }

    public function simpanKontak(): void
    {
        $data = $this->validate([
            'no_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        auth()->user()->karyawan()->update($data);

        session()->flash('kontak_ok', 'Kontak diperbarui.');
    }

    public function punyaPassword(): bool
    {
        return auth()->user()->password !== null;
    }

    public function simpanPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        if ($this->punyaPassword() && ! Hash::check($this->password_lama, $user->password)) {
            $this->addError('password_lama', 'Kata sandi lama salah.');

            return;
        }

        $user->update(['password' => $this->password]); // cast 'hashed' meng-hash otomatis

        $this->reset(['password_lama', 'password', 'password_confirmation']);
        session()->flash('password_ok', 'Kata sandi disimpan.');
    }

    public function render()
    {
        return view('livewire.profil', ['karyawan' => $this->karyawan()]);
    }
}
