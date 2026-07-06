<?php

namespace App\Livewire\Cuti;

use App\Models\HariLibur;
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

    public function render()
    {
        return view('livewire.cuti.kelola-cuti', [
            'hariLibur' => HariLibur::orderBy('tanggal')->get(),
        ]);
    }
}
