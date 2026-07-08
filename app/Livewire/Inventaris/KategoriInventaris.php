<?php

namespace App\Livewire\Inventaris;

use App\Enums\TimTeknis;
use App\Models\KategoriInventaris as Kategori;
use App\Support\NavMenu;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KategoriInventaris extends Component
{
    public ?int $editId = null;

    public string $nama = '';

    public string $tim = '';

    /** @return list<string> nilai tim yang boleh user */
    private function timBoleh(): array
    {
        return array_map(fn (TimTeknis $t) => $t->value, auth()->user()->timTeknis());
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'tim' => ['required', Rule::in($this->timBoleh())],
        ];
    }

    public function simpan(): void
    {
        $data = $this->validate();
        Kategori::updateOrCreate(
            ['id' => $this->editId],
            ['nama' => $data['nama'], 'tim' => $data['tim'], 'aktif' => true],
        );
        $this->reset('editId', 'nama', 'tim');
    }

    public function edit(int $id): void
    {
        $k = Kategori::whereIn('tim', $this->timBoleh())->findOrFail($id);
        $this->editId = $k->id;
        $this->nama = $k->nama;
        $this->tim = $k->tim->value;
    }

    public function toggleAktif(int $id): void
    {
        $k = Kategori::whereIn('tim', $this->timBoleh())->findOrFail($id);
        $k->update(['aktif' => ! $k->aktif]);
    }

    public function batal(): void
    {
        $this->reset('editId', 'nama', 'tim');
    }

    public function render()
    {
        return view('livewire.inventaris.kategori-inventaris', [
            'daftar' => Kategori::whereIn('tim', $this->timBoleh())->withCount('aset')->orderBy('nama')->get(),
            'timOpsi' => auth()->user()->timTeknis(),
            'menu' => NavMenu::untuk(auth()->user()),
        ]);
    }
}
