<?php

namespace App\Livewire\Absensi;

use App\Models\PengaturanAbsensi;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PengaturanAbsen extends Component
{
    public ?float $officeLat = null;
    public ?float $officeLong = null;
    public ?int $radiusM = null;
    public ?int $maxAkurasiM = null;

    public function mount(): void
    {
        $p = PengaturanAbsensi::ambil();
        $this->officeLat = (float) $p->office_lat;
        $this->officeLong = (float) $p->office_long;
        $this->radiusM = $p->radius_m;
        $this->maxAkurasiM = $p->max_akurasi_m;
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'officeLat' => ['required', 'numeric', 'between:-90,90'],
            'officeLong' => ['required', 'numeric', 'between:-180,180'],
            'radiusM' => ['required', 'integer', 'min:10', 'max:100000'],
            'maxAkurasiM' => ['required', 'integer', 'min:5', 'max:100000'],
        ]);

        PengaturanAbsensi::ambil()->update([
            'office_lat' => $data['officeLat'],
            'office_long' => $data['officeLong'],
            'radius_m' => $data['radiusM'],
            'max_akurasi_m' => $data['maxAkurasiM'],
        ]);

        session()->flash('ok', 'Pengaturan lokasi absen disimpan.');
    }

    public function render()
    {
        return view('livewire.absensi.pengaturan-absen');
    }
}
