<?php

namespace App\Livewire\Sdm;

use App\Models\Karyawan;
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

    public function render()
    {
        return view('livewire.sdm.karyawan-detail');
    }
}
