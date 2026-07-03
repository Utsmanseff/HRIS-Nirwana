<?php

namespace App\Livewire\Sdm;

use App\Enums\JabatanLevel;
use App\Models\Jabatan;
use App\Models\OrgUnit;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JabatanKelola extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $nama = '';

    public int $level = 1;

    public ?int $orgUnitId = null;

    public function baru(): void
    {
        $this->reset(['editingId', 'nama', 'orgUnitId']);
        $this->level = 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $jab = Jabatan::findOrFail($id);
        $this->editingId = $jab->id;
        $this->nama = $jab->nama;
        $this->level = $jab->level->value;
        $this->orgUnitId = $jab->org_unit_id;
        $this->showForm = true;
    }

    public function batal(): void
    {
        $this->reset(['showForm', 'editingId', 'nama', 'orgUnitId']);
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:120'],
            'level' => ['required', 'integer', 'in:1,2,3,4'],
            'orgUnitId' => ['required', 'exists:org_units,id'],
        ]);

        Jabatan::updateOrCreate(['id' => $this->editingId], [
            'nama' => $data['nama'],
            'level' => $data['level'],
            'org_unit_id' => $data['orgUnitId'],
            'aktif' => true,
        ]);

        $this->batal();
    }

    public function render()
    {
        $jabatan = Jabatan::query()
            ->with('orgUnit')
            ->withCount('karyawan')
            ->orderBy('level')->orderBy('nama')->get();

        return view('livewire.sdm.jabatan-kelola', [
            'jabatan' => $jabatan,
            'levels' => JabatanLevel::cases(),
            'unitOptions' => OrgUnit::orderBy('nama')->get(['id', 'nama']),
        ]);
    }
}
