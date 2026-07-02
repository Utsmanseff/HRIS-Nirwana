<?php

namespace App\Livewire\Sdm;

use App\Enums\JenisKontrak;
use App\Models\Karyawan;
use App\Support\PengingatKontrak;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KaryawanDetail extends Component
{
    public Karyawan $karyawan;

    #[Url]
    public string $tab = 'profil';

    public function mount(Karyawan $karyawan): void
    {
        $this->karyawan = $karyawan->load(['orgUnit.parent', 'jabatan', 'atasan.jabatan', 'kontrak', 'dokumen', 'user.roles']);
    }

    public function inisial(): string
    {
        return collect(explode(' ', trim($this->karyawan->nama_lengkap)))
            ->filter()->take(2)
            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    }

    /** Kontrak urut terbaru→terlama untuk timeline. */
    public function riwayatKontrak(): Collection
    {
        return $this->karyawan->kontrak->sortByDesc('tanggal_mulai')->sortByDesc('id')->values();
    }

    /** Id kontrak PKWT pertama (anchor hak cuti tahunan) — null bila belum ada PKWT. */
    public function idAnchorCuti(): ?int
    {
        return $this->karyawan->kontrak
            ->filter(fn ($k) => $k->jenis === JenisKontrak::Pkwt)
            ->sortBy('tanggal_mulai')->sortBy('id')
            ->first()?->id;
    }

    public function pengingatKontrak(): ?PengingatKontrak
    {
        return PengingatKontrak::untuk($this->karyawan);
    }

    public function ukuranBaca(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format(max(1, round($bytes / 1024))).' KB';
    }

    public function render()
    {
        return view('livewire.sdm.karyawan-detail');
    }
}
