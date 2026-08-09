<?php

namespace App\Livewire\Sdm;

use App\Models\JenisIzin;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JenisIzinKelola extends Component
{
    public ?int $editId = null;

    public string $nama = '';

    public ?int $ambangHari = null;

    public bool $aktif = true;

    public function edit(int $id): void
    {
        $j = JenisIzin::findOrFail($id);
        $this->editId = $j->id;
        $this->nama = $j->nama;
        $this->ambangHari = $j->ambang_hari;
        $this->aktif = (bool) $j->aktif;
    }

    public function batal(): void
    {
        $this->reset(['editId', 'nama', 'ambangHari', 'aktif']);
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:100'],
            'ambangHari' => ['required', 'integer', 'min:1', 'max:365'],
            'aktif' => ['boolean'],
        ]);

        // `kode` sengaja tak ikut — ia dirujuk kode program, jadi read-only di UI.
        JenisIzin::whereKey($this->editId)->update([
            'nama' => $data['nama'],
            'ambang_hari' => $data['ambangHari'],
            'aktif' => $data['aktif'],
        ]);

        $this->batal();
        session()->flash('ok', 'Jenis izin diperbarui.');
    }

    public function render()
    {
        return view('livewire.sdm.jenis-izin-kelola', [
            'daftar' => JenisIzin::orderBy('nama')->get(),
        ]);
    }
}
