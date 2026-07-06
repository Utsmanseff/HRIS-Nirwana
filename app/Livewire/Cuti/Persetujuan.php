<?php

namespace App\Livewire\Cuti;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Persetujuan extends Component
{
    #[Url]
    public string $tab = 'perlu-aksi';

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
