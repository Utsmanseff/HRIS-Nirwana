<?php

namespace App\Livewire\Cuti;

use App\Enums\KodeJenisCuti;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use App\Support\ProsesApproval;
use App\Support\ProsesApprovalException;
use App\Support\SaldoCuti;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Persetujuan extends Component
{
    #[Url]
    public string $tab = 'perlu-aksi';

    #[Url]
    public string $cari = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterJenis = '';

    public ?int $tinjauId = null;

    public string $catatan = '';

    public ?int $batalId = null;

    public string $alasanBatal = '';

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

    private function bolehSemua(): bool
    {
        return auth()->user()->hasRole(Role::Hrd->value);
    }

    private function semuaPengajuan(): Collection
    {
        if (! $this->bolehSemua()) {
            return collect();
        }

        return PengajuanCuti::query()
            ->when($this->cari !== '', fn ($q) => $q->whereHas('karyawan', fn ($k) => $k
                ->where('nama_lengkap', 'like', "%{$this->cari}%")
                ->orWhere('nip', 'like', "%{$this->cari}%")))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterJenis !== '', fn ($q) => $q->where('jenis_cuti_id', $this->filterJenis))
            ->with(['karyawan.jabatan', 'jenisCuti', 'approval.approver'])
            ->latest()
            ->limit(100)
            ->get();
    }

    public function mulaiBatal(int $id): void
    {
        $this->batalId = $id;
        $this->alasanBatal = '';
        $this->resetErrorBag();
    }

    public function konfirmasiBatal(): void
    {
        $this->validate(['alasanBatal' => ['required', 'string', 'max:1000']], [
            'alasanBatal.required' => 'Alasan pembatalan wajib diisi.',
        ]);
        $p = PengajuanCuti::find($this->batalId);
        if (! $p) {
            $this->batalId = null;

            return;
        }
        try {
            ProsesApproval::batalkanOlehHrd($p, auth()->user(), $this->alasanBatal);
            session()->flash('cuti_ok', 'Cuti dibatalkan.');
        } catch (ProsesApprovalException $e) {
            $this->addError('alasanBatal', $e->getMessage());

            return;
        }
        $this->batalId = null;
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

    /** Info jatah (hanya cuti_tahunan) untuk panel tinjau. */
    private function saldoTinjau(?PengajuanCuti $p): ?array
    {
        if (! $p || $p->jenisCuti->kode !== KodeJenisCuti::CutiTahunan) {
            return null;
        }
        $acuan = Carbon::parse($p->tanggal_mulai);
        $saldo = SaldoCuti::untuk($p->karyawan);
        $jatah = $saldo->jatah($acuan);
        $terpakai = $saldo->terpakai($acuan);

        return [
            'jatah' => $jatah,
            'terpakai' => $terpakai,
            'diminta' => $p->jumlah_hari,
            'sisa' => $jatah - $terpakai - $p->jumlah_hari,
        ];
    }

    public function render()
    {
        $tinjauan = $this->tinjauId
            ? PengajuanCuti::with(['karyawan.jabatan', 'jenisCuti', 'approval.approver'])->find($this->tinjauId)
            : null;

        return view('livewire.cuti.persetujuan', [
            'perluAksi' => $this->perluAksi(),
            'bolehSemua' => $this->bolehSemua(),
            'semua' => $this->tab === 'semua' ? $this->semuaPengajuan() : collect(),
            'jenisOpsi' => \App\Models\JenisCuti::orderBy('nama')->get(),
            'tinjauan' => $tinjauan,
            'saldoTinjau' => $this->saldoTinjau($tinjauan),
        ]);
    }
}
