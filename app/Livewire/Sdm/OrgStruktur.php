<?php

namespace App\Livewire\Sdm;

use App\Enums\OrgUnitTipe;
use App\Models\Jabatan;
use App\Models\Karyawan;
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

    // Panel Set Kepala
    public ?int $setKepalaUnitId = null;

    public string $cariKaryawan = '';

    // Tambah cepat kepala
    public string $tcNip = '';

    public string $tcNama = '';

    public string $tcTanggalMasuk = '';

    // Panel Kelola Jabatan Staff
    public ?int $jabatanUnitId = null;

    public string $jNama = '';

    public ?int $editJabatanId = null;

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

    public function bukaSetKepala(int $unitId): void
    {
        $this->reset(['cariKaryawan', 'tcNip', 'tcNama', 'tcTanggalMasuk']);
        $this->setKepalaUnitId = $unitId;
        $this->jabatanUnitId = null;
        $this->showForm = false;
    }

    public function tutupSetKepala(): void
    {
        $this->reset(['setKepalaUnitId', 'cariKaryawan', 'tcNip', 'tcNama', 'tcTanggalMasuk']);
    }

    public function pilihKepala(int $karyawanId): void
    {
        $unit = OrgUnit::findOrFail($this->setKepalaUnitId);
        $unit->setKepala(Karyawan::findOrFail($karyawanId));
        $this->tutupSetKepala();
    }

    public function tambahCepatKepala(): void
    {
        $data = $this->validate([
            'tcNip' => ['required', 'string', 'max:50', 'unique:karyawan,nip'],
            'tcNama' => ['required', 'string', 'max:150'],
            'tcTanggalMasuk' => ['nullable', 'date'],
        ]);

        $unit = OrgUnit::findOrFail($this->setKepalaUnitId);

        // Buat sebagai staff dulu (hindari ambiguitas kepala), lalu promosikan.
        $kar = Karyawan::create([
            'nip' => $data['tcNip'],
            'nama_lengkap' => $data['tcNama'],
            'tanggal_masuk' => $data['tcTanggalMasuk'] ?: null,
            'jabatan_id' => $unit->jabatanStaffDefault()->id,
            'org_unit_id' => $unit->id,
            'status' => 'aktif',
        ]);
        $unit->setKepala($kar);

        $this->tutupSetKepala();
    }

    public function bukaJabatan(int $unitId): void
    {
        $this->reset(['jNama', 'editJabatanId']);
        $this->jabatanUnitId = $unitId;
        $this->setKepalaUnitId = null;
        $this->showForm = false;
    }

    public function tutupJabatan(): void
    {
        $this->reset(['jabatanUnitId', 'jNama', 'editJabatanId']);
    }

    public function editJabatanStaff(int $id): void
    {
        $jab = Jabatan::findOrFail($id);
        $this->editJabatanId = $jab->id;
        $this->jNama = $jab->nama;
    }

    public function simpanJabatanStaff(): void
    {
        $data = $this->validate(['jNama' => ['required', 'string', 'max:120']]);

        Jabatan::updateOrCreate(
            ['id' => $this->editJabatanId],
            ['nama' => $data['jNama'], 'level' => 1, 'org_unit_id' => $this->jabatanUnitId, 'aktif' => true],
        );

        $this->reset(['jNama', 'editJabatanId']);
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

        $hasilCari = ($this->setKepalaUnitId && trim($this->cariKaryawan) !== '')
            ? Karyawan::aktif()
                ->where(fn ($q) => $q->where('nama_lengkap', 'like', '%'.trim($this->cariKaryawan).'%')
                    ->orWhere('nip', 'like', '%'.trim($this->cariKaryawan).'%'))
                ->orderBy('nama_lengkap')->limit(8)->get(['id', 'nama_lengkap', 'nip'])
            : collect();

        $jabatanUnit = $this->jabatanUnitId
            ? Jabatan::where('org_unit_id', $this->jabatanUnitId)->staff()->orderBy('nama')->get()
            : collect();

        return view('livewire.sdm.org-struktur', [
            'akar' => $akar,
            'semuaUnit' => OrgUnit::orderBy('nama')->get(['id', 'nama']),
            'tipeOptions' => OrgUnitTipe::cases(),
            'hasilCari' => $hasilCari,
            'jabatanUnit' => $jabatanUnit,
        ]);
    }
}
