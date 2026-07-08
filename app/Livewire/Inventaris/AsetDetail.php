<?php

namespace App\Livewire\Inventaris;

use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\LampiranAset;
use App\Models\MutasiAset;
use App\Models\OrgUnit;
use App\Support\KompresGambar;
use App\Support\NavMenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class AsetDetail extends Component
{
    use WithFileUploads;

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

    // Form lampiran
    public string $lampiranTipe = 'faktur';
    public ?string $lampiranTanggal = null;
    public ?string $lampiranBerlakuSampai = null;
    public $berkas = null;

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

    public function simpanLampiran(): void
    {
        $this->validate([
            'lampiranTipe' => ['required', 'string', 'max:50'],
            'berkas' => ['required', 'file', 'max:8192', 'mimes:jpg,jpeg,png,webp,pdf'],
            'lampiranTanggal' => ['nullable', 'date'],
            'lampiranBerlakuSampai' => ['nullable', 'date'],
        ]);

        $isPdf = strtolower($this->berkas->getClientOriginalExtension()) === 'pdf'
            || $this->berkas->getMimeType() === 'application/pdf';
        $dir = 'aset/'.$this->aset->id;

        if ($isPdf) {
            $path = $this->berkas->store($dir, 'local');
            $mime = 'application/pdf';
        } else {
            // Kompres agresif: webp q70 + downscale sisi terpanjang 1600px.
            $webp = KompresGambar::keWebp(file_get_contents($this->berkas->getRealPath()), 70, 1600);
            $path = $dir.'/'.Str::uuid().'.webp';
            Storage::disk('local')->put($path, $webp);
            $mime = 'image/webp';
        }

        LampiranAset::create([
            'aset_id' => $this->aset->id,
            'tipe' => $this->lampiranTipe,
            'path' => $path,
            'mime' => $mime,
            'tanggal' => $this->lampiranTanggal ?: null,
            'berlaku_sampai' => $this->lampiranBerlakuSampai ?: null,
        ]);

        $this->reset('berkas', 'lampiranTanggal', 'lampiranBerlakuSampai');
        $this->aset->refresh();
    }

    public function hapusLampiran(int $id): void
    {
        $l = $this->aset->lampiran()->findOrFail($id);
        Storage::disk('local')->delete($l->path);
        $l->delete();
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
