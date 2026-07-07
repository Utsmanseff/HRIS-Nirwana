<?php

namespace App\Livewire\Disiplin;

use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusSanksi;
use App\Models\ApprovalSanksi;
use App\Models\SanksiDisiplin;
use App\Support\ProsesSanksi;
use App\Support\ProsesSanksiException;
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

    public ?int $tingkatBaru = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('approve-disiplin'), 403);
    }

    public function tinjau(int $id): void
    {
        $this->tinjauId = $id;
        $this->catatan = '';
        $this->nomorSurat = '';
        $this->tingkatBaru = SanksiDisiplin::find($id)?->tingkat?->value;
        $this->resetErrorBag();
    }

    public function tutup(): void
    {
        $this->tinjauId = null;
        $this->catatan = '';
        $this->nomorSurat = '';
        $this->tingkatBaru = null;
    }

    protected function bolehSemua(): bool
    {
        return auth()->user()->hasRole(Role::Hrd->value);
    }

    protected function stepAktifUntukSaya(): ?ApprovalSanksi
    {
        if (! $this->tinjauId) {
            return null;
        }
        $s = SanksiDisiplin::with('approval')->find($this->tinjauId);
        $step = $s?->tahapAktif();

        return $step && $step->approver_id === auth()->user()->karyawan_id ? $step : null;
    }

    public function setujui(): void
    {
        $step = $this->stepAktifUntukSaya();
        if (! $step) {
            $this->tutup();

            return;
        }
        if ($step->peran === PeranApproval::Hrd) {
            $this->validate(['nomorSurat' => ['required', 'string', 'max:100']], [
                'nomorSurat.required' => 'Nomor surat wajib diisi di tahap HRD.',
            ]);
        }
        try {
            ProsesSanksi::setujui(
                $step,
                auth()->user(),
                $this->catatan ?: null,
                $this->tingkatBaru,
                $step->peran === PeranApproval::Hrd ? $this->nomorSurat : null,
            );
            session()->flash('disiplin_ok', 'Usulan disetujui, diteruskan ke tahap berikut.');
        } catch (ProsesSanksiException $e) {
            $this->addError($step->peran === PeranApproval::Hrd ? 'nomorSurat' : 'catatan', $e->getMessage());

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
            ProsesSanksi::tolak($step, auth()->user(), $this->catatan);
            session()->flash('disiplin_ok', 'Usulan ditolak.');
        } catch (ProsesSanksiException $e) {
            $this->addError('catatan', $e->getMessage());

            return;
        }
        $this->tutup();
    }

    public function terbitkan(): void
    {
        $step = $this->stepAktifUntukSaya();
        if (! $step) {
            $this->tutup();

            return;
        }
        $sanksi = SanksiDisiplin::find($this->tinjauId);
        if (trim((string) $sanksi?->nomor_surat) === '') {
            $this->validate(['nomorSurat' => ['required', 'string', 'max:100']], [
                'nomorSurat.required' => 'Nomor surat wajib diisi.',
            ]);
        }
        try {
            ProsesSanksi::terbit($step, auth()->user(), $this->nomorSurat ?: null, $this->catatan ?: null, $this->tingkatBaru);
            session()->flash('disiplin_ok', 'Sanksi diterbitkan, surat dibuat, karyawan diberi tahu.');
        } catch (ProsesSanksiException $e) {
            $this->addError('nomorSurat', $e->getMessage());

            return;
        }
        $this->tutup();
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
