<?php

namespace App\Livewire\Sdm;

use App\Enums\JabatanLevel;
use App\Models\Jabatan;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JabatanKelola extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $nama = '';

    public int $level = 1;

    public function baru(): void
    {
        $this->reset(['editingId', 'nama']);
        $this->level = 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $jab = Jabatan::findOrFail($id);
        $this->editingId = $jab->id;
        $this->nama = $jab->nama;
        $this->level = $jab->level->value;
        $this->showForm = true;
    }

    public function batal(): void
    {
        $this->reset(['showForm', 'editingId', 'nama']);
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:120'],
            'level' => ['required', 'integer', 'in:1,2,3,4'],
        ]);

        Jabatan::updateOrCreate(['id' => $this->editingId], $data + ['aktif' => true]);

        $this->batal();
    }

    public function render()
    {
        $jabatan = Jabatan::query()
            ->withCount('karyawan')
            ->orderBy('level')->orderBy('nama')->get();

        return view('livewire.sdm.jabatan-kelola', [
            'jabatan' => $jabatan,
            'levels' => JabatanLevel::cases(),
        ]);
    }
}
