<?php

namespace App\Livewire\Inventaris;

use App\Enums\StatusAset;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use App\Models\OrgUnit;
use App\Support\NavMenu;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AsetForm extends Component
{
    public ?Aset $aset = null;

    public string $kode = '';
    public string $nama = '';
    public ?int $kategoriId = null;
    public ?string $merk = null;
    public ?string $model = null;
    public ?string $noSeri = null;
    public ?string $tanggalPengadaan = null;
    public ?string $nilaiPerolehan = null;
    public ?int $orgUnitId = null;
    public ?int $penanggungJawabId = null;
    public string $status = 'baik';
    public ?string $keterangan = null;

    public function mount(?Aset $aset = null): void
    {
        if ($aset && $aset->exists) {
            $this->aset = $aset;
            $this->kode = $aset->kode;
            $this->nama = $aset->nama;
            $this->kategoriId = $aset->kategori_inventaris_id;
            $this->merk = $aset->merk;
            $this->model = $aset->model;
            $this->noSeri = $aset->no_seri;
            $this->tanggalPengadaan = $aset->tanggal_pengadaan?->format('Y-m-d');
            $this->nilaiPerolehan = $aset->nilai_perolehan;
            $this->orgUnitId = $aset->org_unit_id;
            $this->penanggungJawabId = $aset->penanggung_jawab_id;
            $this->status = $aset->status->value;
            $this->keterangan = $aset->keterangan;
        }
    }

    private function timBoleh(): array
    {
        return array_map(fn (TimTeknis $t) => $t->value, auth()->user()->timTeknis());
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', Rule::unique('aset', 'kode')->ignore($this->aset?->id)],
            'nama' => ['required', 'string', 'max:150'],
            'kategoriId' => ['required', Rule::exists('kategori_inventaris', 'id')->where(fn ($q) => $q->whereIn('tim', $this->timBoleh()))],
            'merk' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'noSeri' => ['nullable', 'string', 'max:100'],
            'tanggalPengadaan' => ['nullable', 'date'],
            'nilaiPerolehan' => ['nullable', 'numeric', 'min:0'],
            'orgUnitId' => ['nullable', 'exists:org_units,id'],
            'penanggungJawabId' => ['nullable', 'exists:karyawan,id'],
            'status' => ['required', Rule::enum(StatusAset::class)],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    public function simpan()
    {
        $this->validate();
        $payload = [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kategori_inventaris_id' => $this->kategoriId,
            'merk' => $this->merk,
            'model' => $this->model,
            'no_seri' => $this->noSeri,
            'tanggal_pengadaan' => $this->tanggalPengadaan ?: null,
            'nilai_perolehan' => $this->nilaiPerolehan ?: null,
            'org_unit_id' => $this->orgUnitId,
            'penanggung_jawab_id' => $this->penanggungJawabId,
            'status' => $this->status,
            'keterangan' => $this->keterangan,
        ];

        if ($this->aset) {
            $this->aset->update($payload);
            $id = $this->aset->id;
        } else {
            $id = Aset::create($payload)->id;
        }

        session()->flash('ok', 'Aset tersimpan.');

        // Halaman detail (Task 13) belum tentu ada saat build inkremental; fallback ke index.
        $tujuan = Route::has('inventaris.detail') ? route('inventaris.detail', $id) : route('inventaris');

        return $this->redirect($tujuan, navigate: true);
    }

    public function render()
    {
        return view('livewire.inventaris.aset-form', [
            'kategoriList' => KategoriInventaris::whereIn('tim', $this->timBoleh())->where('aktif', true)->orderBy('nama')->get(),
            'unitList' => OrgUnit::orderBy('nama')->get(),
            'statusOpsi' => StatusAset::cases(),
            'menu' => NavMenu::untuk(auth()->user()),
        ]);
    }
}
