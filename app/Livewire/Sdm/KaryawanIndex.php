<?php

namespace App\Livewire\Sdm;

use App\Enums\JabatanLevel;
use App\Enums\JenisKontrak;
use App\Enums\SeverityPengingat;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Support\PengingatKontrak;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class KaryawanIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cari = '';

    #[Url]
    public string $unitId = '';

    #[Url]
    public string $level = '';

    #[Url]
    public string $kontrakJenis = '';

    #[Url]
    public string $status = 'aktif';

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function updatedUnitId(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedKontrakJenis(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /** Id unit terpilih + seluruh turunannya (tabel org kecil — hitung di PHP). */
    private function unitIds(): array
    {
        $semua = OrgUnit::get(['id', 'parent_id']);
        $ids = [(int) $this->unitId];
        $antrian = [(int) $this->unitId];
        while ($antrian) {
            $anak = $semua->whereIn('parent_id', $antrian)->pluck('id')->all();
            $ids = array_merge($ids, $anak);
            $antrian = $anak;
        }

        return $ids;
    }

    /** Teks + kelas badge untuk kolom Kontrak. */
    public function badgeKontrak(Karyawan $k): array
    {
        $terbaru = $k->kontrakTerbaru;
        if (! $terbaru) {
            return ['Belum ada kontrak', 'badge-neutral'];
        }

        $pengingat = PengingatKontrak::untuk($k);
        if ($pengingat) {
            return $pengingat->severity === SeverityPengingat::Terlewat
                ? [$terbaru->jenis->label().' terlewat', 'badge-danger']
                : [$terbaru->jenis->label().' · H-'.$pengingat->sisaHari, 'badge-warning'];
        }

        return [
            $terbaru->jenis->label(),
            $terbaru->jenis === JenisKontrak::Tetap ? 'badge-brand' : 'badge-info',
        ];
    }

    public function render()
    {
        $karyawan = Karyawan::query()
            ->with(['orgUnit.parent', 'jabatan', 'atasan.jabatan', 'kontrakTerbaru', 'kontrak'])
            ->when($this->cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama_lengkap', 'like', '%'.$this->cari.'%')
                ->orWhere('nip', 'like', '%'.$this->cari.'%')))
            ->when($this->unitId !== '', fn ($q) => $q->whereIn('org_unit_id', $this->unitIds()))
            ->when($this->level !== '', fn ($q) => $q->whereHas('jabatan', fn ($w) => $w->where('level', (int) $this->level)))
            ->when($this->kontrakJenis !== '', fn ($q) => $q->whereHas('kontrakTerbaru', fn ($w) => $w->where('jenis', $this->kontrakJenis)))
            ->when($this->status !== 'semua', fn ($q) => $q->where('status', $this->status))
            ->orderBy('nama_lengkap')
            ->paginate(15);

        return view('livewire.sdm.karyawan-index', [
            'karyawan' => $karyawan,
            'unitOptions' => OrgUnit::orderBy('nama')->get(['id', 'nama']),
            'levelOptions' => JabatanLevel::cases(),
            'kontrakOptions' => JenisKontrak::cases(),
        ]);
    }
}
