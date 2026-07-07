<?php

namespace App\Livewire\Disiplin;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\OrgUnit;
use App\Support\RekapDisiplin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class LaporanDisiplin extends Component
{
    use WithPagination;

    #[Url]
    public string $dari = '';

    #[Url]
    public string $sampai = '';

    #[Url]
    public string $unitId = '';

    #[Url]
    public string $tingkat = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('kelola-disiplin'), 403);
        if ($this->dari === '') {
            $this->dari = Carbon::now()->startOfYear()->toDateString();
        }
        if ($this->sampai === '') {
            $this->sampai = Carbon::now()->endOfYear()->toDateString();
        }
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    /** @return array{dari:string,sampai:string,unit_id:?string,tingkat:?string,status:?string} */
    private function filter(): array
    {
        return [
            'dari' => $this->dari,
            'sampai' => $this->sampai,
            'unit_id' => $this->unitId ?: null,
            'tingkat' => $this->tingkat ?: null,
            'status' => $this->status ?: null,
        ];
    }

    public function render()
    {
        $f = $this->filter();

        return view('livewire.disiplin.laporan-disiplin', [
            'status_hitung' => RekapDisiplin::hitungStatus($f),
            'sanksi' => RekapDisiplin::query($f)->paginate(15),
            'daftarUnit' => OrgUnit::orderBy('nama')->get(),
            'tingkatOpsi' => TingkatSanksi::cases(),
            'statusOpsi' => StatusSanksi::cases(),
        ]);
    }
}
