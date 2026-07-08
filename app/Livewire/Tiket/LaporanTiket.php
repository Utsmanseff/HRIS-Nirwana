<?php

namespace App\Livewire\Tiket;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Support\NavMenu;
use App\Support\RekapTiket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class LaporanTiket extends Component
{
    use WithPagination;

    #[Url]
    public string $dari = '';
    #[Url]
    public string $sampai = '';
    #[Url]
    public string $status = '';
    #[Url]
    public string $prioritas = '';
    #[Url]
    public string $jenis = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    private function timNilai(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    private function filter(): array
    {
        return [
            'tim' => $this->timNilai(),
            'status' => $this->status ?: null,
            'prioritas' => $this->prioritas ?: null,
            'jenis' => $this->jenis ?: null,
            'dari' => $this->dari ?: null,
            'sampai' => $this->sampai ?: null,
        ];
    }

    public function render()
    {
        $f = $this->filter();

        return view('livewire.tiket.laporan-tiket', [
            'menu' => NavMenu::untuk(auth()->user()),
            'tiket' => RekapTiket::query($f)->paginate(15),
            'metrik' => RekapTiket::metrikPerTim($f),
            'statusOpsi' => StatusTiket::cases(),
            'prioritasOpsi' => PrioritasTiket::cases(),
            'jenisOpsi' => JenisTiket::cases(),
            'query' => array_filter([
                'dari' => $this->dari, 'sampai' => $this->sampai, 'status' => $this->status,
                'prioritas' => $this->prioritas, 'jenis' => $this->jenis,
            ]),
        ]);
    }
}
