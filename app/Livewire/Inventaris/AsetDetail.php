<?php

namespace App\Livewire\Inventaris;

use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\MutasiAset;
use App\Models\OrgUnit;
use App\Support\NavMenu;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AsetDetail extends Component
{
    public Aset $aset;

    #[Url]
    public string $tab = 'info';

    // Form mutasi
    public ?int $mutasiUnitId = null;
    public ?string $mutasiCatatan = null;

    // Form jadwal
    public ?int $editJadwalId = null;
    public string $jNama = '';
    public ?int $jInterval = null;

    public function mount(Aset $aset): void
    {
        abort_unless($this->bolehTim($aset), 403);
        $this->aset = $aset;
    }

    private function bolehTim(Aset $aset): bool
    {
        $timNilai = array_map(fn ($t) => $t->value, auth()->user()->timTeknis());

        return in_array($aset->kategori->tim->value, $timNilai, true);
    }

    public function simpanMutasi(): void
    {
        $this->validate([
            'mutasiUnitId' => ['required', 'exists:org_units,id'],
            'mutasiCatatan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () {
            MutasiAset::create([
                'aset_id' => $this->aset->id,
                'dari_unit_id' => $this->aset->org_unit_id,
                'ke_unit_id' => $this->mutasiUnitId,
                'tanggal' => now(),
                'oleh' => auth()->id(),
                'catatan' => $this->mutasiCatatan,
            ]);
            $this->aset->update(['org_unit_id' => $this->mutasiUnitId]);
        });

        $this->reset('mutasiUnitId', 'mutasiCatatan');
        $this->aset->refresh();
    }

    public function simpanJadwal(): void
    {
        $this->validate([
            'jNama' => ['required', 'string', 'max:100'],
            'jInterval' => ['required', 'integer', 'min:1'],
        ]);

        JadwalPemeliharaan::updateOrCreate(
            ['id' => $this->editJadwalId, 'aset_id' => $this->aset->id],
            ['nama' => $this->jNama, 'interval_bulan' => $this->jInterval, 'aktif' => true],
        );
        $this->reset('editJadwalId', 'jNama', 'jInterval');
    }

    public function editJadwal(int $id): void
    {
        $j = $this->aset->jadwalPemeliharaan()->findOrFail($id);
        $this->editJadwalId = $j->id;
        $this->jNama = $j->nama;
        $this->jInterval = $j->interval_bulan;
    }

    public function batalJadwal(): void
    {
        $this->reset('editJadwalId', 'jNama', 'jInterval');
    }

    public function tandaiJadwalSelesai(int $id): void
    {
        $j = $this->aset->jadwalPemeliharaan()->findOrFail($id);
        $j->update(['terakhir_dilakukan' => now()]);
    }

    public function hapusJadwal(int $id): void
    {
        $this->aset->jadwalPemeliharaan()->where('id', $id)->delete();
    }

    public function render()
    {
        $this->aset->load([
            'kategori', 'orgUnit', 'penanggungJawab',
            'mutasi.keUnit', 'mutasi.dariUnit', 'mutasi.oleh',
            'jadwalPemeliharaan', 'lampiran',
        ]);

        return view('livewire.inventaris.aset-detail', [
            'unitList' => OrgUnit::orderBy('nama')->get(),
            'menu' => NavMenu::untuk(auth()->user()),
        ]);
    }
}
