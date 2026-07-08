<?php

namespace App\Livewire\Tiket;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Models\Tiket;
use App\Support\NavMenu;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class TiketIndex extends Component
{
    use WithPagination;

    public bool $adalahTim = false;

    #[Url]
    public string $q = '';
    /** '' = semua status (termasuk selesai/batal); 'aktif' = baru+diproses; selain itu = status spesifik. */
    #[Url]
    public string $status = 'aktif';
    #[Url]
    public string $prioritas = '';
    #[Url]
    public string $jenis = '';

    public function mount(): void
    {
        $this->adalahTim = count(auth()->user()->timTeknis()) > 0;
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    private function timNilai(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    public function render()
    {
        $q = Tiket::query()->with(['aset', 'pelapor']);

        if ($this->adalahTim) {
            $q->tim($this->timNilai());
        } else {
            $q->where('pelapor_id', auth()->user()->karyawan_id);
        }

        if ($this->q !== '') {
            $q->where(fn ($w) => $w
                ->where('nomor', 'like', "%{$this->q}%")
                ->orWhere('judul', 'like', "%{$this->q}%"));
        }
        if ($this->status === 'aktif') {
            $q->whereIn('status', array_map(fn ($s) => $s->value, StatusTiket::aktif()));
        } elseif ($this->status !== '') {
            $q->where('status', $this->status);
        }
        // status === '' → semua status (tanpa filter)
        if ($this->prioritas !== '') {
            $q->where('prioritas', $this->prioritas);
        }
        if ($this->jenis !== '') {
            $q->where('jenis', $this->jenis);
        }

        // Urut: prioritas mendesak dulu, lalu terbaru.
        $q->orderByRaw("CASE prioritas WHEN 'urgent' THEN 4 WHEN 'tinggi' THEN 3 WHEN 'sedang' THEN 2 ELSE 1 END DESC")
            ->orderByDesc('waktu_lapor');

        return view('livewire.tiket.tiket-index', [
            'tiket' => $q->paginate(15),
            'menu' => NavMenu::untuk(auth()->user()),
            'statusOpsi' => StatusTiket::cases(),
            'prioritasOpsi' => PrioritasTiket::cases(),
            'jenisOpsi' => JenisTiket::cases(),
        ]);
    }
}
