<?php

namespace App\Livewire\Disiplin;

use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusSanksi;
use App\Models\SanksiDisiplin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PersetujuanDisiplin extends Component
{
    #[Url]
    public string $tab = 'perlu-aksi';

    #[Url]
    public string $cari = '';

    #[Url]
    public string $filterStatus = '';

    public ?int $tinjauId = null;

    public string $catatan = '';

    public string $nomorSurat = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('approve-disiplin'), 403);
    }

    public function tinjau(int $id): void
    {
        $this->tinjauId = $id;
        $this->catatan = '';
        $this->nomorSurat = '';
        $this->resetErrorBag();
    }

    public function tutup(): void
    {
        $this->tinjauId = null;
        $this->catatan = '';
        $this->nomorSurat = '';
    }

    protected function bolehSemua(): bool
    {
        return auth()->user()->hasRole(Role::Hrd->value);
    }

    /** Sanksi yang tahap aktifnya = karyawan login. */
    protected function perluAksi(): Collection
    {
        $karyawanId = auth()->user()->karyawan_id;

        return SanksiDisiplin::query()
            ->whereIn('status', [StatusSanksi::Diajukan, StatusSanksi::Diproses])
            ->whereHas('approval', fn ($q) => $q
                ->where('approver_id', $karyawanId)
                ->where('status', StatusApproval::Menunggu))
            ->with(['karyawan.jabatan', 'pengusul', 'approval.approver'])
            ->latest()
            ->get()
            ->filter(fn (SanksiDisiplin $s) => $s->tahapAktif()?->approver_id === $karyawanId)
            ->values();
    }

    protected function semua(): Collection
    {
        if (! $this->bolehSemua()) {
            return collect();
        }

        return SanksiDisiplin::query()
            ->when($this->cari !== '', fn ($q) => $q->whereHas('karyawan', fn ($k) => $k
                ->where('nama_lengkap', 'like', "%{$this->cari}%")
                ->orWhere('nip', 'like', "%{$this->cari}%")))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->with(['karyawan.jabatan', 'pengusul', 'approval.approver'])
            ->latest()
            ->limit(100)
            ->get();
    }

    public function render()
    {
        $tinjauan = $this->tinjauId
            ? SanksiDisiplin::with(['karyawan.jabatan', 'pengusul', 'approval.approver'])->find($this->tinjauId)
            : null;

        return view('livewire.disiplin.persetujuan-disiplin', [
            'perluAksi' => $this->perluAksi(),
            'bolehSemua' => $this->bolehSemua(),
            'semua' => $this->tab === 'semua' ? $this->semua() : collect(),
            'tinjauan' => $tinjauan,
            'tahapAktif' => $tinjauan?->tahapAktif(),
        ]);
    }
}
