<?php

namespace App\Livewire\Inventaris;

use App\Models\Aset;
use App\Models\KategoriInventaris;
use App\Support\NavMenu;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class InventarisIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $kategoriId = null;

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $timNilai = array_map(fn ($t) => $t->value, auth()->user()->timTeknis());

        $aset = Aset::query()
            ->tim($timNilai)
            ->with(['kategori', 'orgUnit'])
            ->when($this->q !== '', fn ($qq) => $qq->where(fn ($w) => $w
                ->where('nama', 'like', "%{$this->q}%")
                ->orWhere('kode', 'like', "%{$this->q}%")
                ->orWhere('no_seri', 'like', "%{$this->q}%")))
            ->when($this->status !== '', fn ($qq) => $qq->where('status', $this->status))
            ->when($this->kategoriId, fn ($qq) => $qq->where('kategori_inventaris_id', $this->kategoriId))
            ->orderBy('kode')
            ->paginate(15);

        $kategori = KategoriInventaris::query()->tim($timNilai)->orderBy('nama')->get();

        return view('livewire.inventaris.inventaris-index', [
            'aset' => $aset,
            'kategoriList' => $kategori,
            'menu' => NavMenu::untuk(auth()->user()),
        ]);
    }
}
