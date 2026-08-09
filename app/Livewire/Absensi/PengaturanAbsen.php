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
    public bool $pengingatAktif = true;
    public ?int $jedaMasukMenit = null;
    public ?int $jedaPulangMenit = null;
    public ?int $ambangNyangkutJam = null;

    public function mount(): void
    {
        $p = PengaturanAbsensi::ambil();
        $this->officeLat = (float) $p->office_lat;
        $this->officeLong = (float) $p->office_long;
        $this->radiusM = $p->radius_m;
        $this->maxAkurasiM = $p->max_akurasi_m;
        $this->pengingatAktif = (bool) $p->pengingat_aktif;
        $this->jedaMasukMenit = $p->jeda_masuk_menit;
        $this->jedaPulangMenit = $p->jeda_pulang_menit;
        $this->ambangNyangkutJam = $p->ambang_nyangkut_jam;
    }

    public function simpanLokasi(): void
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

    /** Terpisah dari simpanLokasi supaya koordinat tak valid tak memblokir setelan pengingat. */
    public function simpanPengingat(): void
    {
        $data = $this->validate([
            'pengingatAktif' => ['boolean'],
            'jedaMasukMenit' => ['required', 'integer', 'min:0', 'max:240'],
            'jedaPulangMenit' => ['required', 'integer', 'min:0', 'max:240'],
            'ambangNyangkutJam' => ['required', 'integer', 'min:4', 'max:24'],
        ]);

        PengaturanAbsensi::ambil()->update([
            'pengingat_aktif' => $data['pengingatAktif'],
            'jeda_masuk_menit' => $data['jedaMasukMenit'],
            'jeda_pulang_menit' => $data['jedaPulangMenit'],
            'ambang_nyangkut_jam' => $data['ambangNyangkutJam'],
        ]);

        session()->flash('ok', 'Pengaturan pengingat absen disimpan.');
    }

    public function render()
    {
        return view('livewire.absensi.pengaturan-absen');
    }
}
