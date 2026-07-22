<?php

namespace App\Livewire\Pengganti;

use App\Enums\StatusPengajuanCuti;
use App\Enums\TipePengganti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use App\Support\ProsesPengganti;
use App\Support\ProsesPenggantiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Layar pengganti jadwal: cuti berjalan + lowongan karyawan nonaktif, satu
 * daftar. Sengaja terpisah dari CutiDetail — hanya info minimal (nama, unit,
 * rentang, cakupan); alasan & lampiran cuti tak dibuka di sini.
 *
 * Kartu dikunci string `cuti-<id>` / `low-<karyawanId>` supaya satu set form
 * (ajukan diri / alihkan) melayani kedua tipe tanpa cabang di blade.
 */
#[Layout('components.layouts.app')]
class PenggantiKelola extends Component
{
    public ?string $ajukanId = null;

    public ?string $alihId = null;

    public string $tanggalAksi = '';

    public string $cariPengganti = '';

    /** karyawan_id lowongan yang menunggu konfirmasi tombol "Selesai". */
    public ?int $selesaiKaryawanId = null;

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

    /** Kartu gabungan cuti + lowongan, satu bentuk, terurut tanggal mulai. */
    public function kartu(): Collection
    {
        $cuti = $this->daftar()->map(fn (PengajuanCuti $c) => [
            'kunci' => 'cuti-'.$c->id,
            'tipe' => TipePengganti::Cuti,
            'digantikan' => $c->karyawan,
            'judul' => $c->karyawan->nama_lengkap,
            'sub' => $c->karyawan->orgUnit?->nama.' · '
                .$c->tanggal_mulai->format('d M').' s/d '.$c->tanggal_selesai->format('d M Y'),
            'rencana' => $c->pengganti,
            'urut' => $c->tanggal_mulai->toDateString(),
        ]);

        $lowongan = ProsesPengganti::lowongan($this->unitTerlihat())->map(function (Karyawan $k) {
            $rencana = PenugasanPengganti::lowongan()
                ->where('karyawan_digantikan_id', $k->id)
                ->with('karyawan')->orderBy('tanggal_mulai')->get();

            return [
                'kunci' => 'low-'.$k->id,
                'tipe' => TipePengganti::Lowongan,
                'digantikan' => $k,
                'judul' => $k->nama_lengkap,
                'sub' => $k->orgUnit?->nama.' · Nonaktif sejak '
                    .($k->tanggal_nonaktif?->format('d M Y') ?? '—')
                    .' · '.($k->alasan_nonaktif?->value ?? '—'),
                'rencana' => $rencana,
                'urut' => $k->tanggal_nonaktif?->toDateString() ?? '9999-12-31',
            ];
        });

        return $cuti->concat($lowongan)->sortBy('urut')->values();
    }

    /** Sumber kasus untuk service, dari kunci kartu. */
    private function kasusDariKunci(string $kunci): PengajuanCuti|Karyawan
    {
        [$tipe, $id] = explode('-', $kunci, 2);

        return $tipe === 'low'
            ? Karyawan::findOrFail((int) $id)
            : PengajuanCuti::findOrFail((int) $id);
    }

    public function sayaKoordinatorUnit(?Karyawan $digantikan): bool
    {
        return $digantikan !== null
            && optional($digantikan->orgUnit?->kepala())->id === $this->saya()->id;
    }

    /** Saya rekan satu unit yang digantikan (dan bukan orangnya sendiri)? */
    public function sayaRekanUnit(?Karyawan $digantikan): bool
    {
        $saya = $this->saya();

        return $digantikan !== null
            && $saya->id !== $digantikan->id
            && $saya->org_unit_id !== null
            && $saya->org_unit_id === $digantikan->org_unit_id;
    }

    public function mulaiAjukan(string $kunci): void
    {
        $this->reset(['alihId', 'tanggalAksi', 'cariPengganti', 'selesaiKaryawanId']);
        $this->ajukanId = $kunci;
        $this->resetErrorBag();
    }

    public function mulaiAlih(string $kunci): void
    {
        $this->reset(['ajukanId', 'tanggalAksi', 'cariPengganti', 'selesaiKaryawanId']);
        $this->alihId = $kunci;
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->reset(['ajukanId', 'alihId', 'tanggalAksi', 'cariPengganti', 'selesaiKaryawanId']);
    }

    public function kirimAjukanDiri(): void
    {
        $this->validate(['tanggalAksi' => ['required', 'date']]);
        $kasus = $this->kasusDariKunci($this->ajukanId);

        try {
            ProsesPengganti::ajukanDiri($kasus, $this->saya(), Carbon::parse($this->tanggalAksi), auth()->user());
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

        $kasus = $this->kasusDariKunci($this->alihId);
        $digantikanId = $kasus instanceof PengajuanCuti ? $kasus->karyawan_id : $kasus->id;

        return Karyawan::aktif()
            ->where(fn ($q) => $q->where('nama_lengkap', 'like', '%'.$kunci.'%')
                ->orWhere('nip', 'like', '%'.$kunci.'%'))
            ->whereKeyNot($digantikanId)
            ->orderBy('nama_lengkap')
            ->limit(8)
            ->get();
    }

    public function pilihAlih(int $karyawanId): void
    {
        $this->validate(['tanggalAksi' => ['required', 'date']]);
        $kasus = $this->kasusDariKunci($this->alihId);
        $digantikan = $kasus instanceof PengajuanCuti ? $kasus->karyawan : $kasus;

        if (! $this->sayaKoordinatorUnit($digantikan)) {
            $this->addError('tanggalAksi', 'Hanya koordinator unit pemohon yang boleh mengalihkan.');

            return;
        }

        try {
            ProsesPengganti::alihkan(
                $kasus, Carbon::parse($this->tanggalAksi), Karyawan::aktif()->findOrFail($karyawanId), auth()->user(),
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
        $usulan = PenugasanPengganti::findOrFail($usulanId);
        try {
            ProsesPengganti::accUsulan($usulan, auth()->user());
            session()->flash('cuti_ok', 'Usulan disetujui.');
        } catch (ProsesPenggantiException $e) {
            $this->addError('usulan', $e->getMessage());
        }
    }

    public function tolak(int $usulanId): void
    {
        $usulan = PenugasanPengganti::findOrFail($usulanId);
        try {
            ProsesPengganti::tolakUsulan($usulan, auth()->user());
            session()->flash('cuti_ok', 'Usulan ditolak.');
        } catch (ProsesPenggantiException $e) {
            $this->addError('usulan', $e->getMessage());
        }
    }

    public function mulaiSelesai(int $karyawanId): void
    {
        $this->reset(['ajukanId', 'alihId', 'tanggalAksi', 'cariPengganti']);
        $this->selesaiKaryawanId = $karyawanId;
        $this->resetErrorBag();
    }

    public function batalSelesai(): void
    {
        $this->selesaiKaryawanId = null;
    }

    /** Penghapusan data → hanya lewat konfirmasi, dan hanya oleh koordinator unit. */
    public function konfirmasiSelesai(): void
    {
        $kar = Karyawan::findOrFail($this->selesaiKaryawanId);

        if (! $this->sayaKoordinatorUnit($kar)) {
            $this->addError('selesai', 'Hanya koordinator unit yang boleh menutup lowongan.');

            return;
        }

        ProsesPengganti::tutupLowongan($kar, now());
        session()->flash('cuti_ok', 'Lowongan ditutup. Jadwal sisa dan salinannya dilepas.');
        $this->batalSelesai();
    }

    public function render()
    {
        return view('livewire.pengganti.pengganti-kelola', [
            'kartu' => $this->kartu(),
            'hasilCariPengganti' => $this->hasilCariPengganti(),
        ]);
    }
}
