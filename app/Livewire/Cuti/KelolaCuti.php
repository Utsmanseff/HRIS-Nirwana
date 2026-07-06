<?php

namespace App\Livewire\Cuti;

use App\Models\HariLibur;
use App\Models\JenisCuti;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KelolaCuti extends Component
{
    #[Url]
    public string $tab = 'hari-libur';

    public string $hlTanggal = '';

    public string $hlNama = '';

    public ?int $editHlId = null;

    public function simpanHariLibur(): void
    {
        $data = $this->validate([
            'hlTanggal' => ['required', 'date'],
            'hlNama' => ['required', 'string', 'max:120'],
        ]);

        HariLibur::updateOrCreate(
            ['id' => $this->editHlId],
            ['tanggal' => $data['hlTanggal'], 'nama' => $data['hlNama']],
        );
        $this->resetHariLibur();
        session()->flash('cuti_ok', 'Hari libur tersimpan.');
    }

    public function editHariLibur(int $id): void
    {
        $h = HariLibur::findOrFail($id);
        $this->editHlId = $h->id;
        $this->hlTanggal = $h->tanggal->toDateString();
        $this->hlNama = $h->nama;
    }

    public function hapusHariLibur(int $id): void
    {
        HariLibur::whereKey($id)->delete();
        if ($this->editHlId === $id) {
            $this->resetHariLibur();
        }
    }

    public function resetHariLibur(): void
    {
        $this->reset(['hlTanggal', 'hlNama', 'editHlId']);
    }

    public ?int $jcId = null;

    public string $jcNama = '';

    public ?string $jcEfek = null;

    public bool $jcPotongSaldo = false;

    public bool $jcButuhLampiran = false;

    public bool $jcBolehBackdate = false;

    public function editJenis(int $id): void
    {
        $j = JenisCuti::findOrFail($id);
        $this->jcId = $j->id;
        $this->jcNama = $j->nama;
        $this->jcEfek = $j->efek_penggajian;
        $this->jcPotongSaldo = (bool) $j->potong_saldo;
        $this->jcButuhLampiran = (bool) $j->butuh_lampiran;
        $this->jcBolehBackdate = (bool) $j->boleh_backdate;
    }

    public function simpanJenis(): void
    {
        $data = $this->validate([
            'jcNama' => ['required', 'string', 'max:80'],
            'jcEfek' => ['nullable', 'string', 'max:80'],
        ]);

        JenisCuti::whereKey($this->jcId)->update([
            'nama' => $data['jcNama'],
            'efek_penggajian' => $data['jcEfek'] ?: null,
            'potong_saldo' => $this->jcPotongSaldo,
            'butuh_lampiran' => $this->jcButuhLampiran,
            'boleh_backdate' => $this->jcBolehBackdate,
        ]);
        $this->reset(['jcId', 'jcNama', 'jcEfek', 'jcPotongSaldo', 'jcButuhLampiran', 'jcBolehBackdate']);
        session()->flash('cuti_ok', 'Jenis cuti diperbarui.');
    }

    public function toggleAktif(int $id): void
    {
        $j = JenisCuti::findOrFail($id);
        $j->update(['aktif' => ! $j->aktif]);
    }

    public function render()
    {
        return view('livewire.cuti.kelola-cuti', [
            'hariLibur' => HariLibur::orderBy('tanggal')->get(),
            'jenisCuti' => JenisCuti::orderBy('id')->get(),
        ]);
    }
}
