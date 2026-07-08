<?php

namespace App\Livewire\Inventaris;

use App\Enums\StatusAset;
use App\Models\KategoriInventaris;
use App\Models\OrgUnit;
use App\Support\NavMenu;
use App\Support\RekapInventaris;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class LaporanInventaris extends Component
{
    use WithPagination;

    #[Url]
    public ?int $kategoriId = null;

    #[Url]
    public ?int $unitId = null;

    #[Url]
    public string $status = '';

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    /** @return list<string> */
    private function timNilai(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    private function filter(): array
    {
        return [
            'tim' => $this->timNilai(),
            'kategori_id' => $this->kategoriId,
            'unit_id' => $this->unitId,
            'status' => $this->status ?: null,
        ];
    }

    public function render()
    {
        $timNilai = $this->timNilai();

        return view('livewire.inventaris.laporan-inventaris', [
            'aset' => RekapInventaris::query($this->filter())->paginate(15),
            'strip' => RekapInventaris::hitungStatus($this->filter()),
            'kategoriList' => KategoriInventaris::query()->tim($timNilai)->orderBy('nama')->get(),
            'unitList' => OrgUnit::orderBy('nama')->get(),
            'statusOpsi' => StatusAset::cases(),
            'menu' => NavMenu::untuk(auth()->user()),
        ]);
    }
}
