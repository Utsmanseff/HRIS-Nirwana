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

    /** @var array<int, string> id karyawan terpilih (string dari checkbox) */
    public array $pilihan = [];

    public bool $pilihSemua = false;

    public string $unitTujuan = '';

    /** @var array<int, string> id pada halaman aktif — diisi tiap render */
    public array $idsHalaman = [];

    public function updatedCari(): void
    {
        $this->resetPage();
        $this->batalPilih();
    }

    public function updatedUnitId(): void
    {
        $this->resetPage();
        $this->batalPilih();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
        $this->batalPilih();
    }

    public function updatedKontrakJenis(): void
    {
        $this->resetPage();
        $this->batalPilih();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->batalPilih();
    }

    public function updatedPilihSemua(bool $nilai): void
    {
        $this->pilihan = $nilai ? $this->idsHalaman : [];
    }

    public function batalPilih(): void
    {
        $this->reset(['pilihan', 'pilihSemua', 'unitTujuan']);
    }

    public function terapkanUbahUnit(): void
    {
        $this->validate([
            'unitTujuan' => ['required', 'exists:org_units,id'],
        ]);

        Karyawan::whereIn('id', $this->pilihan)->update(['org_unit_id' => (int) $this->unitTujuan]);

        $this->batalPilih();
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
            ->saring([
                'cari' => $this->cari,
                'unit_id' => $this->unitId,
                'level' => $this->level,
                'kontrak_jenis' => $this->kontrakJenis,
                'status' => $this->status,
            ])
            ->orderBy('nama_lengkap')
            ->paginate(15);

        $this->idsHalaman = $karyawan->pluck('id')->map(fn ($id) => (string) $id)->all();

        return view('livewire.sdm.karyawan-index', [
            'karyawan' => $karyawan,
            'unitOptions' => OrgUnit::orderBy('nama')->get(['id', 'nama']),
            'levelOptions' => JabatanLevel::cases(),
            'kontrakOptions' => JenisKontrak::cases(),
        ]);
    }
}
