<?php

namespace App\Livewire\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Layar estafet pengganti cuti. Sengaja terpisah dari CutiDetail: hanya info
 * minimal (pemohon, unit, rentang, cakupan) — alasan & lampiran cuti tak dibuka.
 */
#[Layout('components.layouts.app')]
class PenggantiKelola extends Component
{
    public ?int $ajukanId = null;

    public ?int $alihId = null;

    public string $tanggalAksi = '';

    public string $cariPengganti = '';

    private function saya(): Karyawan
    {
        return auth()->user()->karyawan()->firstOrFail();
    }

    /** Unit yang boleh saya lihat: unit sendiri + subtree unit yang saya pimpin. */
    private function unitTerlihat(): array
    {
        $kar = $this->saya();
        $ids = $kar->org_unit_id ? [$kar->org_unit_id] : [];
        foreach ($kar->unitDipimpin() as $u) {
            $ids = array_merge($ids, OrgUnit::denganTurunan($u->id));
        }

        return array_values(array_unique($ids)) ?: [-1];
    }

    /** Cuti disetujui yang masa cutinya belum lewat. */
    public function daftar(): Collection
    {
        return PengajuanCuti::query()
            ->where('status', StatusPengajuanCuti::Disetujui)
            ->whereDate('tanggal_selesai', '>=', now()->toDateString())
            ->whereHas('karyawan', fn ($q) => $q->whereIn('org_unit_id', $this->unitTerlihat()))
            ->with(['karyawan.orgUnit', 'pengganti.karyawan'])
            ->orderBy('tanggal_mulai')
            ->get();
    }

    /** Saya koordinator (kepala unit) pemohon cuti ini? */
    public function sayaKoordinator(PengajuanCuti $cuti): bool
    {
        return optional($cuti->karyawan->orgUnit?->kepala())->id === $this->saya()->id;
    }

    /** Saya rekan satu unit pemohon (dan bukan pemohonnya)? */
    public function sayaRekan(PengajuanCuti $cuti): bool
    {
        $saya = $this->saya();

        return $saya->id !== $cuti->karyawan_id
            && $saya->org_unit_id !== null
            && $saya->org_unit_id === $cuti->karyawan->org_unit_id;
    }

    public function mulaiAjukan(int $pengajuanId): void
    {
        $this->reset(['alihId', 'tanggalAksi', 'cariPengganti']);
        $this->ajukanId = $pengajuanId;
        $this->resetErrorBag();
    }

    public function mulaiAlih(int $pengajuanId): void
    {
        $this->reset(['ajukanId', 'tanggalAksi', 'cariPengganti']);
        $this->alihId = $pengajuanId;
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->reset(['ajukanId', 'alihId', 'tanggalAksi', 'cariPengganti']);
    }

    public function kirimAjukanDiri(): void
    {
        $this->validate(['tanggalAksi' => ['required', 'date']]);
        $cuti = PengajuanCuti::findOrFail($this->ajukanId);

        try {
            ProsesPengganti::ajukanDiri($cuti, $this->saya(), Carbon::parse($this->tanggalAksi), auth()->user());
            session()->flash('cuti_ok', 'Usulan terkirim, menunggu acc koordinator.');
        } catch (ProsesPenggantiException $e) {
            $this->addError('tanggalAksi', $e->getMessage());

            return;
        }
        $this->batal();
    }

    public function hasilCariPengganti()
    {
        $kunci = trim($this->cariPengganti);
        if ($kunci === '' || ! $this->alihId) {
            return collect();
        }
        $cuti = PengajuanCuti::find($this->alihId);

        return Karyawan::aktif()
            ->where(fn ($q) => $q->where('nama_lengkap', 'like', '%'.$kunci.'%')
                ->orWhere('nip', 'like', '%'.$kunci.'%'))
            ->whereKeyNot($cuti?->karyawan_id ?? -1)
            ->orderBy('nama_lengkap')
            ->limit(8)
            ->get();
    }

    public function pilihAlih(int $karyawanId): void
    {
        $this->validate(['tanggalAksi' => ['required', 'date']]);
        $cuti = PengajuanCuti::findOrFail($this->alihId);

        if (! $this->sayaKoordinator($cuti)) {
            $this->addError('tanggalAksi', 'Hanya koordinator unit pemohon yang boleh mengalihkan.');

            return;
        }

        try {
            ProsesPengganti::alihkan(
                $cuti, Carbon::parse($this->tanggalAksi), Karyawan::aktif()->findOrFail($karyawanId), auth()->user(),
            );
            session()->flash('cuti_ok', 'Cakupan pengganti dialihkan.');
        } catch (ProsesPenggantiException $e) {
            $this->addError('tanggalAksi', $e->getMessage());

            return;
        }
        $this->batal();
    }

    public function acc(int $usulanId): void
    {
        $usulan = PenggantiCuti::findOrFail($usulanId);
        try {
            ProsesPengganti::accUsulan($usulan, auth()->user());
            session()->flash('cuti_ok', 'Usulan disetujui.');
        } catch (ProsesPenggantiException $e) {
            $this->addError('usulan', $e->getMessage());
        }
    }

    public function tolak(int $usulanId): void
    {
        $usulan = PenggantiCuti::findOrFail($usulanId);
        try {
            ProsesPengganti::tolakUsulan($usulan, auth()->user());
            session()->flash('cuti_ok', 'Usulan ditolak.');
        } catch (ProsesPenggantiException $e) {
            $this->addError('usulan', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.cuti.pengganti-kelola', [
            'daftar' => $this->daftar(),
            'hasilCariPengganti' => $this->hasilCariPengganti(),
        ]);
    }
}
