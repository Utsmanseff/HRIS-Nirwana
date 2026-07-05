<?php

namespace App\Livewire\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CutiDetail extends Component
{
    public PengajuanCuti $pengajuan;

    public function mount(PengajuanCuti $pengajuan): void
    {
        abort_unless($pengajuan->karyawan_id === auth()->user()->karyawan_id, 403);
        $this->pengajuan = $pengajuan->load(['jenisCuti', 'approval.approver']);
    }

    /** Pemohon boleh batal hanya bila belum final (diajukan/diproses). */
    public function batalkan()
    {
        abort_unless(
            in_array($this->pengajuan->status, [StatusPengajuanCuti::Diajukan, StatusPengajuanCuti::Diproses], true),
            403,
        );

        $this->pengajuan->update([
            'status' => StatusPengajuanCuti::Dibatalkan,
            'dibatalkan_oleh' => auth()->id(),
            'alasan_batal' => 'Dibatalkan oleh pemohon.',
        ]);

        return $this->redirectRoute('cuti');
    }

    public function render()
    {
        return view('livewire.cuti.cuti-detail');
    }
}
