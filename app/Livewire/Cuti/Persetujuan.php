<?php

namespace App\Livewire\Cuti;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use App\Support\ProsesApproval;
use App\Support\ProsesApprovalException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Persetujuan extends Component
{
    #[Url]
    public string $tab = 'perlu-aksi';

    public ?int $tinjauId = null;

    public string $catatan = '';

    public function tinjau(int $id): void
    {
        $this->tinjauId = $id;
        $this->catatan = '';
        $this->resetErrorBag();
    }

    public function tutup(): void
    {
        $this->tinjauId = null;
        $this->catatan = '';
    }

    private function stepAktifUntukSaya(): ?\App\Models\ApprovalCuti
    {
        if (! $this->tinjauId) {
            return null;
        }
        $p = PengajuanCuti::with('approval')->find($this->tinjauId);
        $step = $p?->tahapAktif();

        return $step && $step->approver_id === auth()->user()->karyawan_id ? $step : null;
    }

    public function setujui(): void
    {
        $step = $this->stepAktifUntukSaya();
        if (! $step) {
            $this->tutup();

            return;
        }
        try {
            ProsesApproval::setujui($step, auth()->user(), $this->catatan ?: null);
            session()->flash('cuti_ok', 'Pengajuan disetujui.');
        } catch (ProsesApprovalException $e) {
            $this->addError('catatan', $e->getMessage());

            return;
        }
        $this->tutup();
    }

    public function tolak(): void
    {
        $this->validate(['catatan' => ['required', 'string', 'max:1000']], [
            'catatan.required' => 'Catatan alasan wajib diisi saat menolak.',
        ]);
        $step = $this->stepAktifUntukSaya();
        if (! $step) {
            $this->tutup();

            return;
        }
        try {
            ProsesApproval::tolak($step, auth()->user(), $this->catatan);
            session()->flash('cuti_ok', 'Pengajuan ditolak.');
        } catch (ProsesApprovalException $e) {
            $this->addError('catatan', $e->getMessage());

            return;
        }
        $this->tutup();
    }

    /** Pengajuan yang tahap aktifnya menunjuk karyawan login. */
    private function perluAksi(): Collection
    {
        $karyawanId = auth()->user()->karyawan_id;

        return PengajuanCuti::query()
            ->whereIn('status', [StatusPengajuanCuti::Diajukan, StatusPengajuanCuti::Diproses])
            ->whereHas('approval', fn ($q) => $q
                ->where('approver_id', $karyawanId)
                ->where('status', StatusApproval::Menunggu))
            ->with(['karyawan.jabatan', 'jenisCuti', 'approval.approver'])
            ->orderBy('tanggal_mulai')
            ->get()
            ->filter(fn (PengajuanCuti $p) => $p->tahapAktif()?->approver_id === $karyawanId)
            ->values();
    }

    public function render()
    {
        return view('livewire.cuti.persetujuan', [
            'perluAksi' => $this->perluAksi(),
        ]);
    }
}
