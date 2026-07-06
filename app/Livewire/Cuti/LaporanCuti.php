<?php

namespace App\Livewire\Cuti;

use App\Models\JenisCuti;
use App\Models\OrgUnit;
use App\Support\RekapCuti;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class LaporanCuti extends Component
{
    use WithPagination;

    #[Url]
    public string $dari = '';

    #[Url]
    public string $sampai = '';

    #[Url]
    public string $unitId = '';

    #[Url]
    public string $jenisId = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
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

    /** @return array{dari:string,sampai:string,unit_id:?string,jenis_id:?string,status:?string} */
    private function filter(): array
    {
        return [
            'dari' => $this->dari,
            'sampai' => $this->sampai,
            'unit_id' => $this->unitId ?: null,
            'jenis_id' => $this->jenisId ?: null,
            'status' => $this->status ?: null,
        ];
    }

    public function render()
    {
        $f = $this->filter();

        return view('livewire.cuti.laporan-cuti', [
            'status_hitung' => RekapCuti::hitungStatus($f),
            'pengajuan' => RekapCuti::query($f)->paginate(15),
            'daftarUnit' => OrgUnit::orderBy('nama')->get(),
            'daftarJenis' => JenisCuti::orderBy('id')->get(),
        ]);
    }
}
