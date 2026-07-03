<?php

namespace App\Livewire\Sdm;

use App\Enums\OrgUnitTipe;
use App\Models\OrgUnit;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrgStruktur extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $tipe = 'unit';

    public ?int $parentId = null;

    public function baru(?int $parentId = null): void
    {
        $this->reset(['editingId', 'nama']);
        $this->tipe = 'unit';
        $this->parentId = $parentId;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $unit = OrgUnit::findOrFail($id);
        $this->editingId = $unit->id;
        $this->nama = $unit->nama;
        $this->tipe = $unit->tipe->value;
        $this->parentId = $unit->parent_id;
        $this->showForm = true;
    }

    public function batal(): void
    {
        $this->reset(['showForm', 'editingId', 'nama', 'parentId']);
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:120'],
            'tipe' => ['required', 'in:direktur,bidang,bagian,unit'],
            'parentId' => ['nullable', 'exists:org_units,id'],
        ]);

        OrgUnit::updateOrCreate(['id' => $this->editingId], [
            'nama' => $data['nama'],
            'tipe' => $data['tipe'],
            'parent_id' => $data['parentId'],
            'aktif' => true,
        ]);

        $this->batal();
    }

    public function render()
    {
        // Muat pohon: akar + anak berjenjang (kedalaman wajar 3-4 level).
        $akar = OrgUnit::query()->akar()
            ->withCount('karyawan')
            ->with(['children' => fn ($q) => $q->withCount('karyawan')
                ->with(['children' => fn ($q2) => $q2->withCount('karyawan')
                    ->with('children')])])
            ->orderBy('nama')->get();

        return view('livewire.sdm.org-struktur', [
            'akar' => $akar,
            'semuaUnit' => OrgUnit::orderBy('nama')->get(['id', 'nama']),
            'tipeOptions' => OrgUnitTipe::cases(),
        ]);
    }
}
